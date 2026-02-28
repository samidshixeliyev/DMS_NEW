<?php

namespace App\Http\Controllers;

use App\Models\LegalAct;
use App\Models\ExecutorStatusLog;
use App\Models\ExecutionAttachment;
use App\Models\ExecutionNote;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ExecutorDashboardController extends Controller
{
    private static ?array $icraOlunubNoteIds = null;

    private function getIcraOlunubNoteIds(): array
    {
        if (self::$icraOlunubNoteIds === null) {
            self::$icraOlunubNoteIds = ExecutionNote::all()
                ->filter(fn($n) => mb_stripos($n->note, 'İcra olunub') !== false || mb_stripos($n->note, 'icra olunub') !== false)
                ->pluck('id')->toArray();
        }
        return self::$icraOlunubNoteIds;
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
        if (!$user->executor_id && !$user->canManage()) abort(403, 'Sizin icraçı profiliniz yoxdur.');
        $executionNotes = ExecutionNote::active()->get();
        return view('executor.index', compact('executionNotes'));
    }

    public function load(Request $request)
    {
        $user       = auth()->user();
        $executorId = $user->executor_id;
        if (!$executorId && !$user->canManage()) {
            return response()->json(['draw' => 1, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []]);
        }

        $draw   = $request->input('draw', 1);
        $start  = $request->input('start', 0);
        $length = $request->input('length', 25);

        $baseQuery    = LegalAct::active()->whereHas('executors', fn($q) => $q->where('executors.id', $executorId));
        $totalRecords = (clone $baseQuery)->count();

        $query = LegalAct::with([
            'actType', 'issuingAuthority', 'executors.department',
            'latestStatusLog.executionNote', 'latestStatusLog.approvedByUser',
            'statusLogs.executionNote', 'statusLogs.approvedByUser', 'insertedUser',
        ])->active()->whereHas('executors', fn($q) => $q->where('executors.id', $executorId));

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

        $results = $query->skip($start)->take($length)->get();
        $data = [];

        foreach ($results as $i => $act) {
            $latestLog   = $act->latestStatusLog;
            $myLatestLog = $act->statusLogs->where('user_id', $user->id)->sortByDesc('id')->first();

            $isIcraOlunub   = $latestLog && $this->isIcraOlunubNote($latestLog->execution_note_id);
            $myIsIcraOlunub = $myLatestLog && $this->isIcraOlunubNote($myLatestLog->execution_note_id);
            $noteText       = $myLatestLog?->executionNote?->note ?? '';

            $isExecuted  = $isIcraOlunub && $latestLog->approval_status === 'approved';
            $isPending   = $isIcraOlunub && $latestLog->approval_status === 'pending';

            $myIsExecuted = $myIsIcraOlunub && $myLatestLog->approval_status === 'approved';
            $myIsPending  = $myIsIcraOlunub && in_array($myLatestLog->approval_status, ['pending', 'partial']);
            $myIsRejected = $myIsIcraOlunub && $myLatestLog->approval_status === 'rejected';

            $daysLeft = null;
            $rowClass = '';
            if ($isExecuted) {
                $rowClass = 'row-executed';
            } elseif ($isPending) {
                $rowClass = 'row-pending';
            } elseif ($act->execution_deadline) {
                $daysLeft = (int) now()->startOfDay()->diffInDays($act->execution_deadline->startOfDay(), false);
                $rowClass = $daysLeft < 0 ? 'row-overdue' : ($daysLeft <= 3 ? 'row-warning' : '');
            }

            $deadlineHtml = '-';
            if ($act->execution_deadline) {
                $deadlineHtml = $act->execution_deadline->format('d.m.Y');
                if (!$isExecuted && !$isPending && $daysLeft !== null) {
                    if ($daysLeft < 0)      $deadlineHtml .= '<br><span class="badge bg-danger text-white mt-1">İcra müddəti bitib</span>';
                    elseif ($daysLeft <= 3) $deadlineHtml .= '<br><span class="badge bg-warning text-dark mt-1">' . $daysLeft . ' gün qalıb</span>';
                }
            }

            $statusHtml = '-';
            if ($myLatestLog) {
                if ($myIsExecuted) {
                    $statusHtml = '<span class="badge bg-success">İcra olunub ✓</span>';
                } elseif ($myIsPending) {
                    $statusHtml = '<span class="badge bg-warning text-dark">Təsdiq gözləyir</span>';
                } elseif ($myIsRejected) {
                    $statusHtml = '<span class="badge bg-danger">Rədd edilib</span>';
                    if ($myLatestLog->approval_note)
                        $statusHtml .= '<br><small class="text-danger">' . e(Str::limit($myLatestLog->approval_note, 30)) . '</small>';
                } else {
                    $statusHtml = '<span class="badge bg-secondary">' . e(Str::limit($noteText, 25)) . '</span>';
                }
                if ($myLatestLog->custom_note && !$myIsRejected)
                    $statusHtml .= '<br><small class="text-muted">' . e(Str::limit($myLatestLog->custom_note, 30)) . '</small>';
            }

            $pivot    = $act->executors->where('id', $executorId)->first()?->pivot;
            $roleHtml = $pivot?->role === 'main'
                ? '<span class="badge bg-primary">Əsas</span>'
                : '<span class="badge bg-info">Digər</span>';

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
                'canChangeStatus' => !$myIsExecuted && !$myIsPending,
            ];
        }

        return response()->json(['draw' => (int) $draw, 'recordsTotal' => $totalRecords, 'recordsFiltered' => $filteredRecords, 'data' => $data]);
    }

    public function show(LegalAct $legalAct)
    {
        $user = auth()->user();
        $this->authorizeAccess($legalAct, $user);

        $legalAct->load([
            'actType', 'issuingAuthority', 'executors.department',
            'statusLogs.executionNote', 'statusLogs.user', 'statusLogs.attachments',
            'statusLogs.approvedByUser', 'attachments.user', 'insertedUser',
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
            'main_executors'             => $mainExecutors->map(fn($e) => ['id' => $e->id, 'name' => $e->name, 'department' => $e->department?->name]),
            'helper_executors'           => $helperExecutors->map(fn($e) => ['id' => $e->id, 'name' => $e->name, 'department' => $e->department?->name]),
            // Legacy fields
            'main_executor'              => $mainExecutors->first()?->name,
            'main_executor_department'   => $mainExecutors->first()?->department?->name,
            'helper_executor'            => $helperExecutors->first()?->name,
            'helper_executor_department' => $helperExecutors->first()?->department?->name,
            'task_number'                => $legalAct->task_number,
            'task_description'           => $legalAct->task_description,
            'execution_deadline'         => $legalAct->execution_deadline?->format('d.m.Y'),
            'related_document_number'    => $legalAct->related_document_number,
            'related_document_date'      => $legalAct->related_document_date?->format('d.m.Y'),
            'inserted_user'              => $legalAct->insertedUser?->full_name,
            'created_at'                 => $legalAct->created_at?->format('d.m.Y H:i'),
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
                'attachments'     => $log->attachments->map(fn($att) => ['id' => $att->id, 'name' => $att->original_name, 'size' => round($att->file_size / 1024, 1) . ' KB', 'mime_type' => $att->mime_type]),
            ]),
        ]);
    }

    public function storeStatus(Request $request, LegalAct $legalAct)
    {
        $user = auth()->user();
        $this->authorizeAccess($legalAct, $user);

        $validated = $request->validate([
            'execution_note_id' => 'required|exists:execution_notes,id',
            'custom_note'       => 'nullable|string|max:2000',
            'attachments'       => 'nullable|array|max:10',
            'attachments.*'     => 'file|max:10240',
        ]);

        $isIcraOlunub       = $this->isIcraOlunubNote((int) $validated['execution_note_id']);
        $isQismenIcra       = $this->isQismenIcraNote((int) $validated['execution_note_id']);
        $requiresAllExecutors = $isIcraOlunub || $isQismenIcra;

        // Block if already approved
        $latestLog = $legalAct->latestStatusLog()->with('executionNote')->first();
        if ($latestLog) {
            $latestIsIcra = $this->isIcraOlunubNote($latestLog->execution_note_id);
            if ($latestIsIcra && $latestLog->approval_status === 'approved') {
                return back()->withErrors(['general' => 'Bu sənəd artıq icra olunub və təsdiqlənib.']);
            }
            if ($latestIsIcra && $latestLog->approval_status === 'pending') {
                return back()->withErrors(['general' => 'Bu sənəd üçün təsdiq gözləyən icra qeydi var.']);
            }
        }

        // Block if this user already has a partial for this round
        if ($requiresAllExecutors) {
            $existingPartial = ExecutorStatusLog::where('legal_act_id', $legalAct->id)
                ->where('user_id', $user->id)
                ->where('approval_status', 'partial')
                ->first();
            if ($existingPartial) {
                return back()->withErrors(['general' => 'Siz artıq status göndərmisiniz. Digər icraçıların cavabı gözlənilir.']);
            }
        }

        // İcra olunub requires at least 1 file
        if ($isIcraOlunub) {
            $hasValidFiles = false;
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    if ($file && $file->isValid()) { $hasValidFiles = true; break; }
                }
            }
            if (!$hasValidFiles) {
                return back()->withErrors(['attachments' => '"İcra olunub" statusu seçildikdə ən azı bir sübut sənəd yükləmək MƏCBURİDİR!'])->withInput();
            }
        }

        // MIME validation
        $allowedMimes = ['application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','image/jpeg','image/png','image/jpg'];
        $allowedExts  = ['doc','docx','pdf','jpg','jpeg','png'];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if (!$file || !$file->isValid()) continue;
                if (!in_array($file->getClientMimeType(), $allowedMimes) && !in_array(strtolower($file->getClientOriginalExtension()), $allowedExts)) {
                    return back()->withErrors(['attachments' => 'Yalnız Word, PDF və şəkil faylları qəbul olunur.'])->withInput();
                }
            }
        }

        // ── Multi-executor partial logic ───────────────────────────────
        $approvalStatus = null;
        if ($requiresAllExecutors) {
            // Get ALL executor user_ids assigned to this legal act (both main and helper)
            $allAssignedExecutorIds = $legalAct->executors()->pluck('executors.id')->toArray();
            $totalExecutors         = count($allAssignedExecutorIds);

            if ($totalExecutors > 1) {
                // Count how many OTHER users have already submitted partial for this round
                $otherPartials = ExecutorStatusLog::where('legal_act_id', $legalAct->id)
                    ->where('user_id', '!=', $user->id)
                    ->where('approval_status', 'partial')
                    ->get();

                // Find executor_ids of those who already submitted
                $submittedUserIds = $otherPartials->pluck('user_id')->toArray();

                // How many distinct executors have submitted (including current user after this submission)
                $submittedCount = count($submittedUserIds) + 1; // +1 for current user

                if ($submittedCount >= $totalExecutors) {
                    // Everyone has submitted — promote all partials to pending
                    $approvalStatus = 'pending';
                    ExecutorStatusLog::where('legal_act_id', $legalAct->id)
                        ->where('approval_status', 'partial')
                        ->update(['approval_status' => 'pending']);
                } else {
                    // Still waiting for others
                    $approvalStatus = 'partial';
                }
            } else {
                // Only one executor — go straight to pending
                $approvalStatus = $isIcraOlunub ? 'pending' : null;
            }
        }

        $statusLog = ExecutorStatusLog::create([
            'legal_act_id'      => $legalAct->id,
            'user_id'           => $user->id,
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
                    'user_id'       => $user->id,
                    'status_log_id' => $statusLog->id,
                    'file_path'     => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type'     => $file->getClientMimeType(),
                    'file_size'     => $file->getSize(),
                ]);
            }
        }

        $successMsg = 'Status uğurla yeniləndi.';
        if ($requiresAllExecutors && $approvalStatus === 'partial') {
            $remaining  = $this->getRemainingExecutorCount($legalAct, $user->id);
            $successMsg = 'Status göndərildi. Daha ' . $remaining . ' icraçının cavabı gözlənilir.';
        } elseif ($requiresAllExecutors && $approvalStatus === 'pending') {
            $successMsg = 'Bütün icraçılar status göndərdi. Admin/menecer təsdiqi gözlənilir.';
        }

        return redirect()->route('executor.index')->with('success', $successMsg);
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
        $ext      = strtolower(pathinfo($attachment->original_name, PATHINFO_EXTENSION));

        if (in_array($ext, ['jpg','jpeg','png'])) {
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

    // ─── Private helpers ────────────────────────────────────────────────────

    private function getRemainingExecutorCount(LegalAct $legalAct, int $currentUserId): int
    {
        $total     = $legalAct->executors()->count();
        $submitted = ExecutorStatusLog::where('legal_act_id', $legalAct->id)
            ->whereIn('approval_status', ['partial', 'pending'])
            ->distinct('user_id')
            ->count('user_id');
        return max(0, $total - $submitted);
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
        if ($attachment->legalAct && $user->isExecutor()) $this->authorizeAccess($attachment->legalAct, $user);
    }

    private function authorizeAccess(LegalAct $legalAct, $user): void
    {
        if ($user->canManage()) return;
        if (!$user->executor_id) abort(403, 'İcraçı profiliniz yoxdur.');
        if (!$legalAct->executors()->where('executors.id', $user->executor_id)->exists()) abort(403, 'Bu sənədə giriş icazəniz yoxdur.');
    }
}