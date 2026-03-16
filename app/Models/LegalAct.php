<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegalAct extends Model
{
    use HasFactory;

    protected $fillable = [
        'act_type_id',
        'issued_by_id',
        'legal_act_number',
        'legal_act_date',
        'summary',
        'task_number',
        'task_description',
        'execution_deadline',
        'related_document_number',
        'related_document_date',
        'proof_required',
        'created_date',
        'inserted_user_id',
        'is_active',
        'is_deleted',
    ];

    protected $casts = [
        'legal_act_date' => 'date',
        'execution_deadline' => 'date',
        'related_document_date' => 'date',
        'created_date' => 'datetime',
        'is_active' => 'boolean',
        'is_deleted' => 'boolean',
        'proof_required' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_deleted', false);
    }

    public function actType()
    {
        return $this->belongsTo(ActType::class);
    }

    public function issuingAuthority()
    {
        return $this->belongsTo(IssuingAuthority::class, 'issued_by_id');
    }

    public function executors()
    {
        return $this->belongsToMany(Executor::class, 'legal_act_executor')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    public function mainExecutor()
    {
        return $this->executors()->wherePivot('role', 'main');
    }

    public function helperExecutor()
    {
        return $this->executors()->wherePivot('role', 'helper');
    }

    public function statusLogs()
    {
        return $this->hasMany(ExecutorStatusLog::class)->orderBy('created_at', 'desc');
    }

    public function attachments()
    {
        return $this->hasMany(ExecutionAttachment::class);
    }

    public function latestStatusLog()
    {
        return $this->hasOne(ExecutorStatusLog::class)->latestOfMany();
    }

    public function insertedUser()
    {
        return $this->belongsTo(User::class, 'inserted_user_id');
    }

    public function getIsExecutedAttribute(): bool
    {
        $latest = $this->latestStatusLog;
        if (!$latest) return false;

        $noteText = $latest->executionNote?->note ?? '';
        $isIcraOlunub = $noteText && mb_stripos($noteText, 'İcra olunub') !== false;

        return $isIcraOlunub && $latest->approval_status === ExecutorStatusLog::APPROVAL_APPROVED;
    }

    public function getIsPendingApprovalAttribute(): bool
    {
        $latest = $this->latestStatusLog;
        if (!$latest) return false;

        $noteText = $latest->executionNote?->note ?? '';
        $isIcraOlunub = $noteText && mb_stripos($noteText, 'İcra olunub') !== false;

        return $isIcraOlunub && $latest->approval_status === ExecutorStatusLog::APPROVAL_PENDING;
    }

    public function getIsRejectedAttribute(): bool
    {
        $latest = $this->latestStatusLog;
        if (!$latest) return false;

        $noteText = $latest->executionNote?->note ?? '';
        $isIcraOlunub = $noteText && mb_stripos($noteText, 'İcra olunub') !== false;

        return $isIcraOlunub && $latest->approval_status === ExecutorStatusLog::APPROVAL_REJECTED;
    }

    public function pendingApprovalLogs()
    {
        return $this->statusLogs()->where('approval_status', ExecutorStatusLog::APPROVAL_PENDING);
    }
}