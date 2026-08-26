<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasDisableWorkflow;
use Illuminate\Database\Eloquent\Model;

class PositionCategory extends Model
{
    use HasDisableWorkflow;

    protected $fillable = [
        'name', 'description', 'parent_id', 'sort_order', 'created_by',
        'is_active', 'disabled_by', 'disabled_at', 'disable_reason',
    ];

    protected $casts = [
        'sort_order'  => 'integer',
        'is_active'   => 'boolean',
        'disabled_at' => 'datetime',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function positions()
    {
        return $this->hasMany(Position::class, 'category_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isMainCategory(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Total active positions in this category — if it's a Main Category,
     * this sums across every one of its subcategories too, not just
     * positions attached directly to it (a Main Category itself normally
     * has no positions attached directly — see PositionCategoryController
     * docblock for why direct-to-main attachment is disallowed).
     */
    public function totalPositionCount(): int
    {
        if ($this->isMainCategory()) {
            return $this->positions()->count()
                + Position::whereIn('category_id', $this->children()->pluck('id'))->count();
        }
        return $this->positions()->count();
    }

    public function scopeMainCategories($query)
    {
        return $query->whereNull('parent_id');
    }
}
