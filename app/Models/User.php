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
        'email',
        'user_role',
        'executor_id',
        'department_id',
        'is_deleted',
        'force_password_change',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_deleted'             => 'boolean',
        'force_password_change'  => 'boolean',
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
     * Only admin, or managers whose department (or ancestor) has can_assign=true.
     */
    public function canAssignTasks(): bool
    {
        return $this->isAdmin() || ($this->user_role === 'manager' && $this->canAssignDeptId() !== null);
    }

    /**
     * Check if user may create legal acts.
     * Only managers whose department (or ancestor) has can_assign=true. Admin is excluded.
     */
    public function canCreateLegalActs(): bool
    {
        return $this->user_role === 'manager' && $this->canAssignDeptId() !== null;
    }

    /**
     * Returns the ID of the nearest ancestor department (or own) that has can_assign=true.
     * This defines the root of the subtree the user is allowed to assign tasks within.
     */
    public function canAssignDeptId(): ?int
    {
        $dept = $this->department;
        while ($dept) {
            if ($dept->can_assign) return $dept->id;
            $dept = $dept->parent;
        }
        return null;
    }

    /**
     * The effective department ID for all visibility scoping.
     *
     * For executor-role users the executor record's department is authoritative —
     * user.department_id may be set differently as an administrative field.
     * For managers and admins user.department_id is always used.
     */
    public function effectiveDeptId(): ?int
    {
        if (!$this->isAdmin() && !$this->canManage() && $this->executor_id) {
            $execDeptId = $this->executor?->department_id;
            if ($execDeptId) return (int) $execDeptId;
        }
        return $this->department_id ? (int) $this->department_id : null;
    }

    /**
     * Like canAssignDeptId() but anchored on effectiveDeptId().
     * Use this for scoping instead of canAssignDeptId() to ensure executor-role
     * users are scoped to their executor's department, not user.department_id.
     */
    public function effectiveCanAssignDeptId(): ?int
    {
        $deptId = $this->effectiveDeptId();
        if (!$deptId) return null;

        $dept = \App\Models\Department::find($deptId);
        while ($dept) {
            if ($dept->can_assign) return $dept->id;
            $dept = $dept->parent;
        }
        return null;
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
