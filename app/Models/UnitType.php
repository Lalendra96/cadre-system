<?php

namespace App\Models;

use App\Traits\HasDisableWorkflow;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitType extends Model
{
    use HasDisableWorkflow, HasFactory;

    protected $table = 'unit_types';

    protected $fillable = [
        'name', 'code', 'description', 'is_active', 'sort_order',
        'disabled_by', 'disabled_at', 'disable_reason',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'sort_order'  => 'integer',
        'disabled_at' => 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────────────────────

    public function units()
    {
        return $this->hasMany(Unit::class, 'unit_type_id');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
