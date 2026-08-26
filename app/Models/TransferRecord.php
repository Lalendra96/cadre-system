<?php

namespace App\Models;

use App\Traits\HasDisableWorkflow;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferRecord extends Model
{
    use HasDisableWorkflow, HasFactory;

    protected $table = 'transfer_records';

    protected $fillable = [
        'carder_entry_id', 'employee_id', 'employee_name', 'designation',
        'direction', 'transfer_type', 'from_location', 'to_location',
        'effective_date', 'notes', 'recorded_by',
        'psc_circular_no', 'transfer_board_ref_no', 'transfer_board_decision_date',
        'is_active', 'disabled_by', 'disabled_at', 'disable_reason',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'transfer_board_decision_date' => 'date',
        'is_active'      => 'boolean',
        'disabled_at'    => 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────────────────────

    public function carderEntry()
    {
        return $this->belongsTo(CarderMonthlyEntry::class, 'carder_entry_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeDirection($query, string $dir)
    {
        return $query->where('direction', $dir);
    }

    public function scopeIn($query)
    {
        return $query->direction('in');
    }

    public function scopeOut($query)
    {
        return $query->direction('out');
    }
}
