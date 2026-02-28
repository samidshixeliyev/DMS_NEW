<?php

namespace App\Http\Controllers;

use App\Models\LegalAct;
use App\Models\ExecutorStatusLog;
use App\Models\ExecutionNote;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApprovalController extends Controller
{
    private function getIcraOlunubNoteIds(): array
    {
        return ExecutionNote::all()
            ->filter(fn($n) => mb_stripos($n->note, 'İcra olunub') !== false || mb_stripos($n->note, 'icra olunub') !== false || mb_stripos($n->note, 'ICRA OLUNUB') !== false)
            ->pluck('id')->toArray();
    }

    private function getSubmittedByHtml(ExecutorStatusLog $log): string
    {
        $allPendingLogs = ExecutorStatusLog::where('legal_act_id', $log->legal_act_id)
            ->where('approval_status', 'pending')
            ->with(['user', 'executionNote'])
            ->get();

        if ($allPendingLogs->count() <= 1) {
            return e($log->user->name ?? '-');
        }

        $html = '';
        foreach ($allPendingLogs as $pLog) {
            $executorId = $pLog->user?->executor_id;
            $role       = null;
            if ($executorId) {
                $pivot = LegalAct::find($log->legal_act_id)?->executors()->where('executors.id', $executorId)->first()?->pivot;
                $role  = $pivot?->role;
            }
            $roleLabel = $role === 'main' ? 'Əsas' : 'Digər';
            $html .= '<div><strong>' . e($roleLabel) . ':</strong> ' . e($pLog->user->name ?? '-')
                . ' <small class="text-muted">(' . e($pLog->executionNote->note ?? '') . ')</small></div>';
        }
        return $html;
    }

    public function index()
    {
        return view('approvals.index');
    }

    public function load(Request $request)
    {
        $draw   = $request->input('draw', 1);
        $start  = $request->input('start', 0);
        $length = $request->input('length', 25);

        $icraOlunubIds = $this->getIcraOlunubNoteIds();
        if (empty($icraOlunubIds)) {
            return response()->json(['draw' => (int) $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []]);
        }

        $pendingLogs = ExecutorStatusLog::with([
            'legalAct.actType', 'legalAct.issuingAuthority',
            'legalAct.executors.department', 'legalAct.insertedUser',
            'user', 'executionNote', 'attachments',
        ])
            ->where('approval_status', 'pending')
            ->whereIn('execution_note_id', $icraOlunubIds)
            ->whereHas('legalAct', fn($q) => $q->where('is_deleted', false))
            ->orderBy('created_at', 'desc');

        $totalRecords = (clone $pendingLogs)->count();

        if ($request->filled('col.legal_act_number')) {
            $term = trim($request->input('col.legal_act_number'));
            $pendingLogs->whereHas('legalAct', fn($q) => $q->where('legal_act_number', 'like', '%' . $term . '%'));
        }

        $filteredRecords = (clone $pendingLogs)->count();
        $results         = $pendingLogs->skip($start)->take($length)->get();

        $data = [];
        foreach ($results as $i => $log) {
            $act = $log->legalAct;
            if (!$act) continue;

            // Show all main executors (first one for compact display, all for tooltip)
            $mainExecutors = $act->executors->where('pivot.role', 'main')->values();
            $firstMain     = $mainExecutors->first();

            $executorHtml = '-';
            if ($mainExecutors->isNotEmpty()) {
                if ($mainExecutors->count() === 1) {
                    $executorHtml = e($firstMain->name);
                    if ($firstMain->department) {
                        $executorHtml .= '<br><small class="text-muted">' . e($firstMain->department->name) . '</small>';
                    }
                } else {
                    $names = $mainExecutors->map(fn($e) => e($e->name))->implode('<br>');
                    $executorHtml = $names;
                }
            }

            $data[] = [
                'id'              => $act->id,
                'logId'           => $log->id,
                'rowNum'          => $start + $i + 1,
                'actType'         => $act->actType?->name ?? '-',
                'legalActNumber'  => $act->legal_act_number ?? '-',
                'legalActDate'    => $act->legal_act_date?->format('d.m.Y') ?? '-',
                'summary'         => Str::limit($act->summary, 60) ?? '-',
                'executor'        => $executorHtml,
                'submittedBy'     => $this->getSubmittedByHtml($log),
                'submittedAt'     => $log->created_at?->format('d.m.Y H:i') ?? '-',
                'customNote'      => $log->custom_note ? Str::limit($log->custom_note, 40) : '-',
                'attachmentCount' => $log->attachments->count(),
                'deadlineHtml'    => $act->execution_deadline ? $act->execution_deadline->format('d.m.Y') : '-',
            ];
        }

        return response()->json([
            'draw'            => (int) $draw,
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $data,
        ]);
    }

    public function show(LegalAct $legalAct)
    {
        $legalAct->load([
            'actType', 'issuingAuthority', 'executors.department',
            'statusLogs.executionNote', 'statusLogs.user',
            'statusLogs.attachments', 'statusLogs.approvedByUser',
            'insertedUser',
        ]);

        $mainExecutors   = $legalAct->executors->where('pivot.role', 'main')->values();
        $helperExecutors = $legalAct->executors->where('pivot.role', 'helper')->values();

        return response()->json([
            'id'                         => $legalAct->id,
            'act_type'                   => $legalAct->actType?->name,
            'legal_act_number'           => $legalAct->legal_act_number,
            'legal_act_date'             => $legalAct->legal_act_date?->format('d.m.Y'),
            'summary'                    => $legalAct->summary,
            'issuing_authority'          => $legalAct->issuingAuthority?->name,
            'main_executors'             => $mainExecutors->map(fn($e) => ['name' => $e->name, 'department' => $e->department?->name]),
            'helper_executors'           => $helperExecutors->map(fn($e) => ['name' => $e->name, 'department' => $e->department?->name]),
            // Legacy
            'main_executor'              => $mainExecutors->first()?->name,
            'main_executor_department'   => $mainExecutors->first()?->department?->name,
            'helper_executor'            => $helperExecutors->first()?->name,
            'helper_executor_department' => $helperExecutors->first()?->department?->name,
            'task_number'                => $legalAct->task_number,
            'task_description'           => $legalAct->task_description,
            'execution_deadline'         => $legalAct->execution_deadline?->format('d.m.Y'),
            'status_logs'                => $legalAct->statusLogs->map(fn($log) => [
                'id'              => $log->id,
                'user'            => $log->user?->full_name,
                'note'            => $log->executionNote?->note,
                'custom_note'     => $log->custom_note,
                'date'            => $log->created_at?->format('d.m.Y H:i'),
                'approval_status' => $log->approval_status,
                'approval_note'   => $log->approval_note,
                'approved_by'     => $log->approvedByUser?->full_name,
                'approved_at'     => $log->approved_at?->format('d.m.Y H:i'),
                'attachments'     => $log->attachments->map(fn($att) => [
                    'id'        => $att->id,
                    'name'      => $att->original_name,
                    'size'      => round($att->file_size / 1024, 1) . ' KB',
                    'mime_type' => $att->mime_type,
                ]),
            ]),
        ]);
    }

    public function approve(Request $request, ExecutorStatusLog $statusLog)
    {
        if ($statusLog->approval_status !== 'pending') {
            return back()->withErrors(['general' => 'Bu qeyd artıq işlənib.']);
        }
        $validated = $request->validate(['approval_note' => 'nullable|string|max:2000']);
        $statusLog->update([
            'approval_status' => 'approved',
            'approved_by'     => auth()->id(),
            'approval_note'   => $validated['approval_note'] ?? null,
            'approved_at'     => now(),
        ]);
        return redirect()->route('approvals.index')->with('success', 'İcra qeydi təsdiqləndi. Sənəd "İcra olunub" statusuna keçdi.');
    }

    public function reject(Request $request, ExecutorStatusLog $statusLog)
    {
        if ($statusLog->approval_status !== 'pending') {
            return back()->withErrors(['general' => 'Bu qeyd artıq işlənib.']);
        }
        $validated = $request->validate(['approval_note' => 'required|string|max:2000']);
        $statusLog->update([
            'approval_status' => 'rejected',
            'approved_by'     => auth()->id(),
            'approval_note'   => $validated['approval_note'],
            'approved_at'     => now(),
        ]);
        return redirect()->route('approvals.index')->with('success', 'İcra qeydi rədd edildi. İcraçı yenidən status təyin edə bilər.');
    }
}