<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasDisableWorkflow;
use Illuminate\Database\Eloquent\Model;

/**
 * A single grade-history entry for one employee. Never mutated once
 * created except to set end_date when superseded by a newer record —
 * see EmployeeGradeController::store() for how the previous "current"
 * record is closed out automatically.
 */
class EmployeeGradeRecord extends Model
{
    use HasDisableWorkflow;

    protected $fillable = [
        'employee_id', 'position_grade_id', 'effective_date', 'end_date',
        'reference_no', 'notes', 'recorded_by',
        'is_active', 'disabled_by', 'disabled_at', 'disable_reason',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'end_date'       => 'date',
        'is_active'      => 'boolean',
        'disabled_at'    => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function positionGrade()
    {
        return $this->belongsTo(PositionGrade::class, 'position_grade_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /** Records with no end_date, or an end_date in the future — i.e. currently held. */
    public function scopeCurrent($query)
    {
        return $query->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString()));
    }
}
