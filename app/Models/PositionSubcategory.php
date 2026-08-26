<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasDisableWorkflow;
use Illuminate\Database\Eloquent\Model;

class PositionSubcategory extends Model
{
    use HasDisableWorkflow;

    protected $fillable = [
        'parent_position_id', 'name', 'code', 'description',
        'counts_toward_parent_total', 'sort_order', 'created_by',
        'is_active', 'disabled_by', 'disabled_at', 'disable_reason',
    ];

    protected $casts = [
        'counts_toward_parent_total' => 'boolean',
        'sort_order'                 => 'integer',
        'is_active'                  => 'boolean',
        'disabled_at'                => 'datetime',
    ];

    public function parentPosition()
    {
        return $this->belongsTo(Position::class, 'parent_position_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function allocations()
    {
        return $this->hasMany(UnitPositionAllocation::class, 'position_subcategory_id');
    }

    /** Subcategories that DON'T roll into the parent total — e.g. PGIM. */
    public function scopeExcludedFromTotal($query)
    {
        return $query->where('counts_toward_parent_total', false);
    }
}
