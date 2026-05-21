<?php

namespace App\Http\Controllers;

use App\Models\LegalAct;
use App\Models\ExecutorStatusLog;
use App\Models\ExecutionAttachment;
use App\Models\ExecutionNote;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExecutorDashboardController extends Controller
{
    private ?array $icraOlunubNoteIds = null;

    private function getIcraOlunubNoteIds(): array
    {
        return $this->icraOlunubNoteIds ??= ExecutionNote::active()
            ->where(fn($q) => $q->where('note', 'like', '%İcra olunub%')->orWhere('note', 'like', '%icra olunub%'))
            ->pluck('id')
            ->toArray();
    }

    private function isIcraOlunubNote(?int $noteId): bool
    {
        return $noteId && in_array($noteId, $this->getIcraOlunubNoteIds());
    }

    private function isQismenIcraNote(int $noteId): bool
    {
        return ExecutionNote::where('id', $noteId)->where('note', 'like', '%qismən icra olunub%')->exists();
    }

    public function index()
    {
        $user = auth()->user();
        if (!$user->executor_id && !$user->canManage() && !$user->department_id)
            abort(403, 'Sizin icraçı profiliniz yoxdur.');
        $executionNotes = ExecutionNote::active()->get();

        // Admin sees all executors; managers only see executors within their OWN dept subtree
        // (not the whole can_assign ancestor subtree — that would expose sibling depts).
        if ($user->isAdmin()) {
            $allExecutors = \App\Models\Executor::with('department')->active()->get();
        } elseif ($user->canManage() && $user->canAssignDeptId() && $user->department_id) {
            $deptIds      = Department::descendantIdsOf($user->department_id);
            $allExecutors = \App\Models\Executor::with('department')->active()->whereIn('department_id', $deptIds)->get();
        } else {
            $allExecutors = collect();
        }

        // Org-filter tabs: ancestor depts with can_assign=true only.
        // Own dept is intentionally excluded — it issues tasks downward and never
        // receives tasks from itself, so the tab would be empty and misleading.
        $visibleOrgs = collect();
        if ($user->effectiveDeptId()) {
            $ownDeptId   = $user->effectiveDeptId();
            $ownDept     = Department::active()->find($ownDeptId);
            $ancestorIds = array_map('intval', $ownDept?->ancestorIds() ?? []);
            if (!empty($ancestorIds)) {
                // Depth map: root ancestor = 0, immediate parent = deepest. Sort root-first.
                $depthMap = [];
                foreach (array_reverse($ancestorIds) as $depth => $id) {
                    $depthMap[$id] = $depth;
                }
                $visibleOrgs = Department::active()
                    ->whereIn('id', $ancestorIds)
                    ->where('can_assign', true)
                    ->get()
                    ->sortBy(fn($dept) => $depthMap[$dept->id] ?? 999)
                    ->values();
            }
        }

        return view('executor.index', compact('executionNotes', 'allExecutors', 'visibleOrgs'));
    }

    public function load(Request $request)
    {
        $user       = auth()->user();
        $executorId = $user->executor_id;

        // Only admin may view as a specific executor
        if ($user->isAdmin() && $request->filled('view_as_executor_id')) {
            $executorId = (int) $request->input('view_as_executor_id');
        }

        // Determine which executor IDs this user can see tasks for
        $visibleExecutorIds = $this->resolveVisibleExecutorIds($user, $executorId);

        if (empty($visibleExecutorIds) && !$user->isAdmin()) {
            return response()->json(['draw' => (int) $request->input('draw', 1), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []]);
        }

        $draw   = $request->input('draw', 1);
        $start  = $request->input('start', 0);
        $length = $request->input('length', 25);

        $orgId = $request->filled('organization_id') ? (int) $request->input('organization_id') : null;

        $baseQuery = LegalAct::active();
        if (!empty($visibleExecutorIds)) {
            $baseQuery->whereHas('executors', fn($q) => $q->whereIn('executors.id', $visibleExecutorIds));
        }
        if ($orgId) {
            $baseQuery->where('organization_id', $orgId);
        }
        $totalRecords = (clone $baseQuery)->count();

        $query = LegalAct::with([
            'actType',
            'issuingAuthority',
            'executors.department',
            'statusLogs' => fn($q) => $q->with(['executionNote', 'user', 'attachments', 'approvedByUser'])->reorder('created_at', 'asc'),
            'insertedUser',
        ])->active();

        if (!empty($visibleExecutorIds)) {
            $query->whereHas('executors', fn($q) => $q->whereIn('executors.id', $visibleExecutorIds));
        }
        if ($orgId) {
            $query->where('organization_id', $orgId);
        }
        if ($request->filled('col.legal_act_number')) {
            foreach (preg_split('/\s+/', trim($request->input('col.legal_act_number'))) as $term) {
                $query->where('legal_act_number', 'like', '%' . $term . '%');
            }
        }
        if ($request->filled('col.summary')) {
            foreach (preg_split('/\s+/', trim($request->input('col.summary'))) as $term) {
                $query->where('summary', 'like', '%' . $term . '%');
            }
        }

        $filteredRecords = (clone $query)->count();
        $orderCol = (int) $request->input('order.0.column', 2);
        $orderDir = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
        match ($orderCol) {
            1 => $query->orderBy('legal_act_number', $orderDir),
            2 => $query->orderBy('legal_act_date', $orderDir),
            6 => $query->orderBy('execution_deadline', $orderDir),
            default => $query->orderBy('id', 'desc'),
        };

        $results    = $query->skip($start)->take($length)->get();
        $data       = [];

        // The "primary" executor ID for status tracking
        $primaryExecutorId = $executorId ?? ($user->executor_id);

        // Pre-load all user IDs belonging to the primary executor (for dept-mate locking)
        $primaryExecutorAllUserIds = [];
        if ($primaryExecutorId) {
            $primaryExecutorAllUserIds = \App\Models\User::where('executor_id', $primaryExecutorId)
                ->where('is_deleted', false)
                ->pluck('id')
                ->toArray();
        }

        $viewUserIds = [$user->id];
        if ($user->canManage() && $executorId && $executorId !== $user->executor_id) {
            $viewUserIds = \App\Models\User::where('executor_id', $executorId)->pluck('id')->toArray();
        }

        foreach ($results as $i => $act) {
            $myLatestLog = $act->statusLogs->whereIn('user_id', $viewUserIds)->sortByDesc('id')->first();
            $noteText    = $myLatestLog?->executionNote?->note ?? '';

            $myIsIcraOlunub = $myLatestLog && $this->isIcraOlunubNote($myLatestLog->execution_note_id);
            $myIsExecuted   = $myIsIcraOlunub && $myLatestLog->approval_status === 'approved';
            $myIsPending    = $myIsIcraOlunub && in_array($myLatestLog->approval_status, ['pending', 'partial']);
            $myIsRejected   = $myIsIcraOlunub && $myLatestLog->approval_status === 'rejected';

            $daysLeft = null;
            $rowClass = '';
            if ($myIsExecuted) {
                $rowClass = 'row-executed';
            } elseif ($myIsPending) {
                $rowClass = 'row-pending';
            } elseif ($act->execution_deadline) {
                $daysLeft = (int) now()->startOfDay()->diffInDays($act->execution_deadline->startOfDay(), false);
                $rowClass = $daysLeft < 0 ? 'row-overdue' : ($daysLeft <= 3 ? 'row-warning' : '');
            }

            $deadlineHtml = '-';
            if ($act->execution_deadline) {
                $deadlineHtml = $act->execution_deadline->format('d.m.Y');
                if (!$myIsExecuted && !$myIsPending && $daysLeft !== null) {
                    if ($daysLeft < 0)
                        $deadlineHtml .= '<br><span class="badge bg-danger text-white mt-1">İcra müddəti bitib</span>';
                    elseif ($daysLeft <= 3)
                        $deadlineHtml .= '<br><span class="badge bg-warning text-dark mt-1">' . $daysLeft . ' gün qalıb</span>';
                }
            }

            $statusHtml = '-';
            if ($myLatestLog) {
                if ($myIsExecuted) {
                    $statusHtml = '<span class="badge bg-success">İcra olunub ✓</span>';
                } elseif ($myIsPending) {
                    $statusHtml = '<span class="badge bg-warning text-dark">Təsdiq gözləyir</span>';
                } elseif ($myIsRejected) {
                    $statusHtml = '<span class="badge bg-danger">İmtina edilib</span>';
                    if ($myLatestLog->approval_note)
                        $statusHtml .= '<br><small class="text-danger">' . e(Str::limit($myLatestLog->approval_note, 30)) . '</small>';
                } else {
                    $statusHtml = '<span class="badge bg-secondary">' . e(Str::limit($noteText, 25)) . '</span>';
                }
                if ($myLatestLog->custom_note && !$myIsRejected)
                    $statusHtml .= '<br><small class="text-muted">' . e(Str::limit($myLatestLog->custom_note, 30)) . '</small>';
            }

            // Attachment indicator: true if any status log on this act has uploaded files.
            $hasAttachments = $act->statusLogs->flatMap(fn($log) => $log->attachments)->isNotEmpty();

            // Role badge for primary executor
            $pivot    = $primaryExecutorId ? $act->executors->where('id', $primaryExecutorId)->first()?->pivot : null;
            $roleHtml = $pivot?->role === 'main'
                ? '<span class="badge bg-primary">Əsas</span>'
                : '<span class="badge bg-info">Digər</span>';

            // Check if any user in the same executor already has an approved icra-olunub log
            $executorApproved = !empty($primaryExecutorAllUserIds) && $act->statusLogs
                ->whereIn('user_id', $primaryExecutorAllUserIds)
                ->filter(fn($log) => $this->isIcraOlunubNote($log->execution_note_id) && $log->approval_status === 'approved')
                ->isNotEmpty();

            // canChangeStatus applies for own executor OR admin acting as a specific executor
            $isOwnExecutor   = $primaryExecutorId && $primaryExecutorId === $user->executor_id;
            $isAdminActingAs = $user->isAdmin() && $executorId !== null;
            $canAct          = $isOwnExecutor || $isAdminActingAs;

            // 1-hour grace period for executor; admin acting on behalf has no time limit
            $graceMinsLeft = null;
            $canWithdraw   = false;
            if ($myIsPending && $canAct && $myLatestLog) {
                if ($isAdminActingAs) {
                    $graceMinsLeft = 999;
                    $canWithdraw   = true;
                } else {
                    $minsElapsed   = (int) $myLatestLog->created_at->diffInMinutes(now());
                    $graceMinsLeft = max(0, 60 - $minsElapsed);
                    $canWithdraw   = $graceMinsLeft > 0;
                }
            }

            $data[] = [
                'DT_RowClass'     => $rowClass,
                'id'              => $act->id,
                'rowNum'          => $start + $i + 1,
                'actType'         => $act->actType?->name ?? '-',
                'legalActNumber'  => $act->legal_act_number ?? '-',
                'legalActDate'    => $act->legal_act_date?->format('d.m.Y') ?? '-',
                'issuingAuthority'=> $act->issuingAuthority?->name ?? '-',
                'summary'         => Str::limit($act->summary, 80) ?? '-',
                'taskNumber'      => $act->task_number ?? '-',
                'deadlineHtml'    => $deadlineHtml,
                'statusHtml'      => $statusHtml,
                'roleHtml'        => $roleHtml,
                'proofRequired'   => (bool) $act->proof_required,
                'canChangeStatus' => !$executorApproved && !$myIsExecuted && !$myIsPending && $canAct,
                'canWithdraw'     => $canWithdraw,
                'graceMinsLeft'   => $graceMinsLeft,
                'isAdminActingAs' => $isAdminActingAs,
                'actingAsExecutorId' => $isAdminActingAs ? $executorId : null,
                'hasAttachments'  => $hasAttachments,
            ];
        }

        return response()->json(['draw' => (int) $draw, 'recordsTotal' => $totalRecords, 'recordsFiltered' => $filteredRecords, 'data' => $data]);
    }

    public function show(LegalAct $legalAct)
    {
        $user = auth()->user();
        $this->authorizeAccess($legalAct, $user);

        $legalAct->load([
            'actType',
            'issuingAuthority',
            'executors.department',
            'statusLogs' => fn($q) => $q->with(['executionNote', 'user.executor.department', 'user.department', 'attachments', 'approvedByUser.department'])->reorder('created_at', 'asc'),
            'attachments.user',
            'insertedUser.department',
        ]);

        $mainExecutors   = $legalAct->executors->where('pivot.role', 'main')->values();
        $helperExecutors = $legalAct->executors->where('pivot.role', 'helper')->values();

        // Resolve the executor's own private task_description
        // Priority: pivot task_description → global task_description
        $myTaskDescription = $legalAct->task_description; // fallback: global
        if ($user->executor_id) {
            $myExecutor = $legalAct->executors->where('id', $user->executor_id)->first();
            if ($myExecutor && $myExecutor->pivot->task_description !== null) {
                $myTaskDescription = $myExecutor->pivot->task_description;
            }
        } elseif ($user->department_id) {
            // Dept supervisor: show the global task (not any subordinate's private task)
            $myTaskDescription = $legalAct->task_description;
        }

        // Scope status logs to the viewer's own dept subtree (issue 5):
        // Admin sees all logs; everyone else sees only logs from executors in their subtree.
        $viewerDeptIds = null;
        if (!$user->isAdmin() && $user->effectiveDeptId()) {
            $viewerDeptIds = Department::descendantIdsOf($user->effectiveDeptId());
        }

        $visibleLogs = $viewerDeptIds === null
            ? $legalAct->statusLogs
            : $legalAct->statusLogs->filter(function ($log) use ($viewerDeptIds, $user) {
                // Always include own logs
                if ($log->user_id === $user->id) return true;
                // Include logs from users whose executor is in viewer's subtree
                $execDeptId = $log->user?->executor?->department_id;
                if ($execDeptId && in_array((int) $execDeptId, $viewerDeptIds)) return true;
                // Include logs from users whose own department is in viewer's subtree
                $userDeptId = $log->user?->department_id;
                return $userDeptId && in_array((int) $userDeptId, $viewerDeptIds);
            });

        return response()->json([
            'id'                  => $legalAct->id,
            'act_type'            => $legalAct->actType?->name,
            'legal_act_number'    => $legalAct->legal_act_number,
            'legal_act_date'      => $legalAct->legal_act_date?->format('d.m.Y'),
            'summary'             => $legalAct->summary,
            'issuing_authority'   => $legalAct->issuingAuthority?->name,
            'main_executors'      => $mainExecutors->map(fn($e) => ['id' => $e->id, 'name' => $e->name, 'department' => $e->department?->name, 'task_description' => $e->pivot->task_description]),
            'helper_executors'    => $helperExecutors->map(fn($e) => ['id' => $e->id, 'name' => $e->name, 'department' => $e->department?->name, 'task_description' => $e->pivot->task_description]),
            'main_executor'       => $mainExecutors->first()?->name,
            'main_executor_department' => $mainExecutors->first()?->department?->name,
            'helper_executor'     => $helperExecutors->first()?->name,
            'helper_executor_department' => $helperExecutors->first()?->department?->name,
            'task_number'         => $legalAct->task_number,
            'task_description'    => $myTaskDescription,        // private or global
            'global_task_description' => $legalAct->task_description, // always the global one
            'execution_deadline'  => $legalAct->execution_deadline?->format('d.m.Y'),
            'attachments'         => $legalAct->attachments
                ->filter(fn($a) => is_null($a->status_log_id))
                ->values()
                ->map(fn($a) => [
                    'id'        => $a->id,
                    'name'      => $a->original_name,
                    'size'      => round($a->file_size / 1024, 1) . ' KB',
                    'mime_type' => $a->mime_type,
                ]),
            'related_document_number' => $legalAct->related_document_number,
            'related_document_date'   => $legalAct->related_document_date?->format('d.m.Y'),
            'proof_required'      => (bool) $legalAct->proof_required,
            'inserted_user'            => $legalAct->insertedUser?->full_name,
            'inserted_user_department' => $legalAct->insertedUser?->department?->name,
            'created_at'          => $legalAct->created_at?->format('d.m.Y H:i'),
            'status_logs'         => $visibleLogs->values()->map(fn($log) => [
                'id'              => $log->id,
                'user'            => $log->user?->full_name,
                // Prefer the executor record's department (authoritative for executor-role users),
                // fall back to the user's own department_id for manager/admin submitters.
                'user_department' => $log->user?->executor?->department?->name
                    ?? $log->user?->department?->name,
                'executor_id'     => $log->user?->executor_id,
                'note'            => $log->executionNote?->note,
                'custom_note'     => $log->custom_note,
                'date'            => $log->created_at?->format('d.m.Y H:i'),
                'approval_status' => $log->approval_status,
                'approval_note'   => $log->approval_note,
                'approved_by'            => $log->approvedByUser?->full_name,
                'approved_by_department' => $log->approvedByUser?->department?->name,
                'approved_at'     => $log->approved_at?->format('d.m.Y H:i'),
                'attachments'     => $log->attachments->map(fn($att) => ['id' => $att->id, 'name' => $att->original_name, 'size' => round($att->file_size / 1024, 1) . ' KB', 'mime_type' => $att->mime_type]),
            ]),
        ]);
    }

    public function storeStatus(Request $request, LegalAct $legalAct)
    {
        $user = auth()->user();
        $this->authorizeAccess($legalAct, $user);

        // Resolve the acting user: admin can act on behalf of an executor
        $actingUserId     = $user->id;
        $actingExecutorId = $user->executor_id;
        $isAdminOnBehalf  = false;
        if (!$user->executor_id) {
            if (!$user->isAdmin()) {
                abort(403, 'Yalnız icraçılar status göndərə bilər.');
            }
            $onBehalfOfExecutorId = (int) $request->input('on_behalf_of_executor_id');
            if (!$onBehalfOfExecutorId) {
                return back()->withErrors(['general' => 'Admin olaraq status göndərmək üçün icraçı seçilməlidir.']);
            }
            $targetUser = \App\Models\User::where('executor_id', $onBehalfOfExecutorId)->where('is_deleted', false)->first();
            if (!$targetUser) {
                return back()->withErrors(['general' => 'Seçilmiş icraçıya aid istifadəçi tapılmadı.']);
            }
            $actingUserId     = $targetUser->id;
            $actingExecutorId = $onBehalfOfExecutorId;
            $isAdminOnBehalf  = true;
        }

        $validated = $request->validate([
            'execution_note_id' => 'required|exists:execution_notes,id',
            'custom_note'       => 'nullable|string|max:2000',
            'attachments'       => 'nullable|array|max:10',
            'attachments.*'     => 'file|max:10240',
        ]);

        $isIcraOlunub = $this->isIcraOlunubNote((int) $validated['execution_note_id']);

        $myLatestIcraLog = ExecutorStatusLog::where('legal_act_id', $legalAct->id)
            ->where('user_id', $actingUserId)
            ->whereIn('execution_note_id', $this->getIcraOlunubNoteIds())
            ->orderByDesc('id')
            ->first();

        if ($myLatestIcraLog) {
            if ($myLatestIcraLog->approval_status === 'approved') {
                return back()->withErrors(['general' => 'Bu sənəd menecer tərəfindən artıq təsdiqlənib. Yeni status göndərmək mümkün deyil.']);
            }
            if (in_array($myLatestIcraLog->approval_status, ['pending', 'partial'])) {
                $minsElapsed = (int) $myLatestIcraLog->created_at->diffInMinutes(now());
                if ($isAdminOnBehalf || $minsElapsed <= 60) {
                    // Admin on behalf: always replace. Executor: within 1-hour grace period.
                    $this->deleteStatusLog($myLatestIcraLog);
                } else {
                    return back()->withErrors(['general' => 'Sizin təsdiq gözləyən icra qeydiniz var.']);
                }
            }
        }

        // Block if any other user sharing the same executor already has an approved icra-olunub log.
        // This locks the document for the whole executor once one dept-mate's submission is approved.
        if ($actingExecutorId) {
            $deptMateUserIds = \App\Models\User::where('executor_id', $actingExecutorId)
                ->where('is_deleted', false)
                ->where('id', '!=', $actingUserId)
                ->pluck('id')
                ->toArray();
            if (!empty($deptMateUserIds)) {
                $deptMateApproved = ExecutorStatusLog::where('legal_act_id', $legalAct->id)
                    ->whereIn('user_id', $deptMateUserIds)
                    ->whereIn('execution_note_id', $this->getIcraOlunubNoteIds())
                    ->where('approval_status', 'approved')
                    ->exists();
                if ($deptMateApproved) {
                    return back()->withErrors(['general' => 'Bu sənəd icraçı bölməniz üçün artıq başqası tərəfindən icra edilib və təsdiqlənib. Yeni status göndərmək mümkün deyil.']);
                }
            }
        }

        if ($isIcraOlunub && $legalAct->proof_required) {
            $hasValidFiles = false;
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    if ($file && $file->isValid()) {
                        $hasValidFiles = true;
                        break;
                    }
                }
            }
            if (!$hasValidFiles) {
                return back()->withErrors(['attachments' => '"İcra olunub" statusu seçildikdə bu sənəd üçün ən azı bir sübut sənəd yükləmək MƏCBURİDİR!'])->withInput();
            }
        }

        $allowedMimes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png', 'image/jpg'];
        $allowedExts  = ['doc', 'docx', 'pdf', 'jpg', 'jpeg', 'png'];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if (!$file || !$file->isValid()) continue;
                if (!in_array($file->getClientMimeType(), $allowedMimes) && !in_array(strtolower($file->getClientOriginalExtension()), $allowedExts)) {
                    return back()->withErrors(['attachments' => 'Yalnız Word, PDF və şəkil faylları qəbul olunur.'])->withInput();
                }
            }
        }

        // Each executor's "icra olunub" submission goes immediately to pending review —
        // no waiting for other executors on the same document.
        $approvalStatus = $isIcraOlunub ? 'pending' : null;

        $statusLog = ExecutorStatusLog::create([
            'legal_act_id'      => $legalAct->id,
            'user_id'           => $actingUserId,
            'execution_note_id' => $validated['execution_note_id'],
            'custom_note'       => $validated['custom_note'] ?? null,
            'approval_status'   => $approvalStatus,
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if (!$file || !$file->isValid()) continue;
                $path = $file->store('execution-attachments/' . $legalAct->id, 'local');
                ExecutionAttachment::create([
                    'legal_act_id'  => $legalAct->id,
                    'user_id'       => $actingUserId,
                    'status_log_id' => $statusLog->id,
                    'file_path'     => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type'     => $file->getClientMimeType(),
                    'file_size'     => $file->getSize(),
                ]);
            }
        }

        $successMsg = $approvalStatus === 'pending'
            ? 'İcra statusu göndərildi. Admin/menecer təsdiqi gözlənilir.'
            : 'Status uğurla yeniləndi.';

        return redirect()->route('executor.index')->with('success', $successMsg);
    }

    public function withdrawStatus(Request $request, LegalAct $legalAct)
    {
        $user = auth()->user();
        $this->authorizeAccess($legalAct, $user);

        // Resolve the acting user: admin can act on behalf of an executor
        $actingUserId = $user->id;
        $isAdminOnBehalf = false;
        if (!$user->executor_id) {
            if (!$user->isAdmin()) {
                abort(403, 'Yalnız icraçılar status geri ala bilər.');
            }
            $onBehalfOfExecutorId = (int) $request->input('on_behalf_of_executor_id');
            if (!$onBehalfOfExecutorId) {
                return back()->withErrors(['general' => 'Admin olaraq geri almaq üçün icraçı seçilməlidir.']);
            }
            $targetUser = \App\Models\User::where('executor_id', $onBehalfOfExecutorId)->where('is_deleted', false)->first();
            if (!$targetUser) {
                return back()->withErrors(['general' => 'Seçilmiş icraçıya aid istifadəçi tapılmadı.']);
            }
            $actingUserId = $targetUser->id;
            $isAdminOnBehalf = true;
        }

        // Block if the icra-olunub log was already approved by a manager
        $approvedLog = ExecutorStatusLog::where('legal_act_id', $legalAct->id)
            ->where('user_id', $actingUserId)
            ->whereIn('execution_note_id', $this->getIcraOlunubNoteIds())
            ->where('approval_status', 'approved')
            ->exists();

        if ($approvedLog) {
            return back()->withErrors(['general' => 'Bu sənəd menecer tərəfindən artıq təsdiqlənib. Geri almaq mümkün deyil.']);
        }

        $log = ExecutorStatusLog::where('legal_act_id', $legalAct->id)
            ->where('user_id', $actingUserId)
            ->whereIn('execution_note_id', $this->getIcraOlunubNoteIds())
            ->where('approval_status', 'pending')
            ->orderByDesc('id')
            ->first();

        if (!$log) {
            return back()->withErrors(['general' => 'Geri alınacaq icra qeydi tapılmadı.']);
        }

        // Admin on behalf: no time limit. Executor: 1-hour window only.
        if (!$isAdminOnBehalf) {
            $minsElapsed = (int) $log->created_at->diffInMinutes(now());
            if ($minsElapsed > 60) {
                return back()->withErrors(['general' => '1 saatlıq düzəliş müddəti bitib. Statusu geri almaq mümkün deyil.']);
            }
        }

        $this->deleteStatusLog($log);

        return redirect()->route('executor.index')->with('success', 'İcra statusu uğurla geri alındı. İndi yenidən status göndərə bilərsiniz.');
    }

    public function downloadAttachment(ExecutionAttachment $attachment)
    {
        $this->authorizeAttachmentAccess($attachment);
        return response()->download($this->getAttachmentPath($attachment), $attachment->original_name);
    }

    public function previewAttachment(ExecutionAttachment $attachment)
    {
        $this->authorizeAttachmentAccess($attachment);
        $fullPath = $this->getAttachmentPath($attachment);
        $ext = strtolower(pathinfo($attachment->original_name, PATHINFO_EXTENSION));

        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            return response()->file($fullPath, ['Content-Type' => $ext === 'png' ? 'image/png' : 'image/jpeg', 'Content-Disposition' => 'inline; filename="' . $attachment->original_name . '"']);
        }
        if ($ext === 'pdf') {
            return response()->file($fullPath, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="' . $attachment->original_name . '"']);
        }
        if ($ext === 'docx') {
            return response()->file($fullPath, ['Content-Type' => 'application/octet-stream', 'Content-Disposition' => 'inline; filename="' . $attachment->original_name . '"']);
        }
        if ($ext === 'doc') {
            try {
                $phpWord  = \PhpOffice\PhpWord\IOFactory::load($fullPath, 'MsDoc');
                $tempPath = storage_path('app/private/temp_preview_' . uniqid() . '.docx');
                $writer   = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
                $writer->save($tempPath);
                return response()->file($tempPath, ['Content-Type' => 'application/octet-stream', 'Content-Disposition' => 'inline; filename="preview.docx"'])->deleteFileAfterSend(true);
            } catch (\Exception $e) {
                return response()->json(['error' => true, 'message' => '.doc çevrilə bilmədi: ' . $e->getMessage()], 422);
            }
        }
        return response()->download($fullPath, $attachment->original_name);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function deleteStatusLog(ExecutorStatusLog $log): void
    {
        $log->load('attachments');
        foreach ($log->attachments as $attachment) {
            foreach ([
                storage_path('app/private/' . $attachment->file_path),
                storage_path('app/' . $attachment->file_path),
            ] as $path) {
                if (file_exists($path)) @unlink($path);
            }
            $attachment->delete();
        }
        $log->delete();
    }

    /**
     * Resolve which executor IDs this user may see tasks for.
     * - Admin: requested executor or empty (meaning all)
     * - Manager: restricted to their assignable dept subtree; must not cross into other managers' scopes
     * - Executor user: their own executor_id only
     * - Dept user (no executor_id): all executors in dept subtree
     */
    private function resolveVisibleExecutorIds($user, ?int $requestedExecutorId): array
    {
        if ($user->isAdmin()) {
            return $requestedExecutorId ? [$requestedExecutorId] : [];
        }

        if ($user->canManage()) {
            if (!$user->canAssignDeptId()) return [];

            // Scope to the user's OWN dept subtree — never to the can_assign ancestor's subtree.
            // This prevents siblings from seeing each other's tasks.
            $ownDeptId = $user->department_id ?? $user->canAssignDeptId();
            $deptIds   = Department::descendantIdsOf($ownDeptId);
            $scopeIds  = \App\Models\Executor::whereIn('department_id', $deptIds)
                ->where('is_deleted', false)
                ->pluck('id')
                ->toArray();

            if ($requestedExecutorId) {
                // Silently reject requests for executors outside this manager's scope
                return in_array($requestedExecutorId, $scopeIds) ? [$requestedExecutorId] : [];
            }
            return $scopeIds;
        }

        // Direct executor
        if ($user->executor_id) {
            return [$user->executor_id];
        }

        // Dept user: see all executors in dept subtree
        if ($user->department_id) {
            $deptIds = Department::descendantIdsOf($user->department_id);
            return \App\Models\Executor::whereIn('department_id', $deptIds)
                ->where('is_deleted', false)
                ->pluck('id')
                ->toArray();
        }

        return [];
    }

    private function getAttachmentPath(ExecutionAttachment $attachment): string
    {
        foreach ([storage_path('app/private/' . $attachment->file_path), storage_path('app/' . $attachment->file_path)] as $path) {
            if (file_exists($path)) return $path;
        }
        abort(404, 'Fayl tapılmadı.');
    }

    private function authorizeAttachmentAccess(ExecutionAttachment $attachment): void
    {
        $user = auth()->user();
        if ($attachment->legalAct && $user->isExecutor())
            $this->authorizeAccess($attachment->legalAct, $user);
    }

    /**
     * Authorize read access to a legal act.
     * - Admin: always allowed
     * - Manager: only if the act has an executor within their assignable dept subtree
     * - Executor: must be assigned to the act
     * - Dept user: any executor in their subtree must be assigned
     */
    private function authorizeAccess(LegalAct $legalAct, $user): void
    {
        if ($user->isAdmin()) return;

        if ($user->canManage()) {
            $assignDeptId = $user->canAssignDeptId();
            if ($assignDeptId) {
                $deptIds = Department::descendantIdsOf($assignDeptId);
                if ($legalAct->executors()->whereIn('executors.department_id', $deptIds)->exists()) return;
            }
            abort(403, 'Bu sənədə giriş icazəniz yoxdur.');
        }

        // Direct executor assignment
        if ($user->executor_id && $legalAct->executors()->where('executors.id', $user->executor_id)->exists()) {
            return;
        }

        // Dept hierarchy: parent dept user may view tasks assigned to child depts
        if ($user->department_id) {
            $deptIds   = Department::descendantIdsOf($user->department_id);
            $hasAccess = $legalAct->executors()
                ->whereIn('executors.department_id', $deptIds)
                ->exists();
            if ($hasAccess) return;
        }

        abort(403, 'Bu sənədə giriş icazəniz yoxdur.');
    }

    /**
     * Return the count of open (not yet approved-executed) legal acts per org tab.
     * Used by the JS tab bar to render a badge like "MN KTB (3)".
     *
     * "Open" means: the act is active AND the executor user's visible executors have
     * NOT yet received an approved "İcra olunub" status log for it.
     */
    public function orgCounts(Request $request): \Illuminate\Http\JsonResponse
    {
        $user       = auth()->user();
        $executorId = $user->executor_id;

        // Only admin may view as specific executor
        if ($user->isAdmin() && $request->filled('view_as_executor_id')) {
            $executorId = (int) $request->input('view_as_executor_id');
        }

        $visibleExecutorIds = $this->resolveVisibleExecutorIds($user, $executorId);

        // Build the set of org IDs this user can tab between (ancestors only, own dept excluded)
        $visibleOrgs = collect();
        if ($user->effectiveDeptId()) {
            $ownDeptId   = $user->effectiveDeptId();
            $ownDept     = Department::active()->find($ownDeptId);
            $ancestorIds = array_map('intval', $ownDept?->ancestorIds() ?? []);
            if (!empty($ancestorIds)) {
                $visibleOrgs = Department::active()
                    ->whereIn('id', $ancestorIds)
                    ->where('can_assign', true)
                    ->pluck('id');
            }
        }

        if ($visibleOrgs->isEmpty()) {
            return response()->json([]);
        }

        $icraOlunubIds = $this->getIcraOlunubNoteIds();

        $counts = [];
        foreach ($visibleOrgs as $orgId) {
            $query = LegalAct::active()->where('organization_id', $orgId);

            if (!empty($visibleExecutorIds)) {
                $query->whereHas('executors', fn($q) => $q->whereIn('executors.id', $visibleExecutorIds));
            } elseif (!$user->isAdmin()) {
                $counts[$orgId] = 0;
                continue;
            }

            // "Open" = no approved "icra olunub" status log exists for any of the visible executors
            if (!empty($visibleExecutorIds) && !empty($icraOlunubIds)) {
                // Count acts that do NOT have an approved icra-olunub log for ALL visible executors
                // Simpler definition: count acts where at least one visible executor is still open
                $approvedActIds = \App\Models\ExecutorStatusLog::whereIn('execution_note_id', $icraOlunubIds)
                    ->where('approval_status', 'approved')
                    ->whereHas('legalAct', fn($q) => $q->where('organization_id', $orgId)->active())
                    ->whereIn('user_id', function ($sub) use ($visibleExecutorIds) {
                        $sub->select('id')->from('users')->whereIn('executor_id', $visibleExecutorIds)->where('is_deleted', false);
                    })
                    ->pluck('legal_act_id')
                    ->unique()
                    ->toArray();

                if (!empty($approvedActIds)) {
                    $query->whereNotIn('id', $approvedActIds);
                }
            }

            $counts[$orgId] = $query->count();
        }

        return response()->json($counts);
    }
}
