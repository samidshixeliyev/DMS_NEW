<?php

namespace App\Http\Controllers;

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
        $actTypes = ActType::active()->get();
        $issuingAuthorities = IssuingAuthority::active()->get();
        $executors = Executor::with('department')->active()->get();
        $executionNotes = ExecutionNote::active()->get();
        $departments = Department::active()->get();
        $canManage = auth()->user()->canManage();
        $isAdmin = auth()->user()->isAdmin();

        $pendingApprovalCount = 0;
        if ($canManage) {
            $icraIds = \App\Models\ExecutionNote::all()
                ->filter(fn($n) => mb_stripos($n->note, 'İcra olunub') !== false)
                ->pluck('id')->toArray();
            $pendingApprovalCount = count($icraIds) > 0
                ? ExecutorStatusLog::pending()->whereIn('execution_note_id', $icraIds)->count()
                : 0;
        }

        return view('legal_acts.index', compact(
            'actTypes',
            'issuingAuthorities',
            'executors',
            'executionNotes',
            'departments',
            'canManage',
            'isAdmin',
            'pendingApprovalCount'
        ));
    }

    public function load(Request $request)
    {
        $draw = $request->input('draw', 1);
        $start = $request->input('start', 0);
        $length = $request->input('length', 25);
        $user = auth()->user();

        $totalQuery = LegalAct::active();
        if (!$user->canManage()) {
            $totalQuery->where(function ($q) use ($user) {
                if ($user->executor_id) {
                    $q->whereHas('executors', fn($sq) => $sq->where('executors.id', $user->executor_id));
                }
                if ($user->department_id) {
                    $q->orWhereHas('executors', fn($sq) => $sq->where('executors.department_id', $user->department_id));
                }
            });
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

        $results = $query->skip($start)->take($length)->get();

        $userId = auth()->id();
        $canManage = $user->canManage();
        $isAdmin = $user->isAdmin();

        $data = [];
        foreach ($results as $i => $act) {
            $mainExecutors = $act->executors->where('pivot.role', 'main')->values();
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

            $noteHtml = '-';
            $allApproved = true;
            $anyPending = false;
            $anyPartial = false;
            $anyRejected = false;

            if (count($executorsToShow) > 0) {
                $noteHtml = '';
                foreach ($executorsToShow as $idx => $entry) {
                    $executor = $entry['executor'];
                    $label = $entry['label'];
                    $executorLog = $executorLogMap[$executor->id] ?? null;
                    $status = $executorLog?->approval_status;
                    $logNote = $executorLog?->executionNote?->note ?? '';

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
                            ExecutorStatusLog::APPROVAL_PENDING => '<span class="badge bg-warning text-dark">Təsdiq gözləyir</span>',
                            ExecutorStatusLog::APPROVAL_REJECTED => '<span class="badge bg-danger">Rədd edilib</span>',
                            ExecutorStatusLog::APPROVAL_PARTIAL => '<span class="badge bg-info text-dark">Natamam</span>',
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
                'DT_RowClass' => $rowClass,
                'id' => $act->id,
                'rowNum' => $start + $i + 1,
                'actType' => $act->actType?->name ?? '-',
                'legalActNumber' => $act->legal_act_number ?? '-',
                'legalActDate' => $act->legal_act_date?->format('d.m.Y') ?? '-',
                'issuingAuthority' => $act->issuingAuthority?->name ?? '-',
                'summary' => Str::limit($act->summary, 80) ?? '-',
                'taskNumber' => $act->task_number ?? '-',
                'taskDescription' => Str::limit($act->task_description, 60) ?: '-',
                'executor' => $executorHtml,
                'department' => $departmentHtml,
                'deadlineHtml' => $deadlineHtml,
                'noteHtml' => $noteHtml,
                'relatedDocNumber' => $act->related_document_number ?? '-',
                'relatedDocDate' => $act->related_document_date?->format('d.m.Y') ?? '-',
                'insertedUser' => $act->insertedUser ? $act->insertedUser->name . ' ' . $act->insertedUser->surname : '-',
                'canEdit' => ($userId === $act->inserted_user_id) || $canManage,
                'canDelete' => $isAdmin,
                'hasPendingApproval' => $anyPending,
                'pendingLogId' => $pendingLogId,
                'proofRequired' => (bool) $act->proof_required,
            ];
        }

        return response()->json([
            'draw' => (int) $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'act_type_id' => 'required|exists:act_types,id',
            'issued_by_id' => 'required|exists:issuing_authorities,id',
            'main_executor_ids' => 'required|array|min:1',
            'main_executor_ids.*' => 'required|exists:executors,id',
            'helper_executor_ids' => 'nullable|array',
            'helper_executor_ids.*' => 'required|exists:executors,id',
            'legal_act_number' => 'required|string|max:255',
            'legal_act_date' => 'required|date',
            'summary' => 'required|string',
            'task_number' => 'nullable|string|max:255',
            'task_description' => 'nullable|string',
            'execution_deadline' => 'nullable|date',
            'related_document_number' => 'nullable|string|max:255',
            'related_document_date' => 'nullable|date',
            'proof_required' => 'nullable|boolean',
        ], $this->validationMessages());

        $year = Carbon::parse($validated['legal_act_date'])->year;
        $exists = LegalAct::where('act_type_id', $validated['act_type_id'])
            ->where('legal_act_number', $validated['legal_act_number'])
            ->whereYear('legal_act_date', $year)
            ->where('is_deleted', false)
            ->exists();

        if ($exists) {
            return back()->withErrors(['legal_act_number' => 'Bu akt növü və il üzrə eyni nömrəli hüquqi akt artıq mövcuddur.'])->withInput();
        }

        $mainIds = array_unique(array_map('intval', $validated['main_executor_ids']));
        $helperIds = array_unique(array_map('intval', $validated['helper_executor_ids'] ?? []));

        if (array_intersect($mainIds, $helperIds)) {
            return back()->withErrors(['main_executor_ids' => 'Eyni icraçı həm əsas həm də digər ola bilməz.'])->withInput();
        }

        $actData = collect($validated)->except(['main_executor_ids', 'helper_executor_ids'])->toArray();
        $actData['inserted_user_id'] = auth()->id();
        $actData['proof_required'] = $request->boolean('proof_required') ? 1 : 0;

        $legalAct = LegalAct::create($actData);

        foreach ($mainIds as $id) {
            $legalAct->executors()->attach($id, ['role' => 'main']);
        }
        foreach ($helperIds as $id) {
            $legalAct->executors()->attach($id, ['role' => 'helper']);
        }

        return redirect()->route('legal-acts.index')->with('success', 'Hüquqi akt uğurla yaradıldı.');
    }

    public function show(LegalAct $legalAct)
    {
        $user = auth()->user();
        if ($user->role === 'executor') {
            if (!$legalAct->executors()->where('executor_id', $user->executor_id)->exists())
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

        $mainExecutors = $legalAct->executors->where('pivot.role', 'main')->values();
        $helperExecutors = $legalAct->executors->where('pivot.role', 'helper')->values();

        return response()->json([
            'id' => $legalAct->id,
            'act_type' => $legalAct->actType?->name,
            'legal_act_number' => $legalAct->legal_act_number,
            'legal_act_date' => $legalAct->legal_act_date?->format('d.m.Y'),
            'summary' => $legalAct->summary,
            'issuing_authority' => $legalAct->issuingAuthority?->name,
            'main_executors' => $mainExecutors->map(fn($e) => [
                'id' => $e->id,
                'name' => $e->name,
                'position' => $e->position,
                'department' => $e->department?->name,
            ]),
            'helper_executors' => $helperExecutors->map(fn($e) => [
                'id' => $e->id,
                'name' => $e->name,
                'position' => $e->position,
                'department' => $e->department?->name,
            ]),
            'task_number' => $legalAct->task_number,
            'task_description' => $legalAct->task_description,
            'execution_deadline' => $legalAct->execution_deadline?->format('d.m.Y'),
            'related_document_number' => $legalAct->related_document_number,
            'related_document_date' => $legalAct->related_document_date?->format('d.m.Y'),
            'proof_required' => (bool) $legalAct->proof_required,
            'inserted_user' => $legalAct->insertedUser
                ? $legalAct->insertedUser->name . ' ' . $legalAct->insertedUser->surname : null,
            'created_at' => $legalAct->created_at?->format('d.m.Y H:i'),
            'status_logs' => $legalAct->statusLogs->map(fn($log) => [
                'user' => $log->user?->full_name,
                'executor_id' => $log->user?->executor_id,
                'note' => $log->executionNote?->note,
                'custom_note' => $log->custom_note,
                'date' => $log->created_at?->format('d.m.Y H:i'),
                'approval_status' => $log->approval_status,
                'approval_note' => $log->approval_note,
                'approved_by' => $log->approvedByUser?->full_name,
                'approved_at' => $log->approved_at?->format('d.m.Y H:i'),
                'attachments' => $log->attachments->map(fn($a) => [
                    'id' => $a->id,
                    'name' => $a->original_name,
                    'mime_type' => $a->mime_type,
                ]),
            ]),
        ]);
    }

    public function edit(LegalAct $legalAct)
    {
        $user = auth()->user();
        if ($user->role === 'executor') {
            if (!$legalAct->executors()->where('executor_id', $user->executor_id)->exists())
                abort(403);
        }

        $legalAct->load('executors');

        return response()->json([
            'id' => $legalAct->id,
            'act_type_id' => $legalAct->act_type_id,
            'issued_by_id' => $legalAct->issued_by_id,
            'main_executor_ids' => $legalAct->executors->where('pivot.role', 'main')->pluck('id')->values(),
            'helper_executor_ids' => $legalAct->executors->where('pivot.role', 'helper')->pluck('id')->values(),
            'legal_act_number' => $legalAct->legal_act_number,
            'legal_act_date' => $legalAct->legal_act_date?->format('Y-m-d'),
            'summary' => $legalAct->summary,
            'task_number' => $legalAct->task_number,
            'task_description' => $legalAct->task_description,
            'execution_deadline' => $legalAct->execution_deadline?->format('Y-m-d'),
            'related_document_number' => $legalAct->related_document_number,
            'related_document_date' => $legalAct->related_document_date?->format('Y-m-d'),
            'proof_required' => (bool) $legalAct->proof_required,
            'act_types' => ActType::active()->get(),
            'authorities' => IssuingAuthority::active()->get(),
            'executors' => Executor::with('department')->active()->get(),
        ]);
    }

    public function update(Request $request, LegalAct $legalAct)
    {
        if (!auth()->user()->canManage() && auth()->id() !== $legalAct->inserted_user_id) {
            abort(403, 'Sizin bu əməliyyat üçün icazəniz yoxdur.');
        }

        $validated = $request->validate([
            'act_type_id' => 'required|exists:act_types,id',
            'issued_by_id' => 'required|exists:issuing_authorities,id',
            'main_executor_ids' => 'required|array|min:1',
            'main_executor_ids.*' => 'required|exists:executors,id',
            'helper_executor_ids' => 'nullable|array',
            'helper_executor_ids.*' => 'required|exists:executors,id',
            'legal_act_number' => 'required|string|max:255',
            'legal_act_date' => 'required|date',
            'summary' => 'required|string',
            'task_number' => 'nullable|string|max:255',
            'task_description' => 'nullable|string',
            'execution_deadline' => 'nullable|date',
            'related_document_number' => 'nullable|string|max:255',
            'related_document_date' => 'nullable|date',
            'proof_required' => 'nullable|boolean',
        ], $this->validationMessages());

        $year = Carbon::parse($validated['legal_act_date'])->year;
        $exists = LegalAct::where('act_type_id', $validated['act_type_id'])
            ->where('legal_act_number', $validated['legal_act_number'])
            ->whereYear('legal_act_date', $year)
            ->where('is_deleted', false)
            ->where('id', '!=', $legalAct->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['legal_act_number' => 'Bu akt növü və il üzrə eyni nömrəli hüquqi akt artıq mövcuddur.'])->withInput();
        }

        $mainIds = array_unique(array_map('intval', $validated['main_executor_ids']));
        $helperIds = array_unique(array_map('intval', $validated['helper_executor_ids'] ?? []));

        if (array_intersect($mainIds, $helperIds)) {
            return back()->withErrors(['main_executor_ids' => 'Eyni icraçı həm əsas həm də digər ola bilməz.'])->withInput();
        }

        $actData = collect($validated)->except(['main_executor_ids', 'helper_executor_ids'])->toArray();
        $actData['proof_required'] = $request->boolean('proof_required') ? 1 : 0;

        $legalAct->update($actData);

        $legalAct->executors()->detach();
        foreach ($mainIds as $id) {
            $legalAct->executors()->attach($id, ['role' => 'main']);
        }
        foreach ($helperIds as $id) {
            $legalAct->executors()->attach($id, ['role' => 'helper']);
        }

        return redirect()->route('legal-acts.index')->with('success', 'Hüquqi akt uğurla yeniləndi.');
    }

    public function destroy(LegalAct $legalAct)
    {
        if (!auth()->user()->isAdmin())
            abort(403, 'Yalnız admin silə bilər.');
        $legalAct->update(['is_deleted' => true]);
        return redirect()->route('legal-acts.index')->with('success', 'Hüquqi akt uğurla silindi.');
    }

    public function exportExcel(Request $request)
    {
        $query = $this->applyFilters($request);
        $filename = 'legal_acts_' . now()->format('Y_m_d_His') . '.xls';
        return (new LegalActsExport($query))->download($filename);
    }

    public function exportWord(Request $request)
    {
        $query = $this->applyFilters($request);
        $legalActs = $query->get();
        $filename = 'legal_acts_' . now()->format('Y_m_d_His') . '.doc';
        $exportService = new LegalActWordExportService();
        $filePath = $exportService->export($legalActs, $filename);
        return response()->download($filePath, $filename, ['Content-Type' => 'application/msword'])
            ->deleteFileAfterSend(true);
    }

    public function toggleProofRequired(LegalAct $legalAct)
    {
        if (!auth()->user()->canManage()) {
            abort(403);
        }

        $legalAct->update([
            'proof_required' => !$legalAct->proof_required,
        ]);

        return response()->json([
            'success' => true,
            'proof_required' => (bool) $legalAct->proof_required,
            'message' => $legalAct->proof_required
                ? 'Sübut sənəd məcburi edildi.'
                : 'Sübut sənəd məcburiliyi ləğv edildi.',
        ]);
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
            'insertedUser',
        ])->active();

        $user = auth()->user();
        if (!$user->canManage()) {
            $query->where(function ($q) use ($user) {
                if ($user->executor_id) {
                    $q->whereHas('executors', fn($sq) => $sq->where('executors.id', $user->executor_id));
                }
                if ($user->department_id) {
                    $q->orWhereHas('executors', fn($sq) => $sq->where('executors.department_id', $user->department_id));
                }
            });
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
        if ($request->filled('col.deadline_status')) {
            $status = $request->input('col.deadline_status');
            $today = now()->startOfDay();
            $notExecuted = fn($q) => $q->whereDoesntHave('statusLogs')
                ->orWhereDoesntHave('latestStatusLog', fn($sq) => $sq
                    ->where('approval_status', ExecutorStatusLog::APPROVAL_APPROVED)
                    ->whereHas('executionNote', fn($nq) => $nq->where('note', 'like', '%İcra olunub%')));

            if ($status === 'expired') {
                $query->whereNotNull('execution_deadline')->where('execution_deadline', '<', $today)->where($notExecuted);
            } elseif (in_array($status, ['0day', '1day', '2days', '3days'])) {
                $days = (int) $status[0];
                $query->whereNotNull('execution_deadline')
                    ->whereDate('execution_deadline', '=', $today->copy()->addDays($days))
                    ->where($notExecuted);
            } elseif ($status === 'executed') {
                $query->whereHas('latestStatusLog', fn($q) => $q
                    ->where('approval_status', ExecutorStatusLog::APPROVAL_APPROVED)
                    ->whereHas('executionNote', fn($nq) => $nq->where('note', 'like', '%İcra olunub%')));
            } elseif ($status === 'pending') {
                $query->whereHas('latestStatusLog', fn($q) => $q
                    ->where('approval_status', ExecutorStatusLog::APPROVAL_PENDING)
                    ->whereHas('executionNote', fn($nq) => $nq->where('note', 'like', '%İcra olunub%')));
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
            'act_type_id.required' => 'Akt növü mütləq seçilməlidir.',
            'act_type_id.exists' => 'Seçilmiş akt növü mövcud deyil.',
            'issued_by_id.required' => 'Verən orqan mütləq seçilməlidir.',
            'issued_by_id.exists' => 'Seçilmiş verən orqan mövcud deyil.',
            'main_executor_ids.required' => 'Ən azı bir əsas icraçı seçilməlidir.',
            'main_executor_ids.min' => 'Ən azı bir əsas icraçı seçilməlidir.',
            'main_executor_ids.*.exists' => 'Seçilmiş əsas icraçı mövcud deyil.',
            'helper_executor_ids.*.exists' => 'Seçilmiş digər icraçı mövcud deyil.',
            'legal_act_number.required' => 'Hüquqi aktın nömrəsi mütləq daxil edilməlidir.',
            'legal_act_number.max' => 'Hüquqi aktın nömrəsi 255 simvoldan çox ola bilməz.',
            'legal_act_date.required' => 'Hüquqi aktın tarixi mütləq daxil edilməlidir.',
            'legal_act_date.date' => 'Hüquqi aktın tarixi düzgün tarix formatında olmalıdır.',
            'summary.required' => 'Xülasə mütləq daxil edilməlidir.',
            'execution_deadline.date' => 'İcra müddəti düzgün tarix formatında olmalıdır.',
            'related_document_number.max' => 'Əlaqəli sənədin nömrəsi 255 simvoldan çox ola bilməz.',
            'related_document_date.date' => 'Əlaqəli sənədin tarixi düzgün tarix formatında olmalıdır.',
        ];
    }
}