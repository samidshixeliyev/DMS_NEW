<?php

namespace App\Services;

use App\Mail\DeadlineExpiredMail;
use App\Mail\DeadlineReminderMail;
use App\Mail\NewDepartmentTaskAssigned;
use App\Models\LegalAct;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TaskNotificationService
{
    /**
     * Send "new task assigned" emails to all active users in the departments
     * of the given executors. Called from LegalActController::store().
     *
     * @param  LegalAct  $legalAct
     * @param  array<int>  $executorIds  IDs of all assigned executors (main + helper)
     */
    public function notifyDepartmentUsers(LegalAct $legalAct, array $executorIds): void
    {
        $this->sendToDepartmentUsers(
            $legalAct,
            $executorIds,
            fn() => new NewDepartmentTaskAssigned($legalAct),
            'tapşırıq-təyin-bildirişi'
        );
    }

    /**
     * Send "3 days until deadline" reminder to active users in executor departments.
     */
    public function sendDeadlineReminder(LegalAct $legalAct): void
    {
        $legalAct->loadMissing('executors');
        $executorIds = $legalAct->executors->pluck('id')->toArray();

        $this->sendToDepartmentUsers(
            $legalAct,
            $executorIds,
            fn() => new DeadlineReminderMail($legalAct),
            'müddət-xatırlatma'
        );
    }

    /**
     * Send "deadline expired" notification to active users in executor departments.
     */
    public function sendDeadlineExpired(LegalAct $legalAct): void
    {
        $legalAct->loadMissing('executors');
        $executorIds = $legalAct->executors->pluck('id')->toArray();

        $this->sendToDepartmentUsers(
            $legalAct,
            $executorIds,
            fn() => new DeadlineExpiredMail($legalAct),
            'müddət-bitmə-bildirişi'
        );
    }

    /**
     * Core sending logic: resolve executor departments → active users → send mailable.
     *
     * @param  LegalAct  $legalAct
     * @param  array<int>  $executorIds
     * @param  \Closure  $mailFactory   Returns a Mailable instance
     * @param  string  $label           For logging context
     */
    private function sendToDepartmentUsers(
        LegalAct $legalAct,
        array $executorIds,
        \Closure $mailFactory,
        string $label
    ): void {
        if (empty($executorIds)) {
            return;
        }

        // Collect unique department IDs from the assigned executors.
        $departmentIds = \App\Models\Executor::whereIn('id', $executorIds)
            ->whereNotNull('department_id')
            ->pluck('department_id')
            ->unique()
            ->values()
            ->all();

        if (empty($departmentIds)) {
            return;
        }

        // Find active users in those departments who have an email address.
        // Two cases:
        //   1. users.department_id is set directly (managers, admin-type users)
        //   2. executor-role users whose department lives on the executors table,
        //      not on users.department_id — matched via the executor relationship.
        $users = User::active()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->where(function ($q) use ($departmentIds) {
                $q->whereIn('department_id', $departmentIds)
                  ->orWhereHas('executor', function ($eq) use ($departmentIds) {
                      $eq->whereIn('department_id', $departmentIds);
                  });
            })
            ->get();

        $total      = $users->count();
        $successful = 0;
        $failed     = 0;

        Log::info("TaskNotificationService [{$label}]: bildiriş başladı", [
            'legal_act_id'     => $legalAct->id,
            'departments'      => $departmentIds,
            'ümumi_istifadəçi' => $total,
        ]);

        if ($total === 0) {
            Log::info("TaskNotificationService [{$label}]: bu bölmələrdə aktiv istifadəçi tapılmadı.");
            return;
        }

        foreach ($users as $user) {
            try {
                Mail::to($user->email)->send($mailFactory());
                $successful++;
            } catch (\Throwable $e) {
                $failed++;
                Log::error("TaskNotificationService [{$label}]: e-poçt göndərilmədi", [
                    'user_id' => $user->id,
                    'email'   => $user->email,
                    'xəta'    => $e->getMessage(),
                ]);
            }
        }

        Log::info("TaskNotificationService [{$label}]: tamamlandı", [
            'legal_act_id' => $legalAct->id,
            'ümumi'        => $total,
            'uğurlu'       => $successful,
            'uğursuz'      => $failed,
        ]);
    }
}
