<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'parent_id', 'can_assign', 'is_deleted'];

    protected $casts = [
        'is_deleted' => 'boolean',
        'can_assign' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_deleted', false);
    }

    public function executors()
    {
        return $this->hasMany(Executor::class);
    }

    public function parent()
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Department::class, 'parent_id');
    }

    /**
     * Returns IDs of this department and all its descendants (recursive).
     * Loads all departments once and traverses in memory to avoid N+1 queries.
     */
    public function selfAndDescendantIds(): array
    {
        return static::descendantIdsOf($this->id);
    }

    /**
     * Returns IDs of the given department and all its descendants.
     */
    public static function descendantIdsOf(int $deptId): array
    {
        $parentMap = static::where('is_deleted', false)
            ->pluck('parent_id', 'id')
            ->toArray();

        $result = [$deptId];
        $stack  = [$deptId];

        while (!empty($stack)) {
            $current = array_pop($stack);
            foreach ($parentMap as $id => $parentId) {
                if ((int) $parentId === $current) {
                    $result[] = $id;
                    $stack[]  = $id;
                }
            }
        }

        return array_unique($result);
    }

    /**
     * Returns IDs of all ancestors up to root (not including self).
     */
    public function ancestorIds(): array
    {
        $parentMap = static::where('is_deleted', false)
            ->pluck('parent_id', 'id')
            ->toArray();

        $result  = [];
        $current = $this->parent_id;

        while ($current !== null) {
            $result[] = $current;
            $current  = $parentMap[$current] ?? null;
        }

        return $result;
    }
}
