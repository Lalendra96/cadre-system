<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasDisableWorkflow;
use Illuminate\Database\Eloquent\Model;

class EmployeeLeaveRecord extends Model
{
    use HasDisableWorkflow;

    protected $fillable = [
        'employee_id', 'leave_type', 'start_date', 'expected_return_date',
        'actual_return_date', 'reference_no', 'notes', 'recorded_by',
        'is_active', 'disabled_by', 'disabled_at', 'disable_reason',
    ];

    protected $casts = [
        'start_date'            => 'date',
        'expected_return_date'  => 'date',
        'actual_return_date'    => 'date',
        'is_active'             => 'boolean',
        'disabled_at'           => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /** Currently away — no actual return date recorded yet. */
    public function scopeStillAway($query)
    {
        return $query->whereNull('actual_return_date');
    }

    /** Expected back within N days but hasn't returned yet — for reminders. */
    public function scopeOverdueOrDueSoon($query, int $daysAhead = 14)
    {
        return $query->stillAway()
            ->whereNotNull('expected_return_date')
            ->whereDate('expected_return_date', '<=', now()->addDays($daysAhead)->toDateString());
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->actual_return_date === null
            && $this->expected_return_date !== null
            && $this->expected_return_date->isPast();
    }
}
