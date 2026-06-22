<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExecutionAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'legal_act_id',
        'user_id',
        'status_log_id',
        'file_path',
        'disk',
        'original_name',
        'mime_type',
        'file_size',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function legalAct()
    {
        return $this->belongsTo(LegalAct::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function statusLog()
    {
        return $this->belongsTo(ExecutorStatusLog::class, 'status_log_id');
    }
}
