<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalActChangelog extends Model
{
    protected $fillable = [
        'legal_act_id',
        'user_id',
        'field_key',
        'field_label',
        'old_value',
        'new_value',
    ];

    public function legalAct()
    {
        return $this->belongsTo(LegalAct::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
