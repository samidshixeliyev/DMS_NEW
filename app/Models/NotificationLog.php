<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NotificationLog extends Model
{
    // Notification type constants — extend here as new features are added
    const TYPE_TASK_ASSIGNED      = 'task_assigned';
    const TYPE_DEADLINE_REMINDER  = 'deadline_reminder';
    const TYPE_DEADLINE_EXPIRED   = 'deadline_expired';

    protected $fillable = [
        'notifiable_type',
        'notifiable_id',
        'type',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Check whether a notification of the given type was already sent for a model.
     */
    public static function alreadySent(Model $notifiable, string $type): bool
    {
        return static::where('notifiable_type', get_class($notifiable))
            ->where('notifiable_id', $notifiable->getKey())
            ->where('type', $type)
            ->exists();
    }

    /**
     * Record that a notification was sent. Safe to call multiple times —
     * uses firstOrCreate so duplicate DB rows are never created.
     */
    public static function record(Model $notifiable, string $type): void
    {
        static::firstOrCreate(
            [
                'notifiable_type' => get_class($notifiable),
                'notifiable_id'   => $notifiable->getKey(),
                'type'            => $type,
            ],
            ['sent_at' => now()]
        );
    }
}
