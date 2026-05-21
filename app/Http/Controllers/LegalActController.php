<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\LegalAct;
use App\Models\LegalActChangelog;
use App\Models\ActType;
use App\Models\IssuingAuthority;
use App\Models\Executor;
use App\Models\ExecutionNote;
use App\Models\ExecutorStatusLog;
use App\Models\Department;
use Illuminate\Validation\Rule;
use App\Exports\LegalActsExport;
use App\Services\LegalActWordExportService;
use App\Services\TaskNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class LegalActController extends Controller
{
    public function index()
    {
        $user      = auth()->user();
        $canManage = $user->canManage();
        $canAssign = $user->canAssignTasks();
        $isAdmin   = $user->isAdmin();

        $actTypes            = ActType::active()->get();
        $issuingAuthorities  = IssuingAuthority::active()->get(); //kim qebul edib
        $executionNotes      = ExecutionNote::active()->get();
        $departments         = Department::active()->get();

        // Executors available for assignment — admin sees all; everyone else scoped to their dept tree
        if ($user->isAdmin()) {
            $executors = Executor::with('department')->active()->get();
        } elseif ($canAssign && ($assignDeptId = $user->canAssignDeptId())) {
            $deptIds   = Department::descendantIdsOf($assignDeptId);
            $executors = Executor::with('department')->active()->whereIn('department_id', $deptIds)->get();
        } else {
            $executors = collect();
        }

        $pendingApprovalCount = 0;
        if ($canManage) {
            // Subquery avoids loading IDs into PHP only to pass them back to SQL.
            $icraNoteIds = ExecutionNote::active()
                ->where(fn($q) => $q->where('note', 'like', '%İcra olunub%')->orWhere('note', 'like', '%icra olunub%'))
                ->select('id');
            $pendingQuery = ExecutorStatusLog::pending()->whereIn('execution_note_id', $icraNoteIds);
            if (!$user->isAdmin()) {
                $pendingQuery->whereHas('legalAct', fn($q) => $q->where('organization_id', $user->department_id)->where('is_deleted', false));
            }
            $pendingApprovalCount = $pendingQuery->count();
        }

        // Org-filter tabs: own dept + all ancestors filtered to can_assign=true.
        // The where('can_assign', true) clause naturally excludes a can_assign=false own dept,
        // so users without can_assign see only ancestor tabs (no own-dept tab).
        $visibleOrgs = collect();
        if ($user->isAdmin()) {
            $visibleOrgs = Department::active()->where('can_assign', true)->orderBy('name')->get();
        } elseif ($user->effectiveDeptId()) {
            $ownDeptId   = $user->effectiveDeptId();
            $ownDept     = Department::active()->find($ownDeptId);
            $ancestorIds = $ownDept?->ancestorIds() ?? [];
            $tabDeptIds  = array_merge([$ownDeptId], array_map('intval', $ancestorIds));
            // Build a depth map so we can sort root-first (depth 0) → own dept last (deepest).
            // ancestorIds() returns [immediate parent, grandparent, …, root], so reversing gives root first.
            $depthMap = [];
            foreach (array_reverse($ancestorIds) as $depth => $id) {
                $depthMap[$id] = $depth;          // root = 0, next level = 1, …
            }
            $depthMap[$ownDeptId] = count($ancestorIds); // own dept is always the deepest
            $visibleOrgs = Department::active()
                ->whereIn('id', $tabDeptIds)
                ->where('can_assign', true)
                ->get()
                ->sortBy(fn($dept) => $depthMap[$dept->id] ?? 999)
                ->values();
        }

        return view('legal_acts.index', compact(
            'actTypes',
            'issuingAuthorities',
            'executors',
            'executionNotes',
            'departments',
            'canManage',
            'canAssign',
            'isAdmin',
            'pendingApprovalCount',
            'visibleOrgs'
        ));
    }

    public function load(Request $request)
    {
        $draw  = $request->input('draw', 1);
        $start = $request->input('start', 0);
        $length = $request->input('length', 25);
        $user  = auth()->user();

        $totalQuery = LegalAct::active();
        $this->applyVisibilityScope($totalQuery, $user);
        if ($request->filled('col.organization_id')) {
            $totalQuery->where('organization_id', (int) $request->input('col.organization_id'));
        } else {
            // No tab selected: restrict total count to same default org set as the data query.
            $defaultOrgs = $this->defaultOrgIds();
            if ($defaultOrgs !== null) {
                $totalQuery->whereIn('organization_id', $defaultOrgs);
            }
        }
        $totalRecords = (clone $totalQuery)->count();

        $query = $this->applyFilters($request);
        $filteredRecords = (clone $query)->count();

        $orderCol = (int) $request->input('order.0.column', 3);
        $orderDir = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
        match ($orderCol) {
            0 => $query->orderBy('created_at', $orderDir),
            1 => $query->orderBy('legal_act_number', $orderDir),
            2 => $query->orderBy('legal_act_date', $orderDir),
            5 => $query->orderBy('task_number', $orderDir),
            9 => $query->orderBy('execution_deadline', $orderDir),
            11 => $query->orderBy('related_document_number', $orderDir),
            12 => $query->orderBy('related_document_date', $orderDir),
            default => $query->orderBy('id', 'desc'),
        };

        $results  = $query->skip($start)->take($length)->get();
        $userId   = auth()->id();
        $canManage = $user->canManage();
        $canAssign = $user->canAssignTasks();
        $isAdmin  = $user->isAdmin();

        // Pre-compute editable org IDs so canEdit is accurate per-row (mirrors edit() auth logic).
        // null = unrestricted (admin/manager with no can_assign ancestry).
        // array = only acts whose organization_id is in this set are editable.
        $editableOrgIds = null;
        if ($canManage && !$isAdmin) {
            $assignDeptId = $user->canAssignDeptId();
            $editableOrgIds = $assignDeptId ? Department::descendantIdsOf($assignDeptId) : [];
        }

        $data = [];
        foreach ($results as $i => $act) {
            $mainExecutors   = $act->executors->where('pivot.role', 'main')->values();
            $helperExecutors = $act->executors->where('pivot.role', 'helper')->values();

            $executorsToShow = [];
            foreach ($mainExecutors as $e) {
                $executorsToShow[] = ['executor' => $e, 'label' => 'Əsas'];
            }
            foreach ($helperExecutors as $e) {
                $executorsToShow[] = ['executor' => $e, 'label' => 'Digər'];
            }

            $executorHtml = '';
            foreach ($executorsToShow as $idx => $entry) {
                if ($idx > 0)
                    $executorHtml .= '<hr class="my-1" style="opacity:0.25">';
                $executorHtml .= '<div style="min-height:38px;display:flex;align-items:center;justify-content:center"><span><small class="text-muted fw-semibold">' . e($entry['label']) . ':</small> ' . e($entry['executor']->name) . '</span></div>';
            }
            $executorHtml = $executorHtml ?: '-';

            $departmentHtml = '';
            foreach ($executorsToShow as $idx => $entry) {
                if ($idx > 0)
                    $departmentHtml .= '<hr class="my-1" style="opacity:0.25">';
                $departmentHtml .= '<div style="min-height:38px;display:flex;align-items:center;justify-content:center"><span><small class="text-muted fw-semibold">' . e($entry['label']) . ':</small> ' . e($entry['executor']->department->name ?? '-') . '</span></div>';
            }
            $departmentHtml = $departmentHtml ?: '-';

            $executorLogMap = [];
            foreach ($act->statusLogs as $log) {
                if ($log->user && $log->user->executor_id) {
                    $exId = $log->user->executor_id;
                    $executorLogMap[$exId] = $log;
                }
            }

            $noteHtml    = '-';
            $allApproved = true;
            $anyPending  = false;
            $anyPartial  = false;
            $anyRejected = false;

            if (count($executorsToShow) > 0) {
                $noteHtml = '';
                foreach ($executorsToShow as $idx => $entry) {
                    $executor    = $entry['executor'];
                    $label       = $entry['label'];
                    $executorLog = $executorLogMap[$executor->id] ?? null;
                    $status      = $executorLog?->approval_status;
                    $logNote     = $executorLog?->executionNote?->note ?? '';

                    if ($status !== ExecutorStatusLog::APPROVAL_APPROVED)
                        $allApproved = false;
                    if ($status === ExecutorStatusLog::APPROVAL_PENDING)
                        $anyPending = true;
                    if ($status === ExecutorStatusLog::APPROVAL_PARTIAL)
                        $anyPartial = true;
                    if ($status === ExecutorStatusLog::APPROVAL_REJECTED)
                        $anyRejected = true;
                    if (!$executorLog)
                        $allApproved = false;

                    if ($idx > 0)
                        $noteHtml .= '<hr class="my-1" style="opacity:0.25">';
                    $noteHtml .= '<div style="min-height:38px;display:flex;align-items:center;justify-content:center"><span><small class="text-muted fw-semibold">' . e($label) . ': ' . e($executor->name) . '</small><br>';

                    if ($executorLog) {
                        $noteHtml .= match ($status) {
                            ExecutorStatusLog::APPROVAL_APPROVED => '<span class="badge bg-success">İcra olunub ✓</span>',
                            ExecutorStatusLog::APPROVAL_PENDING  => '<span class="badge bg-warning text-dark">Təsdiq gözləyir</span>',
                            ExecutorStatusLog::APPROVAL_REJECTED => '<span class="badge bg-danger">İmtina edilib</span>',
                            ExecutorStatusLog::APPROVAL_PARTIAL  => '<span class="badge bg-info text-dark">Natamam</span>',
                            default => '<span class="badge bg-secondary">' . e(Str::limit($logNote ?: 'İcradadır', 25)) . '</span>',
                        };
                    } else {
                        $noteHtml .= '<span class="badge bg-light text-dark border">Status yoxdur</span>';
                    }
                    $noteHtml .= '</span></div>';
                }
            }

            $rowClass = '';
            if ($allApproved && count($executorsToShow) > 0) {
                $rowClass = 'row-executed';
            } elseif ($anyPending) {
                $rowClass = 'row-pending';
            } elseif ($anyPartial) {
                $rowClass = 'row-partial';
            } elseif ($anyRejected) {
                $rowClass = 'row-overdue';
            } elseif ($act->execution_deadline) {
                $daysLeft = (int) now()->startOfDay()->diffInDays($act->execution_deadline->startOfDay(), false);
                $rowClass = $daysLeft < 0 ? 'row-overdue' : ($daysLeft <= 3 ? 'row-warning' : '');
            }

            $deadlineHtml = '-';
            if ($act->execution_deadline) {
                $deadlineHtml = $act->execution_deadline->format('d.m.Y');
                $dlDays = (int) now()->startOfDay()->diffInDays($act->execution_deadline->startOfDay(), false);
                if (!$allApproved && !$anyPending) {
                    if ($dlDays < 0)
                        $deadlineHtml .= '<br><span class="badge bg-danger text-white mt-1">İcra müddəti bitib</span>';
                    elseif ($dlDays <= 3)
                        $deadlineHtml .= '<br><span class="badge bg-warning text-dark mt-1">' . $dlDays . ' gün qalıb</span>';
                }
            }

            $pendingLogId = null;
            if ($anyPending) {
                foreach ($executorLogMap as $log) {
                    if ($log->approval_status === ExecutorStatusLog::APPROVAL_PENDING) {
                        $pendingLogId = $log->id;
                        break;
                    }
                }
            }

            $data[] = [
                'DT_RowClass'      => $rowClass,
                'id'               => $act->id,
                'rowNum'           => $start + $i + 1,
                'actType'          => $act->actType?->name ?? '-',
                'legalActNumber'   => $act->legal_act_number ?? '-',
                'legalActDate'     => $act->legal_act_date?->format('d.m.Y') ?? '-',
                'issuingAuthority' => $act->issuingAuthority?->name ?? '-',
                'summary'          => Str::limit($act->summary, 80) ?? '-',
                'taskNumber'       => $act->task_number ?? '-',
                'taskDescription'  => Str::limit($act->task_description, 60) ?: '-',
                'executor'         => $executorHtml,
                'department'       => $departmentHtml,
                'deadlineHtml'     => $deadlineHtml,
                'noteHtml'         => $noteHtml,
                'relatedDocNumber' => $act->related_document_number ?? '-',
                'relatedDocDate'   => $act->related_document_date?->format('d.m.Y') ?? '-',
                'organizationId'   => $act->organization_id,
                'organizationName' => $act->organization?->name ?? '-',
                'canEdit'          => $isAdmin
                    || ($canManage && $editableOrgIds === null)
                   || ($canManage && $editableOrgIds !== null && in_array((int) $act->organization_id, $editableOrgIds))
                   || (!$canManage && $userId === (int) $act->inserted_user_id),
              'canDelete'        => $isAdmin || ($canManage && $userId === (int) $act->inserted_user_id),
//                'canEdit' => $isAdmin
//                    || ($canManage && $editableOrgIds === null && (int) $act->organization_id === (int) $user->department_id)
//                    || ($canManage && $editableOrgIds !== null && in_array((int) $act->organization_id, $editableOrgIds))
//                    || (!$canManage && $userId === (int) $act->inserted_user_id),
                'hasPendingApproval' => $anyPending,
                'pendingLogId'     => $pendingLogId,
                'proofRequired'    => (bool) $act->proof_required,
            ];
        }

        return response()->json([
            'draw'            => (int) $draw,
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $data,
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        if (!$user->canCreateLegalActs()) {
            abort(403, 'Hüquqi akt yaratmaq icazəniz yoxdur.');
        }

        $validated = $request->validate([
            'act_type_id'         => 'required|exists:act_types,id',
            'issued_by_id'        => 'required|exists:issuing_authorities,id',
            'main_executor_ids'   => 'required|array|min:1',
            'main_executor_ids.*' => 'required|exists:executors,id',
            'helper_executor_ids'   => 'nullable|array',
            'helper_executor_ids.*' => 'required|exists:executors,id',
            'executor_tasks'      => 'nullable|array',
            'executor_tasks.*'    => 'nullable|string|max:5000',
            'legal_act_number'    => 'required|string|max:255',
            'legal_act_date'      => 'required|date_format:d.m.Y',
            'summary'             => 'required|string',
            'task_number'         => 'nullable|string|max:2000',
            'task_description'    => 'nullable|string',
            'execution_deadline'  => 'nullable|date_format:d.m.Y',
            'related_document_number' => 'nullable|string|max:255',
            'related_document_date'   => 'nullable|date_format:d.m.Y',
            'proof_required'      => 'nullable|boolean',
        ], $this->validationMessages());

        // Parse legal_act_date explicitly to avoid platform-specific ambiguity with d.m.Y format,
        // then normalise all date fields to Y-m-d so Eloquent's date cast stores them correctly.
        $legalActDateCarbon = Carbon::createFromFormat('d.m.Y', $validated['legal_act_date']);
        $year = $legalActDateCarbon->year;
        $validated['legal_act_date'] = $legalActDateCarbon->format('Y-m-d');
        if (!empty($validated['execution_deadline'])) {
            $validated['execution_deadline'] = Carbon::createFromFormat('d.m.Y', $validated['execution_deadline'])->format('Y-m-d');
        }
        if (!empty($validated['related_document_date'])) {
            $validated['related_document_date'] = Carbon::createFromFormat('d.m.Y', $validated['related_document_date'])->format('Y-m-d');
        }

        $exists = LegalAct::where('organization_id', $user->department_id)
            ->where('act_type_id', $validated['act_type_id'])
            ->where('legal_act_number', $validated['legal_act_number'])
            ->whereYear('legal_act_date', $year)
            ->where('is_deleted', false)
            ->exists();

        if ($exists) {
            return back()->withErrors(['legal_act_number' => 'Bu akt növü və il üzrə eyni nömrəli hüquqi akt artıq mövcuddur.'])->withInput();
        }

        $mainIds   = array_unique(array_map('intval', $validated['main_executor_ids']));
        $helperIds = array_unique(array_map('intval', $validated['helper_executor_ids'] ?? []));

        if (array_intersect($mainIds, $helperIds)) {
            return back()->withErrors(['main_executor_ids' => 'Eyni icraçı həm əsas həm də digər ola bilməz.'])->withInput();
        }

        // Prevent self-assignment: the logged-in user cannot assign themselves as an executor.
        if ($user->executor_id && in_array((int) $user->executor_id, array_merge($mainIds, $helperIds))) {
            return back()->withErrors(['main_executor_ids' => 'Siz özünüzü icraçı kimi təyin edə bilməzsiniz.'])->withInput();
        }

        if (!$user->isAdmin() && ($assignDeptId = $user->canAssignDeptId())) {
            $allowedDeptIds = Department::descendantIdsOf($assignDeptId);
            $forbidden = Executor::whereIn('id', array_merge($mainIds, $helperIds))
                ->whereNotIn('department_id', $allowedDeptIds)
                ->exists();
            if ($forbidden) {
                return back()->withErrors(['main_executor_ids' => 'Yalnız öz idarənizə və alt-idarələrə tapşırıq verə bilərsiniz.'])->withInput();
            }
        }

        $executorTasks = $validated['executor_tasks'] ?? [];
        $actData = collect($validated)->except(['main_executor_ids', 'helper_executor_ids', 'executor_tasks'])->toArray();
        $actData['inserted_user_id'] = auth()->id();
        $actData['organization_id']  = $user->department_id;
        $actData['proof_required']   = $request->boolean('proof_required') ? 1 : 0;

        $legalAct = LegalAct::create($actData);

        foreach ($mainIds as $id) {
            $legalAct->executors()->attach($id, [
                'role'             => 'main',
                'task_description' => $executorTasks[$id] ?? null,
            ]);
        }
        foreach ($helperIds as $id) {
            $legalAct->executors()->attach($id, [
                'role'             => 'helper',
                'task_description' => $executorTasks[$id] ?? null,
            ]);
        }

        // Notify department users about the new task assignment.
        try {
            (new TaskNotificationService())->notifyDepartmentUsers(
                $legalAct,
                array_merge($mainIds, $helperIds)
            );
        } catch (\Throwable $e) {
            Log::error('LegalActController: tapşırıq bildirişi göndərilmədi', ['xəta' => $e->getMessage()]);
        }

        $createDescription = sprintf(
            'Hüquqi akt yaradıldı: %s №%s (%s)',
            $legalAct->actType?->name ?? 'Naməlum növ',
            $legalAct->legal_act_number,
            $legalAct->legal_act_date?->format('d.m.Y') ?? '—'
        );
        ActivityLog::record(ActivityLog::ACTION_CREATE, $createDescription, 'LegalAct', $legalAct->id);

        return redirect()->route('legal-acts.index')->with('success', 'Hüquqi akt uğurla yaradıldı.');
    }

    public function show(LegalAct $legalAct)
    {
        $user = auth()->user();

        if ($user->canManage() || $user->isExecutor()) {
            $assignDeptId = $user->effectiveCanAssignDeptId();
            if ($assignDeptId) {
                // Anchor on effective dept — mirrors applyVisibilityScope logic exactly.
                $ownDeptId         = $user->effectiveDeptId() ?? $assignDeptId;
                $ownSubtreeDeptIds = Department::descendantIdsOf($ownDeptId);
                $orgInScope        = in_array((int) $legalAct->organization_id, $ownSubtreeDeptIds);

                if (!$orgInScope) {
                    // Allow tasks from direct ancestor orgs that have executors in this user's own subtree
                    $ancestorIds     = Department::find($ownDeptId)?->ancestorIds() ?? [];
                    $isAncestorOrg   = in_array((int) $legalAct->organization_id, $ancestorIds);
                    $hasExecutorHere = $legalAct->executors()->whereIn('executors.department_id', $ownSubtreeDeptIds)->exists();

                    if (!$isAncestorOrg || !$hasExecutorHere) {
                        // Executor-role users can always see acts they are personally assigned to
                        if (!$user->isExecutor() || !$user->executor_id ||
                            !$legalAct->executors()->where('executors.id', $user->executor_id)->exists()) {
                            abort(403);
                        }
                    }
                }
            }
            // No can_assign ancestry: managers may view all acts; executor falls through to personal check
            elseif ($user->isExecutor()) {
                if (!$this->userCanViewAct($legalAct, $user)) {
                    abort(403);
                }
            }
        } elseif (!$this->userCanViewAct($legalAct, $user)) {
            abort(403);
        }

        $legalAct->load([
            'actType',
            'issuingAuthority',
            'executors.department',
            'latestStatusLog.executionNote',
            'statusLogs' => fn($q) => $q->with(['executionNote', 'user', 'attachments', 'approvedByUser'])->reorder('created_at', 'asc'),
            'executors.users',
            'attachments.user',
        ]);

        $mainExecutors   = $legalAct->executors->where('pivot.role', 'main')->values();
        $helperExecutors = $legalAct->executors->where('pivot.role', 'helper')->values();

        // Determine which department IDs' executors may appear in the modal.
        // Rule: own dept + all descendants (downward only).
        //   Higher depts can see their subordinates' executor data.
        //   Lower depts never see ancestor or sibling executor data.
        // Admin: unrestricted (null = show all).
        $viewerTabDeptIds = null;
        if (!$user->isAdmin() && $user->effectiveDeptId()) {
            // descendantIdsOf includes self + all recursive children
            $viewerTabDeptIds = Department::descendantIdsOf($user->effectiveDeptId());
        }

        // Filter executor lists: only executors whose dept is in the viewer's subtree.
        // Always include the viewer's own executor record so executor-role users see themselves.
        if ($viewerTabDeptIds !== null) {
            $mainExecutors   = $mainExecutors->filter(fn($e) => in_array((int) $e->department_id, $viewerTabDeptIds) || $e->id === $user->executor_id)->values();
            $helperExecutors = $helperExecutors->filter(fn($e) => in_array((int) $e->department_id, $viewerTabDeptIds) || $e->id === $user->executor_id)->values();
        }

        // All remaining executors are in scope — expose their private task descriptions.
        $mapExecutor = function ($e) {
            return [
                'id'               => $e->id,
                'name'             => $e->name,
                'position'         => $e->position,
                'department'       => $e->department?->name,
                'task_description' => $e->pivot->task_description,
            ];
        };

        // Collect filtered executor IDs and scope status_logs to the same set.
        // Always include the viewer's own executor_id so executor-role users always see their own logs.
        $allowedExecutorIds = $mainExecutors->pluck('id')
            ->merge($helperExecutors->pluck('id'))
            ->merge($user->executor_id ? [$user->executor_id] : [])
            ->unique()
            ->toArray();

        $filteredLogs = $viewerTabDeptIds === null
            ? $legalAct->statusLogs
            : $legalAct->statusLogs->filter(
                fn($log) => $log->user?->executor_id !== null
                    && in_array($log->user->executor_id, $allowedExecutorIds)
            );

        return response()->json([
            'id'                  => $legalAct->id,
            'act_type'            => $legalAct->actType?->name,
            'legal_act_number'    => $legalAct->legal_act_number,
            'legal_act_date'      => $legalAct->legal_act_date?->format('d.m.Y'),
            'summary'             => $legalAct->summary,
            'issuing_authority'   => $legalAct->issuingAuthority?->name,
            'main_executors'      => $mainExecutors->map($mapExecutor),
            'helper_executors'    => $helperExecutors->map($mapExecutor),
            'task_number'         => $legalAct->task_number,
            'task_description'    => $legalAct->task_description,
            'execution_deadline'  => $legalAct->execution_deadline?->format('d.m.Y'),
            'related_document_number' => $legalAct->related_document_number,
            'related_document_date'   => $legalAct->related_document_date?->format('d.m.Y'),
            'proof_required'      => (bool) $legalAct->proof_required,
            'created_at'          => $legalAct->created_at?->format('d.m.Y H:i'),
            'attachments'         => $legalAct->attachments
                ->filter(fn($a) => is_null($a->status_log_id))
                ->values()
                ->map(fn($a) => [
                    'id'        => $a->id,
                    'name'      => $a->original_name,
                    'size'      => round($a->file_size / 1024, 1) . ' KB',
                    'mime_type' => $a->mime_type,
                ]),
            'status_logs'         => $filteredLogs->values()->map(fn($log) => [
                'user'            => $log->user?->full_name,
                'executor_id'     => $log->user?->executor_id,
                'note'            => $log->executionNote?->note,
                'custom_note'     => $log->custom_note,
                'date'            => $log->created_at?->format('d.m.Y H:i'),
                'approval_status' => $log->approval_status,
                'approval_note'   => $log->approval_note,
                'approved_by'     => $log->approvedByUser?->full_name,
                'approved_at'     => $log->approved_at?->format('d.m.Y H:i'),
                'attachments'     => $log->attachments->map(fn($a) => [
                    'id'        => $a->id,
                    'name'      => $a->original_name,
                    'mime_type' => $a->mime_type,
                ]),
            ]),
        ]);
    }

    public function edit(LegalAct $legalAct)
    {
        $user = auth()->user();

        if ($user->canManage()) {
            $assignDeptId = $user->canAssignDeptId();
            if ($assignDeptId) {
                $allowedOrgIds = Department::descendantIdsOf($assignDeptId);
                if (!in_array((int) $legalAct->organization_id, $allowedOrgIds)) {
                    abort(403);
                }
            } elseif (!$user->isAdmin()) {
                // Manager without can_assign ancestry can only edit acts they created
                if (auth()->id() !== $legalAct->inserted_user_id) {
                    abort(403);
                }
            }
        } elseif (auth()->id() !== $legalAct->inserted_user_id) {
            abort(403);
        }

        $legalAct->load('executors.department', 'attachments');

        // Build executor list filtered by what this user may assign — admin sees all; others scoped to dept tree
        if ($user->isAdmin()) {
            $executors = Executor::with('department')->active()->get();
        } elseif ($assignDeptId = $user->canAssignDeptId()) {
            $deptIds   = Department::descendantIdsOf($assignDeptId);
            $executors = Executor::with('department')->active()->whereIn('department_id', $deptIds)->get();
        } else {
            $executors = collect();
        }

        // Per-executor task descriptions currently saved on this act
        $executorTasks = [];
        foreach ($legalAct->executors as $e) {
            $executorTasks[$e->id] = $e->pivot->task_description;
        }

        return response()->json([
            'id'               => $legalAct->id,
            'act_type_id'      => $legalAct->act_type_id,
            'issued_by_id'     => $legalAct->issued_by_id,
            'main_executor_ids'   => $legalAct->executors->where('pivot.role', 'main')->pluck('id')->values(),
            'helper_executor_ids' => $legalAct->executors->where('pivot.role', 'helper')->pluck('id')->values(),
            'executor_tasks'   => $executorTasks,
            'legal_act_number' => $legalAct->legal_act_number,
            'legal_act_date'   => $legalAct->legal_act_date?->format('d.m.Y'),
            'summary'          => $legalAct->summary,
            'task_number'      => $legalAct->task_number,
            'task_description' => $legalAct->task_description,
            'execution_deadline'      => $legalAct->execution_deadline?->format('d.m.Y'),
            'related_document_number' => $legalAct->related_document_number,
            'related_document_date'   => $legalAct->related_document_date?->format('d.m.Y'),
            'proof_required'   => (bool) $legalAct->proof_required,
            'act_types'        => ActType::active()->get(),
            'authorities'      => IssuingAuthority::active()->get(),
            'executors'        => $executors->map(fn($e) => [
                'id'         => $e->id,
                'name'       => $e->name,
                'department' => $e->department ? ['id' => $e->department->id, 'name' => $e->department->name] : null,
            ]),
        ]);
    }

    public function update(Request $request, LegalAct $legalAct)
    {
        $user = auth()->user();

        if ($user->canManage()) {
            $assignDeptId = $user->canAssignDeptId();
            if ($assignDeptId) {
                $allowedOrgIds = Department::descendantIdsOf($assignDeptId);
                if (!in_array((int) $legalAct->organization_id, $allowedOrgIds)) {
                    abort(403, 'Sizin bu əməliyyat üçün icazəniz yoxdur.');
                }
            } elseif (!$user->isAdmin() && auth()->id() !== $legalAct->inserted_user_id) {
                abort(403, 'Sizin bu əməliyyat üçün icazəniz yoxdur.');
            }
        } elseif (auth()->id() !== $legalAct->inserted_user_id) {
            abort(403, 'Sizin bu əməliyyat üçün icazəniz yoxdur.');
        }

        $validated = $request->validate([
            'act_type_id'         => 'required|exists:act_types,id',
            'issued_by_id'        => 'required|exists:issuing_authorities,id',
            'main_executor_ids'   => 'required|array|min:1',
            'main_executor_ids.*' => 'required|exists:executors,id',
            'helper_executor_ids'   => 'nullable|array',
            'helper_executor_ids.*' => 'required|exists:executors,id',
            'executor_tasks'      => 'nullable|array',
            'executor_tasks.*'    => 'nullable|string|max:5000',
            'legal_act_number'    => 'required|string|max:255',
            'legal_act_date'      => 'required|date_format:d.m.Y',
            'summary'             => 'required|string',
            'task_number'         => 'nullable|string|max:2000',
            'task_description'    => 'nullable|string',
            'execution_deadline'  => 'nullable|date_format:d.m.Y',
            'related_document_number' => 'nullable|string|max:255',
            'related_document_date'   => 'nullable|date_format:d.m.Y',
            'proof_required'      => 'nullable|boolean',
        ], $this->validationMessages());

        // Parse legal_act_date explicitly to avoid platform-specific ambiguity with d.m.Y format,
        // then normalise all date fields to Y-m-d so Eloquent's date cast stores them correctly.
        $legalActDateCarbon = Carbon::createFromFormat('d.m.Y', $validated['legal_act_date']);
        $year = $legalActDateCarbon->year;
        $validated['legal_act_date'] = $legalActDateCarbon->format('Y-m-d');
        if (!empty($validated['execution_deadline'])) {
            $validated['execution_deadline'] = Carbon::createFromFormat('d.m.Y', $validated['execution_deadline'])->format('Y-m-d');
        }
        if (!empty($validated['related_document_date'])) {
            $validated['related_document_date'] = Carbon::createFromFormat('d.m.Y', $validated['related_document_date'])->format('Y-m-d');
        }

        $exists = LegalAct::where('organization_id', $legalAct->organization_id)
            ->where('act_type_id', $validated['act_type_id'])
            ->where('legal_act_number', $validated['legal_act_number'])
            ->whereYear('legal_act_date', $year)
            ->where('is_deleted', false)
            ->where('id', '!=', $legalAct->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['legal_act_number' => 'Bu akt növü və il üzrə eyni nömrəli hüquqi akt artıq mövcuddur.'])->withInput();
        }

        $mainIds   = array_unique(array_map('intval', $validated['main_executor_ids']));
        $helperIds = array_unique(array_map('intval', $validated['helper_executor_ids'] ?? []));

        if (array_intersect($mainIds, $helperIds)) {
            return back()->withErrors(['main_executor_ids' => 'Eyni icraçı həm əsas həm də digər ola bilməz.'])->withInput();
        }

        // Prevent self-assignment: the logged-in user cannot assign themselves as an executor.
        if ($user->executor_id && in_array((int) $user->executor_id, array_merge($mainIds, $helperIds))) {
            return back()->withErrors(['main_executor_ids' => 'Siz özünüzü icraçı kimi təyin edə bilməzsiniz.'])->withInput();
        }

        if (!$user->isAdmin() && ($assignDeptId = $user->canAssignDeptId())) {
            $allowedDeptIds = Department::descendantIdsOf($assignDeptId);
            $forbidden = Executor::whereIn('id', array_merge($mainIds, $helperIds))
                ->whereNotIn('department_id', $allowedDeptIds)
                ->exists();
            if ($forbidden) {
                return back()->withErrors(['main_executor_ids' => 'Yalnız öz idarənizə və alt-idarələrə tapşırıq verə bilərsiniz.'])->withInput();
            }
        }

        $executorTasks = $validated['executor_tasks'] ?? [];
        $actData = collect($validated)->except(['main_executor_ids', 'helper_executor_ids', 'executor_tasks'])->toArray();
        $actData['proof_required'] = $request->boolean('proof_required') ? 1 : 0;

        // ── Changelog: capture old values before update ─────────────────────────
        $this->recordChangelog($legalAct, $actData, $mainIds, $helperIds);

        $legalAct->update($actData);

        $legalAct->executors()->detach();
        foreach ($mainIds as $id) {
            $legalAct->executors()->attach($id, [
                'role'             => 'main',
                'task_description' => $executorTasks[$id] ?? null,
            ]);
        }
        foreach ($helperIds as $id) {
            $legalAct->executors()->attach($id, [
                'role'             => 'helper',
                'task_description' => $executorTasks[$id] ?? null,
            ]);
        }

        $updateDescription = sprintf(
            'Hüquqi akt yeniləndi: %s №%s (%s)',
            $legalAct->actType?->name ?? 'Naməlum növ',
            $legalAct->legal_act_number,
            $legalAct->legal_act_date?->format('d.m.Y') ?? '—'
        );
        ActivityLog::record(ActivityLog::ACTION_UPDATE, $updateDescription, 'LegalAct', $legalAct->id);

        return redirect()->route('legal-acts.index')->with('success', 'Hüquqi akt uğurla yeniləndi.');
    }

    public function destroy(LegalAct $legalAct)
    {
        $user = auth()->user();

        $canDelete = $user->isAdmin()
            || ($user->canManage() && (int) $legalAct->inserted_user_id === (int) $user->id);

        if (!$canDelete) {
            abort(403, 'Bu sənədi silmək icazəniz yoxdur.');
        }

        $description = sprintf(
            'Hüquqi akt silindi: %s №%s (%s)',
            $legalAct->actType?->name ?? 'Naməlum növ',
            $legalAct->legal_act_number,
            $legalAct->legal_act_date?->format('d.m.Y') ?? '—'
        );

        $legalAct->update(['is_deleted' => true]);

        ActivityLog::record(ActivityLog::ACTION_DELETE, $description, 'LegalAct', $legalAct->id);

        return redirect()->route('legal-acts.index')->with('success', 'Hüquqi akt uğurla silindi.');
    }

    /**
     * Compare old field values against incoming update data and persist changelog entries.
     */
    private function recordChangelog(LegalAct $legalAct, array $newData, array $newMainIds, array $newHelperIds): void
    {
        $fields = [
            'legal_act_number'        => 'Akt nömrəsi',
            'legal_act_date'          => 'Akt tarixi',
            'summary'                 => 'Xülasə',
            'task_number'             => 'Qeyd',
            'task_description'        => 'Tapşırıq',
            'execution_deadline'      => 'İcra müddəti',
            'related_document_number' => 'Əlaqəli sənəd №',
            'related_document_date'   => 'Əlaqəli sənəd tarixi',
            'proof_required'          => 'Sübut sənəd',
        ];

        $userId = auth()->id();

        // Scalar fields
        foreach ($fields as $key => $label) {
            $old = (string) ($legalAct->getAttribute($key) ?? '');
            $new = (string) ($newData[$key] ?? '');

            // Normalise boolean
            if ($key === 'proof_required') {
                $old = $legalAct->proof_required ? 'Bəli' : 'Xeyr';
                $new = ($newData[$key] ?? false) ? 'Bəli' : 'Xeyr';
            }

            if ($old !== $new) {
                LegalActChangelog::create([
                    'legal_act_id' => $legalAct->id,
                    'user_id'      => $userId,
                    'field_key'    => $key,
                    'field_label'  => $label,
                    'old_value'    => $old ?: null,
                    'new_value'    => $new ?: null,
                ]);
            }
        }

        // Act type
        if (isset($newData['act_type_id']) && (int) $newData['act_type_id'] !== (int) $legalAct->act_type_id) {
            LegalActChangelog::create([
                'legal_act_id' => $legalAct->id,
                'user_id'      => $userId,
                'field_key'    => 'act_type_id',
                'field_label'  => 'Akt növü',
                'old_value'    => $legalAct->actType?->name,
                'new_value'    => ActType::find($newData['act_type_id'])?->name,
            ]);
        }

        // Issuing authority
        if (isset($newData['issued_by_id']) && (int) $newData['issued_by_id'] !== (int) $legalAct->issued_by_id) {
            LegalActChangelog::create([
                'legal_act_id' => $legalAct->id,
                'user_id'      => $userId,
                'field_key'    => 'issued_by_id',
                'field_label'  => 'Verən orqan',
                'old_value'    => $legalAct->issuingAuthority?->name,
                'new_value'    => IssuingAuthority::find($newData['issued_by_id'])?->name,
            ]);
        }

        // Executors
        $legalAct->loadMissing('executors');
        $oldMainIds   = $legalAct->executors->where('pivot.role', 'main')->pluck('id')->sort()->values()->toArray();
        $oldHelperIds = $legalAct->executors->where('pivot.role', 'helper')->pluck('id')->sort()->values()->toArray();
        $sortedNewMain   = collect($newMainIds)->sort()->values()->toArray();
        $sortedNewHelper = collect($newHelperIds)->sort()->values()->toArray();

        if ($oldMainIds !== $sortedNewMain) {
            $oldNames = Executor::whereIn('id', $oldMainIds)->pluck('name')->join(', ');
            $newNames = Executor::whereIn('id', $sortedNewMain)->pluck('name')->join(', ');
            LegalActChangelog::create([
                'legal_act_id' => $legalAct->id,
                'user_id'      => $userId,
                'field_key'    => 'main_executors',
                'field_label'  => 'Əsas icraçılar',
                'old_value'    => $oldNames ?: null,
                'new_value'    => $newNames ?: null,
            ]);
        }

        if ($oldHelperIds !== $sortedNewHelper) {
            $oldNames = Executor::whereIn('id', $oldHelperIds)->pluck('name')->join(', ');
            $newNames = Executor::whereIn('id', $sortedNewHelper)->pluck('name')->join(', ');
            LegalActChangelog::create([
                'legal_act_id' => $legalAct->id,
                'user_id'      => $userId,
                'field_key'    => 'helper_executors',
                'field_label'  => 'Digər icraçılar',
                'old_value'    => $oldNames ?: null,
                'new_value'    => $newNames ?: null,
            ]);
        }
    }

    public function exportExcel(Request $request)
    {
        $query    = $this->applyFilters($request);
        $filename = 'legal_acts_' . now()->format('Y_m_d_His') . '.xls';
        return (new LegalActsExport($query))->download($filename);
    }

    public function exportWord(Request $request)
    {
        $query     = $this->applyFilters($request);
        $legalActs = $query->get();
        $filename  = 'legal_acts_' . now()->format('Y_m_d_His') . '.doc';
        $exportService = new LegalActWordExportService();
        $filePath  = $exportService->export($legalActs, $filename);
        return response()->download($filePath, $filename, ['Content-Type' => 'application/msword'])
            ->deleteFileAfterSend(true);
    }

    public function toggleProofRequired(LegalAct $legalAct)
    {
        $user = auth()->user();
        if (!$user->canManage())
            abort(403);

        $legalAct->update(['proof_required' => !$legalAct->proof_required]);

        return response()->json([
            'success'       => true,
            'proof_required' => (bool) $legalAct->proof_required,
            'message'       => $legalAct->proof_required
                ? 'Sübut sənəd məcburi edildi.'
                : 'Sübut sənəd məcburiliyi ləğv edildi.',
        ]);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Apply role-based visibility scope to a query.
     *
     * Rules:
     *  - can_assign users: see tasks their org created + tasks from ancestor orgs flowing into their subtree
     *  - pure managers (no can_assign): see tasks where their subtree has executors
     *  - pure executors: see only tasks they are personally assigned to
     */
    private function applyVisibilityScope($query, $user): void
    {
        if ($user->isAdmin()) return;

        $assignDeptId = $user->effectiveCanAssignDeptId();

        if ($assignDeptId) {
            // Anchor on the user's effective department (executor's dept for executor-role users).
            // This ensures siblings (same level under can_assign dept) are invisible to each other.
            $ownDeptId         = $user->effectiveDeptId() ?? $assignDeptId;
            $ownSubtreeDeptIds = Department::descendantIdsOf($ownDeptId);   // own dept + all children downward
            $ancestorIds       = Department::find($ownDeptId)?->ancestorIds() ?? [];  // parents up to root

            $query->where(function ($q) use ($user, $ownSubtreeDeptIds, $ancestorIds) {
                // Tasks created BY this user's own department or any of its subordinates
                $q->whereIn('organization_id', $ownSubtreeDeptIds);

                // Tasks created by an ancestor org that have at least one executor in this user's own subtree
                if (!empty($ancestorIds)) {
                    $q->orWhere(function ($sq) use ($ancestorIds, $ownSubtreeDeptIds) {
                        $sq->whereIn('organization_id', $ancestorIds)
                           ->whereHas('executors', fn($eq) => $eq->whereIn('executors.department_id', $ownSubtreeDeptIds));
                    });
                }

                // Tasks personally assigned to this user (regardless of creating org)
                if ($user->executor_id) {
                    $q->orWhereHas('executors', fn($sq) => $sq->where('executors.id', $user->executor_id));
                }
            });
            return;
        }

        if ($user->canManage() && $user->effectiveDeptId()) {
            // Manager without a can_assign ancestry: see tasks where subtree executors are assigned
            $deptIds = Department::descendantIdsOf($user->effectiveDeptId());
            $query->whereHas('executors', fn($sq) => $sq->whereIn('executors.department_id', $deptIds));
            return;
        }

        // Pure executor with no can_assign ancestry: personally assigned tasks only
        if ($user->executor_id) {
            $query->whereHas('executors', fn($sq) => $sq->where('executors.id', $user->executor_id));
        } else {
            $query->whereRaw('1 = 0');
        }
    }

    /**
     * Check whether a non-manager user may view a specific legal act.
     * Mirrors applyVisibilityScope rules for single-record checks.
     */
    private function userCanViewAct(LegalAct $legalAct, $user): bool
    {
        $assignDeptId = $user->effectiveCanAssignDeptId();

        if ($assignDeptId) {
            // Use effective dept as anchor — consistent with applyVisibilityScope.
            $ownDeptId         = $user->effectiveDeptId() ?? $assignDeptId;
            $ownSubtreeDeptIds = Department::descendantIdsOf($ownDeptId);

            // Act was created by own dept or one of its subordinates
            if (in_array((int) $legalAct->organization_id, $ownSubtreeDeptIds)) return true;

            // Act was created by an ancestor org and has an executor in this user's subtree
            $ancestorIds = Department::find($ownDeptId)?->ancestorIds() ?? [];
            if (in_array((int) $legalAct->organization_id, $ancestorIds) &&
                $legalAct->executors()->whereIn('executors.department_id', $ownSubtreeDeptIds)->exists()) {
                return true;
            }

            // Personal executor assignment (fallback)
            if ($user->executor_id && $legalAct->executors()->where('executors.id', $user->executor_id)->exists()) {
                return true;
            }

            return false;
        }

        if ($user->executor_id) {
            return $legalAct->executors()->where('executors.id', $user->executor_id)->exists();
        }

        return false;
    }

    /**
     * Return the organisation IDs shown on initial load (no tab selected).
     * - can_assign=true dept  → own dept only.
     * - can_assign=false dept → root dept (topmost ancestor, parent_id=NULL) of their hierarchy.
     * - Admin → null (unrestricted).
     */
    private function defaultOrgIds(): ?array
    {
        $user = auth()->user();
        if ($user->isAdmin()) return null;
        if (!$user->effectiveDeptId()) return [];

        $ownDeptId   = $user->effectiveDeptId();
        $ownDept     = Department::find($ownDeptId);
        $ancestorIds = $ownDept?->ancestorIds() ?? [];

        // Always default to the root-most (topmost ancestor) visible tab so the initial
        // server-side load matches what is visually highlighted in the UI (first tab = root).
        // ancestorIds = [immediate parent, …, root] — last element is the topmost ancestor.
        if (!empty($ancestorIds)) {
            // Find the root-most ancestor that has can_assign=true (appears in the tabs).
            $canAssignIds = Department::whereIn('id', $ancestorIds)
                ->where('can_assign', true)
                ->pluck('id')
                ->toArray();

            // Walk from root (end of ancestorIds) to find the topmost can_assign dept.
            foreach (array_reverse($ancestorIds) as $ancId) {
                if (in_array((int) $ancId, $canAssignIds)) {
                    return [(int) $ancId];
                }
            }
        }

        // No can_assign ancestor exists: own dept is the root of its hierarchy.
        // If own dept itself is can_assign=true it is the first (and only) tab.
        // If can_assign=false it has no tabs — fall back to own dept for visibility.
        return [$ownDeptId];
    }

    private function applyFilters(Request $request)
    {
        // Only load relations actually consumed by load() row rendering.
        // - executors.users was never read after load (dropped)
        // - latestStatusLog.approvedByUser was loaded but only latestStatusLog.executionNote
        //   is used for the status badge; approvedByUser is fetched in show() instead.
        $query = LegalAct::with([
            'actType',
            'issuingAuthority',
            'executors.department',
            'latestStatusLog.executionNote',
            'statusLogs' => fn($q) => $q->with('executionNote', 'user')->reorder('created_at', 'asc'),
            'organization',
        ])->active();

        $this->applyVisibilityScope($query, auth()->user());

        // When no org tab is selected, restrict to own dept + ancestors by default.
        // This prevents the initial load from showing records from all accessible orgs.
        if (!$request->filled('col.organization_id')) {
            $defaultOrgs = $this->defaultOrgIds();
            if ($defaultOrgs !== null) {
                $query->whereIn('organization_id', $defaultOrgs);
            }
        }

        if ($request->filled('col.legal_act_number')) {
            foreach (preg_split('/\s+/', trim($request->input('col.legal_act_number'))) as $t) {
                $query->where('legal_act_number', 'like', '%' . $t . '%');
            }
        }
        if ($request->filled('col.summary')) {
            foreach (preg_split('/\s+/', trim($request->input('col.summary'))) as $t) {
                $query->where('summary', 'like', '%' . $t . '%');
            }
        }
        if ($request->filled('col.act_type_id'))
            $query->where('act_type_id', $request->input('col.act_type_id'));
        if ($request->filled('col.issued_by_id'))
            $query->where('issued_by_id', $request->input('col.issued_by_id'));
        if ($request->filled('col.executor_id')) {
            $query->whereHas('executors', fn($q) => $q->where('executors.id', $request->input('col.executor_id')));
        }
        if ($request->filled('col.legal_act_date_from'))
            $query->where('legal_act_date', '>=', $request->input('col.legal_act_date_from'));
        if ($request->filled('col.legal_act_date_to'))
            $query->where('legal_act_date', '<=', $request->input('col.legal_act_date_to'));
        if ($request->filled('col.deadline_from'))
            $query->where('execution_deadline', '>=', $request->input('col.deadline_from'));
        if ($request->filled('col.deadline_to'))
            $query->where('execution_deadline', '<=', $request->input('col.deadline_to'));
        if ($request->filled('col.task_number')) {
            foreach (preg_split('/\s+/', trim($request->input('col.task_number'))) as $t) {
                $query->where('task_number', 'like', '%' . $t . '%');
            }
        }
        if ($request->filled('col.department_id')) {
            $query->whereHas('executors', fn($q) => $q->where('department_id', $request->input('col.department_id')));
        }
        if ($request->filled('col.organization_id')) {
            $query->where('organization_id', (int) $request->input('col.organization_id'));
        }
        if ($request->filled('col.deadline_status')) {
            $status      = $request->input('col.deadline_status');
            $today       = now()->startOfDay();
            $icraNote    = fn($nq) => $nq->where('note', 'like', '%İcra olunub%')->orWhere('note', 'like', '%icra olunub%');
            $notExecuted = fn($q) => $q->whereDoesntHave('statusLogs')
                ->orWhereDoesntHave('latestStatusLog', fn($sq) => $sq
                    ->where('approval_status', ExecutorStatusLog::APPROVAL_APPROVED)
                    ->whereHas('executionNote', $icraNote));

            if ($status === 'overdue') {
                // Deadline has already passed and not yet executed
                $query->whereNotNull('execution_deadline')
                    ->whereDate('execution_deadline', '<', $today)
                    ->where($notExecuted);
            } elseif ($status === 'due_soon') {
                // 0, 1, or 2 days left (today through today+2) and not yet executed
                $query->whereNotNull('execution_deadline')
                    ->whereDate('execution_deadline', '>=', $today)
                    ->whereDate('execution_deadline', '<=', $today->copy()->addDays(2))
                    ->where($notExecuted);
            } elseif ($status === 'executed') {
                $query->whereHas('latestStatusLog', fn($q) => $q
                    ->where('approval_status', ExecutorStatusLog::APPROVAL_APPROVED)
                    ->whereHas('executionNote', $icraNote));
            }
        }
        if ($request->filled('col.execution_note_id')) {
            $query->whereHas('latestStatusLog', fn($q) => $q->where('execution_note_id', $request->input('col.execution_note_id')));
        }

        return $query;
    }

    private function validationMessages(): array
    {
        return [
            'act_type_id.required'        => 'Akt növü mütləq seçilməlidir.',
            'act_type_id.exists'          => 'Seçilmiş akt növü mövcud deyil.',
            'issued_by_id.required'       => 'Verən orqan mütləq seçilməlidir.',
            'issued_by_id.exists'         => 'Seçilmiş verən orqan mövcud deyil.',
            'main_executor_ids.required'  => 'Ən azı bir əsas icraçı seçilməlidir.',
            'main_executor_ids.min'       => 'Ən azı bir əsas icraçı seçilməlidir.',
            'main_executor_ids.*.exists'  => 'Seçilmiş əsas icraçı mövcud deyil.',
            'helper_executor_ids.*.exists' => 'Seçilmiş digər icraçı mövcud deyil.',
            'legal_act_number.required'   => 'Hüquqi aktın nömrəsi mütləq daxil edilməlidir.',
            'legal_act_number.max'        => 'Hüquqi aktın nömrəsi 255 simvoldan çox ola bilməz.',
            'legal_act_date.required'         => 'Hüquqi aktın tarixi mütləq daxil edilməlidir.',
            'legal_act_date.date_format'      => 'Hüquqi aktın tarixi gün.ay.il (məs: 01.05.2026) formatında olmalıdır.',
            'summary.required'                => 'Xülasə mütləq daxil edilməlidir.',
            'execution_deadline.date_format'  => 'İcra müddəti gün.ay.il (məs: 01.05.2026) formatında olmalıdır.',
            'related_document_number.max'     => 'Əlaqəli sənədin nömrəsi 255 simvoldan çox ola bilməz.',
            'related_document_date.date_format' => 'Əlaqəli sənədin tarixi gün.ay.il (məs: 01.05.2026) formatında olmalıdır.',
        ];
    }
}
