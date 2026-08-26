<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasDisableWorkflow;
use Illuminate\Database\Eloquent\Model;

class EmployeeInterdiction extends Model
{
    use HasDisableWorkflow;

    public const OUTCOME_LABELS = [
        'reinstated' => 'Reinstated',
        'dismissed'  => 'Dismissed',
        'resigned'   => 'Resigned',
        'other'      => 'Other',
    ];

    protected $fillable = [
        'employee_id', 'interdiction_date', 'reason', 'inquiry_reference_no',
        'inquiry_status', 'reinstatement_date', 'outcome', 'notes', 'recorded_by',
        'is_active', 'disabled_by', 'disabled_at', 'disable_reason',
    ];

    protected $casts = [
        'interdiction_date'  => 'date',
        'reinstatement_date' => 'date',
        'is_active'          => 'boolean',
        'disabled_at'        => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopeOngoing($query)
    {
        return $query->where('inquiry_status', 'ongoing');
    }
}
