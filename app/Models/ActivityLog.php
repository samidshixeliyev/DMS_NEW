<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'ip_address',
    ];

    // ── Constants ──────────────────────────────────────────────────────────
    const ACTION_LOGIN  = 'login';
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';

    // ── Relations ──────────────────────────────────────────────────────────
    public function user()
    {
        return $this->belongsTo(User::class)->withDefault(['name' => 'Silinmiş', 'surname' => '']);
    }

    // ── Helper ─────────────────────────────────────────────────────────────
    /**
     * Write a log entry for the currently authenticated user.
     */
    public static function record(
        string  $action,
        string  $description,
        ?string $subjectType = null,
        ?int    $subjectId   = null
    ): void {
        try {
            static::create([
                'user_id'      => auth()->id(),
                'action'       => $action,
                'subject_type' => $subjectType,
                'subject_id'   => $subjectId,
                'description'  => $description,
                'ip_address'   => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            // Never let logging failures break the main request.
            // Write to the Laravel log so the issue is visible.
            \Illuminate\Support\Facades\Log::error('ActivityLog::record() failed', [
                'action'      => $action,
                'description' => $description,
                'error'       => $e->getMessage(),
            ]);
        }
    }
}
