<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasDisableWorkflow;
use Illuminate\Database\Eloquent\Model;

/**
 * A single increment-history entry for one employee. See
 * App\Console\Commands\NotifyUpcomingIncrements for the 30-day-before
 * reminder that reads scopeDueForReminder() below.
 */
class EmployeeIncrement extends Model
{
    use HasDisableWorkflow;

    protected $fillable = [
        'employee_id', 'increment_date', 'amount', 'reference_no', 'notes',
        'notified_at', 'recorded_by',
        'is_active', 'disabled_by', 'disabled_at', 'disable_reason',
    ];

    protected $casts = [
        'increment_date' => 'date',
        'amount'         => 'decimal:2',
        'notified_at'    => 'datetime',
        'is_active'      => 'boolean',
        'disabled_at'    => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Increments falling exactly `daysAhead` days from today (or earlier,
     * to catch any missed by a scheduler outage) that have not yet been
     * notified. Used by the daily scheduled command.
     */
    public function scopeDueForReminder($query, int $daysAhead = 30)
    {
        return $query->active()
            ->whereNull('notified_at')
            ->whereDate('increment_date', '<=', now()->addDays($daysAhead)->toDateString())
            ->whereDate('increment_date', '>=', now()->toDateString());
    }

    /** The employee's most recent past-or-today increment. */
    public function scopeMostRecent($query)
    {
        return $query->active()->orderByDesc('increment_date');
    }
}
