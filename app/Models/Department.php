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

    // Per-request cache of the active parent map (id => parent_id). Multiple controllers call
    // descendantIdsOf/ancestorIds within a single request; without this each call re-queried.
    private static ?array $parentMapCache    = null;
    private static ?array $childrenMapCache  = null;

    private static function parentMap(): array
    {
        if (self::$parentMapCache === null) {
            self::$parentMapCache = static::where('is_deleted', false)
                ->pluck('parent_id', 'id')
                ->toArray();
        }
        return self::$parentMapCache;
    }

    private static function childrenMap(): array
    {
        if (self::$childrenMapCache === null) {
            $children = [];
            foreach (self::parentMap() as $id => $parentId) {
                $pid = (int) $parentId;
                $children[$pid][] = (int) $id;
            }
            self::$childrenMapCache = $children;
        }
        return self::$childrenMapCache;
    }

    /**
     * Clears the per-request cache. Called automatically when departments are saved/deleted.
     */
    public static function flushHierarchyCache(): void
    {
        self::$parentMapCache   = null;
        self::$childrenMapCache = null;
    }

    protected static function booted(): void
    {
        static::saved(fn() => self::flushHierarchyCache());
        static::deleted(fn() => self::flushHierarchyCache());
    }

    /**
     * Returns IDs of this department and all its descendants (recursive).
     */
    public function selfAndDescendantIds(): array
    {
        return static::descendantIdsOf($this->id);
    }

    /**
     * Returns IDs of the given department and all its descendants.
     * O(n) using a precomputed children map, cached for the request.
     */
    public static function descendantIdsOf(int $deptId): array
    {
        $childrenMap = self::childrenMap();
        $result = [$deptId];
        $stack  = [$deptId];

        while (!empty($stack)) {
            $current = array_pop($stack);
            foreach ($childrenMap[$current] ?? [] as $childId) {
                $result[] = $childId;
                $stack[]  = $childId;
            }
        }

        return array_values(array_unique($result));
    }

    /**
     * Returns IDs of all ancestors up to root (not including self).
     */
    public function ancestorIds(): array
    {
        $parentMap = self::parentMap();
        $result    = [];
        $current   = $this->parent_id;

        while ($current !== null) {
            $result[] = (int) $current;
            $current  = $parentMap[$current] ?? null;
        }

        return $result;
    }
}
