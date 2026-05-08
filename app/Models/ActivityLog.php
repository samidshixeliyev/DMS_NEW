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
        // Use DB::table (raw query builder) instead of Eloquent::create().
        // SQL Server's PDO driver can silently discard Eloquent inserts when
        // SCOPE_IDENTITY() fails to resolve after the INSERT, leaving no row.
        // The raw insert bypasses that issue entirely.
        try {
            $now = now()->toDateTimeString();
            \Illuminate\Support\Facades\DB::table('activity_logs')->insert([
                'user_id'      => auth()->id(),
                'action'       => $action,
                'subject_type' => $subjectType,
                'subject_id'   => $subjectId,
                'description'  => $description,
                'ip_address'   => request()->ip(),
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        } catch (\Throwable $e) {
            // Never let logging failures break the main request.
            \Illuminate\Support\Facades\Log::error('ActivityLog::record() failed', [
                'action'      => $action,
                'description' => $description,
                'error'       => $e->getMessage(),
            ]);
        }
    }
}
