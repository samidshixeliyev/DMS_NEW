<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'password',
        'name',
        'surname',
        'user_role',
        'executor_id',
        'department_id',
        'is_deleted',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_deleted' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_deleted', false);
    }

    /**
     * The executor (rəhbər icraçı) this user belongs to.
     */
    public function executor()
    {
        return $this->belongsTo(Executor::class);
    }

    /**
     * Legal acts inserted by this user.
     */
    public function legalActs()
    {
        return $this->hasMany(LegalAct::class, 'inserted_user_id');
    }

    /**
     * Status logs created by this user.
     */
    public function statusLogs()
    {
        return $this->hasMany(ExecutorStatusLog::class);
    }

    /**
     * Attachments uploaded by this user.
     */
    public function attachments()
    {
        return $this->hasMany(ExecutionAttachment::class);
    }

    /**
     * Check if user is an executor role.
     */
    public function isExecutor(): bool
    {
        return $this->user_role === 'executor';
    }

    /**
     * Check if user can manage (admin or manager).
     */
    public function canManage(): bool
    {
        return in_array($this->user_role, ['admin', 'manager']);
    }

    /**
     * Check if user can create and assign tasks.
     * True for admin/manager and for users whose department has can_assign=true.
     */
    public function canAssignTasks(): bool
    {
        return $this->canManage() || (bool) $this->department?->can_assign;
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->user_role === 'admin';
    }

    public function getFullNameAttribute()
    {
        return "{$this->name} {$this->surname}";
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function executorPivot($legalActId)
    {
        return \App\Models\LegalAct::find($legalActId)
            ->executors()
            ->where('executor_id', $this->id)
            ->first()
                ?->pivot;
    }
}
