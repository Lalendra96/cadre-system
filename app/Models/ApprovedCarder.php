<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApprovedCarder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'approved_carders';

    protected $fillable = [
        'position_id', 'year', 'approved_amount',
        'ministry_reference_no', 'approved_date', 'remarks', 'created_by',
        'is_active', 'disabled_by', 'disabled_at', 'disable_reason',
    ];

    protected $casts = [
        'approved_date' => 'date',
        'disabled_at'   => 'datetime',
        'year'          => 'integer',
        'approved_amount' => 'integer',
        'is_active'     => 'boolean',
    ];

    // ── Relations ──────────────────────────────────────────────────────────

    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function disabledBy()
    {
        return $this->belongsTo(User::class, 'disabled_by');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    /**
     * Scope for a specific year — ACTIVE records only.
     * All reports and carry-forward calculations should use this scope so
     * disabled records are automatically excluded from operational figures.
     */
    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year)->where('is_active', true);
    }

    /**
     * Scope for a specific year including disabled records.
     * Used only by the management UI so the Director can see and re-enable.
     */
    public function scopeForYearAll($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
