<?php

use App\Models\ExecutionNote;
use App\Models\ExecutorStatusLog;
use App\Models\LegalAct;
use App\Services\TaskNotificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ─── Deadline Reminders ────────────────────────────────────────────────────
// Hər gün 08:00-da işləyir.
// 1. İcra müddəti tam olaraq 3 gün sonra olan tapşırıqlara "xatırlatma" göndərir.
// 2. İcra müddəti bu gün bitən tapşırıqlara "müddət bitdi" bildirişi göndərir.
// Artıq "icra olunub" statusu olan tapşırıqlar avtomatik çıxarılır (SQL səviyyəsində).
Schedule::call(function () {
    $service = app(TaskNotificationService::class);

    // ── Step 1: SQL səviyyəsində icra edilmiş aktların ID-lərini tap ─────
    // "İcra olunub" statusu olan ExecutionNote-ların ID-ləri
    $icraIds = ExecutionNote::active()
        ->where(function ($q) {
            $q->where('note', 'like', '%İcra olunub%')
              ->orWhere('note', 'like', '%icra olunub%');
        })
        ->pluck('id');

    // Həmin note-lara aid, approved statuslu logları olan aktların ID-ləri
    $executedActIds = $icraIds->isNotEmpty()
        ? ExecutorStatusLog::whereIn('execution_note_id', $icraIds)
            ->where('approval_status', 'approved')
            ->pluck('legal_act_id')
            ->unique()
            ->values()
        : collect();

    $today       = Carbon::today();
    $reminderDay = $today->copy()->addDays(3);

    // ── Step 2: 3 günlük xatırlatma ──────────────────────────────────────
    $actsDueSoon = LegalAct::active()
        ->whereDate('execution_deadline', $reminderDay)
        ->whereNotIn('id', $executedActIds)   // SQL-də filter et
        ->with('executors')
        ->get();

    Log::info('[Deadline] 3 günlük xatırlatma', [
        'tarix' => $reminderDay->toDateString(),
        'akt sayı' => $actsDueSoon->count(),
    ]);

    foreach ($actsDueSoon as $act) {
        try {
            $service->sendDeadlineReminder($act);
        } catch (\Throwable $e) {
            Log::error('[Deadline] 3 günlük xatırlatma xətası', [
                'legal_act_id' => $act->id,
                'xəta'         => $e->getMessage(),
            ]);
        }
    }

    // ── Step 3: Müddət bitmə bildirişi ───────────────────────────────────
    $actsExpired = LegalAct::active()
        ->whereDate('execution_deadline', $today)
        ->whereNotIn('id', $executedActIds)   // SQL-də filter et
        ->with('executors')
        ->get();

    Log::info('[Deadline] Müddət bitmə bildirişi', [
        'tarix'    => $today->toDateString(),
        'akt sayı' => $actsExpired->count(),
    ]);

    foreach ($actsExpired as $act) {
        try {
            $service->sendDeadlineExpired($act);
        } catch (\Throwable $e) {
            Log::error('[Deadline] Müddət bitmə bildirişi xətası', [
                'legal_act_id' => $act->id,
                'xəta'         => $e->getMessage(),
            ]);
        }
    }

    Log::info('[Deadline] Bütün bildirişlər tamamlandı.');
})
->dailyAt('08:00')
->name('deadline-reminders')
->withoutOverlapping();
