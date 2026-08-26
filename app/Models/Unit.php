<?php

namespace App\Models;

use App\Traits\HasDisableWorkflow;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use HasDisableWorkflow, HasFactory, SoftDeletes;

    protected $table = 'units';

    protected $fillable = [
        'name', 'code', 'location', 'unit_type_id', 'is_active',
        'disabled_by', 'disabled_at', 'disable_reason',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'disabled_at' => 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────────────────────

    public function unitType()
    {
        return $this->belongsTo(UnitType::class, 'unit_type_id');
    }

    public function employees()
    {
        return $this->hasMany(Employee::class, 'unit_id');
    }

    public function unitPositionAllocations()
    {
        return $this->hasMany(UnitPositionAllocation::class, 'unit_id');
    }

    /** Positions Super Admin has bound as relevant to this unit — see unit_position migration docblock. */
    public function boundPositions()
    {
        return $this->belongsToMany(Position::class, 'unit_position')->withTimestamps();
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->whereRaw('name ILIKE ?', ["%{$term}%"])
              ->orWhereRaw('code ILIKE ?', ["%{$term}%"]);
        });
    }
}
