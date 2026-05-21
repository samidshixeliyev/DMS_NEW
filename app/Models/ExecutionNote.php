<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExecutionNote extends Model
{
    use HasFactory;

    protected $fillable = ['note', 'is_deleted'];

    protected $casts = [
        'is_deleted' => 'boolean',
    ];

    // Per-request cache. ExecutionNote rows change rarely; loading them once per
    // request removes the repeated LIKE '%İcra olunub%' scan from hot paths.
    private static ?array $icraOlunubIdsCache = null;

    public function scopeActive($query)
    {
        return $query->where('is_deleted', false);
    }

    public function legalActs()
    {
        return $this->hasMany(LegalAct::class);
    }

    /**
     * IDs of all active "İcra olunub" execution notes, cached for the request.
     */
    public static function icraOlunubIds(): array
    {
        if (self::$icraOlunubIdsCache === null) {
            self::$icraOlunubIdsCache = self::query()
                ->where('is_deleted', false)
                ->where(function ($q) {
                    $q->where('note', 'like', '%İcra olunub%')
                      ->orWhere('note', 'like', '%icra olunub%');
                })
                ->pluck('id')
                ->all();
        }
        return self::$icraOlunubIdsCache;
    }

    protected static function booted(): void
    {
        static::saved(fn() => self::$icraOlunubIdsCache = null);
        static::deleted(fn() => self::$icraOlunubIdsCache = null);
    }
}
