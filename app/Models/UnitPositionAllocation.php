<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents one cell in the Unit × Position headcount matrix.
 *
 * @property int    $unit_id
 * @property int    $position_id
 * @property int    $year
 * @property int    $allocated_posts    Planned posts for this unit/position
 * @property int    $actual_in_post     Current in-post (manually entered)
 * @property string $notes
 * @property int    $last_updated_by
 */
class UnitPositionAllocation extends Model
{
    protected $table = 'unit_position_allocations';

    protected $fillable = [
        'unit_id', 'position_id', 'position_subcategory_id', 'year',
        'allocated_posts', 'actual_in_post',
        'notes', 'last_updated_by',
    ];

    protected $casts = [
        'year'            => 'integer',
        'allocated_posts' => 'integer',
        'actual_in_post'  => 'integer',
    ];

    // ── Relations ──────────────────────────────────────────────────────────

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function subcategory()
    {
        return $this->belongsTo(PositionSubcategory::class, 'position_subcategory_id');
    }

    public function lastUpdatedBy()
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeForUnit($query, int $unitId)
    {
        return $query->where('unit_id', $unitId);
    }

    /** The position's own main-line row only — excludes subcategory breakdowns. */
    public function scopeMainLine($query)
    {
        return $query->whereNull('position_subcategory_id');
    }

    /** Subcategory breakdown rows only — excludes the main line. */
    public function scopeSubcategoryLines($query)
    {
        return $query->whereNotNull('position_subcategory_id');
    }

    // ── Computed ───────────────────────────────────────────────────────────

    public function getVacancyAttribute(): int
    {
        return max($this->allocated_posts - $this->actual_in_post, 0);
    }

    public function getFillPctAttribute(): int
    {
        if ($this->allocated_posts === 0) {
            return $this->actual_in_post > 0 ? 100 : 0;
        }
        return (int) round($this->actual_in_post / $this->allocated_posts * 100);
    }
}
