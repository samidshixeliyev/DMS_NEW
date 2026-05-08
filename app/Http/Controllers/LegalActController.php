<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\LegalAct;
use App\Models\ActType;
use App\Models\IssuingAuthority;
use App\Models\Executor;
use App\Models\ExecutionNote;
use App\Models\ExecutorStatusLog;
use App\Models\Department;
use Illuminate\Validation\Rule;
use App\Exports\LegalActsExport;
use App\Services\LegalActWordExportService;
use Illuminate\Http\Request;
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
            $icraIds = ExecutionNote::active()->where(fn($q) => $q->where('note', 'like', '%İcra olunub%')->orWhere('note', 'like', '%icra olunub%'))->pluck('id')->toArray();
            if (count($icraIds) > 0) {
                $pendingQuery = ExecutorStatusLog::pending()->whereIn('execution_note_id', $icraIds);
                if (!$user->isAdmin()) {
                    $pendingQuery->whereHas('legalAct', fn($q) => $q->where('organization_id', $user->department_id)->where('is_deleted', false));
                }
                $pendingApprovalCount = $pendingQuery->count();
            }
        }

        // Org-filter tabs: own dept + all ancestors (upward chain only).
        // Children and siblings are never shown as tabs — higher depts do not browse subordinates.
        $visibleOrgs = collect();
        if ($user->isAdmin()) {
            $visibleOrgs = Department::active()->where('can_assign', true)->orderBy('name')->get();
        } elseif ($user->department_id) {
            $ownDeptId   = (int) $user->department_id;
            $ancestorIds = Department::find($ownDeptId)?->ancestorIds() ?? [];
            $tabDeptIds  = array_merge([$ownDeptId], array_map('intval', $ancestorIds));
            $visibleOrgs = Department::active()
                ->whereIn('id', $tabDeptIds)
                ->where('can_assign', true)
                ->orderBy('name')
                ->get();
            // Always include own dept even if can_assign=false
            if ($visibleOrgs->doesntContain('id', $ownDeptId)) {
                $own = Department::active()->find($ownDeptId);
                if ($own) $visibleOrgs = $visibleOrgs->prepend($own);
            }
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
            4 => $query->orderBy('inserted_user_id', $orderDir),
            6 => $query->orderBy('task_number', $orderDir),
            10 => $query->orderBy('execution_deadline', $orderDir),
            12 => $query->orderBy('related_document_number', $orderDir),
            13 => $query->orderBy('related_document_date', $orderDir),
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
                'insertedUser'     => $act->insertedUser?->executor
                    ? e($act->insertedUser->executor->name) . ($act->insertedUser->executor->position ? '<br><small class="text-muted">' . e($act->insertedUser->executor->position) . '</small>' : '')
                    : '-',
                'organizationId'   => $act->organization_id,
                'organizationName' => $act->organization?->name ?? '-',
                'canEdit'          => $isAdmin
                    || ($canManage && $editableOrgIds === null)
                    || ($canManage && $editableOrgIds !== null && in_array((int) $act->organization_id, $editableOrgIds))
                    || (!$canManage && $userId === (int) $act->inserted_user_id),
                'canDelete'        => $isAdmin || ($canManage && $userId === (int) $act->inserted_user_id),
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
            'task_number'         => 'nullable|string|max:255',
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

        return redirect()->route('legal-acts.index')->with('success', 'Hüquqi akt uğurla yaradıldı.');
    }

    public function show(LegalAct $legalAct)
    {
        $user = auth()->user();

        if ($user->canManage()) {
            $assignDeptId = $user->canAssignDeptId();
            if ($assignDeptId) {
                // Anchor on user's own dept — mirrors applyVisibilityScope logic exactly.
                $ownDeptId         = $user->department_id ?? $assignDeptId;
                $ownSubtreeDeptIds = Department::descendantIdsOf($ownDeptId);
                $orgInScope        = in_array((int) $legalAct->organization_id, $ownSubtreeDeptIds);

                if (!$orgInScope) {
                    // Allow tasks from direct ancestor orgs that have executors in this user's own subtree
                    $ancestorIds     = Department::find($ownDeptId)?->ancestorIds() ?? [];
                    $isAncestorOrg   = in_array((int) $legalAct->organization_id, $ancestorIds);
                    $hasExecutorHere = $legalAct->executors()->whereIn('executors.department_id', $ownSubtreeDeptIds)->exists();

                    if (!$isAncestorOrg || !$hasExecutorHere) {
                        abort(403);
                    }
                }
            }
            // Manager without any can_assign ancestry: may view all acts
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
            'insertedUser',
        ]);

        $mainExecutors   = $legalAct->executors->where('pivot.role', 'main')->values();
        $helperExecutors = $legalAct->executors->where('pivot.role', 'helper')->values();

        // Determine which department IDs' executors may appear as tabs in the modal.
        // Rule: own dept + all ancestors (upward chain only).
        //   - Root dept viewer → ancestors = [], so only own-dept executors show → single group → no tab nav.
        //   - Level-N viewer → own dept + every parent up to root → never siblings, never children.
        // Admin: unrestricted (null = show all).
        $viewerTabDeptIds = null;
        if (!$user->isAdmin() && $user->department_id) {
            $ownDeptId        = (int) $user->department_id;
            $ancestorIds      = Department::find($ownDeptId)?->ancestorIds() ?? [];
            $viewerTabDeptIds = array_merge([$ownDeptId], array_map('intval', $ancestorIds));
        }

        // Filter executor lists to own dept + ancestors only.
        // Siblings and child departments are intentionally excluded.
        if ($viewerTabDeptIds !== null) {
            $mainExecutors   = $mainExecutors->filter(fn($e) => in_array((int) $e->department_id, $viewerTabDeptIds))->values();
            $helperExecutors = $helperExecutors->filter(fn($e) => in_array((int) $e->department_id, $viewerTabDeptIds))->values();
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
        $allowedExecutorIds = $mainExecutors->pluck('id')
            ->merge($helperExecutors->pluck('id'))
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
            'inserted_user'       => $legalAct->insertedUser
                ? $legalAct->insertedUser->name . ' ' . $legalAct->insertedUser->surname : null,
            'created_at'          => $legalAct->created_at?->format('d.m.Y H:i'),
            'status_logs'         => $filteredLogs->map(fn($log) => [
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
            }
        } elseif (auth()->id() !== $legalAct->inserted_user_id) {
            abort(403);
        }

        $legalAct->load('executors.department');

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

        if (!$user->canManage() && auth()->id() !== $legalAct->inserted_user_id) {
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
            'task_number'         => 'nullable|string|max:255',
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

        $assignDeptId = $user->canAssignDeptId();

        if ($assignDeptId) {
            // Anchor on the user's OWN department — never on the can_assign ancestor.
            // This ensures siblings (same level under can_assign dept) are invisible to each other.
            $ownDeptId         = $user->department_id ?? $assignDeptId;
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

        if ($user->canManage() && $user->department_id) {
            // Manager without a can_assign ancestry: see tasks where subtree executors are assigned
            $deptIds = Department::descendantIdsOf($user->department_id);
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
        $assignDeptId = $user->canAssignDeptId();

        if ($assignDeptId) {
            // Use user's own dept as anchor — consistent with applyVisibilityScope.
            $ownDeptId         = $user->department_id ?? $assignDeptId;
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

    private function applyFilters(Request $request)
    {
        $query = LegalAct::with([
            'actType',
            'issuingAuthority',
            'executors.department',
            'latestStatusLog.executionNote',
            'latestStatusLog.approvedByUser',
            'statusLogs' => fn($q) => $q->with('executionNote', 'user')->reorder('created_at', 'asc'),
            'executors.users',
            'insertedUser.executor',
            'organization',
        ])->active();

        $this->applyVisibilityScope($query, auth()->user());

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

            if ($status === 'last1day') {
                $query->whereNotNull('execution_deadline')
                    ->whereDate('execution_deadline', '>=', $today)
                    ->whereDate('execution_deadline', '<=', $today->copy()->addDays(1))
                    ->where($notExecuted);
            } elseif ($status === 'last2days') {
                $query->whereNotNull('execution_deadline')
                    ->whereDate('execution_deadline', '>=', $today)
                    ->whereDate('execution_deadline', '<=', $today->copy()->addDays(2))
                    ->where($notExecuted);
            } elseif ($status === 'last3days') {
                $query->whereNotNull('execution_deadline')
                    ->whereDate('execution_deadline', '>=', $today)
                    ->whereDate('execution_deadline', '<=', $today->copy()->addDays(3))
                    ->where($notExecuted);
            } elseif ($status === 'expired3days') {
                $query->whereNotNull('execution_deadline')
                    ->whereDate('execution_deadline', '>=', $today->copy()->subDays(3))
                    ->whereDate('execution_deadline', '<', $today)
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
