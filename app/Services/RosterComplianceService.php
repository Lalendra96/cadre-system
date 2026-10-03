<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeCompetency;
use App\Models\RosterAvailability;
use App\Models\RosterAssignment;
use App\Models\RosterStaffingRule;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RosterComplianceService
{
    public function evaluateEmployee(
        int $employeeId,
        string $date,
        string $start,
        string $end,
        int|array|null $excludeAssignmentId = null,
        ?int $dutyUnitId = null,
        ?int $positionId = null
    ): array {
        $warnings = [];
        $hardStops = [];
        $employee = Employee::findOrFail($employeeId);
        $dutyStart = Carbon::parse($date.' '.$start);
        $dutyEnd = Carbon::parse($date.' '.$end);
        if ($dutyEnd->lte($dutyStart)) {
            $dutyEnd->addDay();
        }

        // Check duties across the previous/current/next day so overnight shifts
        // cannot evade overlap detection through a simple same-date SQL comparison.
        $windowStart = Carbon::parse($date)->subDay()->toDateString();
        $windowEnd = Carbon::parse($date)->addDay()->toDateString();
        $overlaps = RosterAssignment::query()
            ->where('employee_id', $employeeId)
            ->whereBetween('duty_date', [$windowStart, $windowEnd])
            ->when($excludeAssignmentId, function ($q) use ($excludeAssignmentId) {
                $ids = is_array($excludeAssignmentId) ? $excludeAssignmentId : [$excludeAssignmentId];
                return $q->whereNotIn('id', array_map('intval', $ids));
            })
            ->whereNotIn('status', ['cancelled'])
            ->get()
            ->contains(function ($existing) use ($dutyStart, $dutyEnd) {
                $existingStart = Carbon::parse($existing->duty_date->format('Y-m-d').' '.$existing->start_time);
                $existingEnd = Carbon::parse($existing->duty_date->format('Y-m-d').' '.$existing->end_time);
                if ($existingEnd->lte($existingStart)) $existingEnd->addDay();
                return $existingStart->lt($dutyEnd) && $existingEnd->gt($dutyStart);
            });
        if ($overlaps) {
            $hardStops[] = 'Employee already has an overlapping roster duty.';
        }

        // Digital leave is only authoritative when the module has been formally enabled.
        if (FeatureToggleService::enabled('leave_management')) {
            $onLeave = DB::table('leave_requests')
                ->where('employee_id', $employeeId)
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->exists();
            if ($onLeave) {
                $hardStops[] = 'Employee is on approved digital leave for this date.';
            }
        }

        $availability = RosterAvailability::query()
            ->where('employee_id', $employeeId)
            ->whereDate('available_date', $date)
            ->first();
        if ($availability && $availability->availability_type === 'unavailable') {
            $hardStops[] = 'Employee marked this date as unavailable.';
        } elseif ($availability && $availability->available_from && $availability->available_to) {
            if ($start < $availability->available_from || $end > $availability->available_to) {
                $warnings[] = 'Duty falls outside the employee\'s stated availability window.';
            }
        }

        $contractExpired = DB::table('employee_contracts')
            ->where('employee_id', $employeeId)
            ->where('is_active', true)
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', $date)
            ->exists();
        if ($contractExpired) {
            $hardStops[] = 'Employee contract is expired for this duty date.';
        }

        if ($employee->professional_registration_expiry && Carbon::parse($employee->professional_registration_expiry)->lt(Carbon::parse($date))) {
            $hardStops[] = 'Professional registration is expired for this duty date.';
        }

        $minRest = (int) (SystemSetting::where('key', 'roster.minimum_rest_hours')->value('value') ?: 10);
        $previous = RosterAssignment::query()
            ->where('employee_id', $employeeId)
            ->whereDate('duty_date', '>=', Carbon::parse($date)->subDays(3)->toDateString())
            ->whereDate('duty_date', '<=', $date)
            ->when($excludeAssignmentId, function ($q) use ($excludeAssignmentId) {
                $ids = is_array($excludeAssignmentId) ? $excludeAssignmentId : [$excludeAssignmentId];
                return $q->whereNotIn('id', array_map('intval', $ids));
            })
            ->whereNotIn('status', ['cancelled'])
            ->get()
            ->map(function ($row) {
                $finish = Carbon::parse($row->duty_date->format('Y-m-d').' '.$row->end_time);
                $startAt = Carbon::parse($row->duty_date->format('Y-m-d').' '.$row->start_time);
                if ($finish->lte($startAt)) $finish->addDay();
                return ['row'=>$row,'finish'=>$finish];
            })
            ->filter(fn ($entry) => $entry['finish']->lte($dutyStart))
            ->sortByDesc(fn ($entry) => $entry['finish']->timestamp)
            ->first();
        if ($previous) {
            $restHours = $previous['finish']->diffInMinutes($dutyStart) / 60;
            if ($restHours < $minRest) {
                $warnings[] = sprintf('Only %.1f hours rest since the previous duty; configured minimum is %d hours.', $restHours, $minRest);
            }
        }

        $maxWeekly = (int) (SystemSetting::where('key', 'roster.max_weekly_hours')->value('value') ?: 48);
        $weekStart = Carbon::parse($date)->startOfWeek();
        $weekEnd = Carbon::parse($date)->endOfWeek();
        $minutes = RosterAssignment::query()
            ->where('employee_id', $employeeId)
            ->whereBetween('duty_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->when($excludeAssignmentId, function ($q) use ($excludeAssignmentId) {
                $ids = is_array($excludeAssignmentId) ? $excludeAssignmentId : [$excludeAssignmentId];
                return $q->whereNotIn('id', array_map('intval', $ids));
            })
            ->get()->sum(function ($a) {
                $s = Carbon::parse($a->duty_date->format('Y-m-d').' '.$a->start_time);
                $e = Carbon::parse($a->duty_date->format('Y-m-d').' '.$a->end_time);
                if ($e->lte($s)) $e->addDay();
                return $s->diffInMinutes($e);
            });
        $minutes += $dutyStart->diffInMinutes($dutyEnd);
        if ($minutes > $maxWeekly * 60) {
            $warnings[] = sprintf('Weekly roster hours would become %.1f, above the configured %d-hour warning threshold.', $minutes / 60, $maxWeekly);
        }

        // Competency / skill-mix rules are evaluated against the duty unit and slot.
        if ($dutyUnitId) {
            $dow = Carbon::parse($date)->dayOfWeek;
            $rules = RosterStaffingRule::query()
                ->where('unit_id', $dutyUnitId)
                ->where('is_active', true)
                ->whereNotNull('competency_id')
                ->where(fn ($q) => $q->whereNull('day_of_week')->orWhere('day_of_week', $dow))
                ->where('start_time', '<', $end)
                ->where('end_time', '>', $start)
                ->when($positionId, fn ($q) => $q->where(fn ($p) => $p->whereNull('position_id')->orWhere('position_id', $positionId)))
                ->get();

            foreach ($rules as $rule) {
                $hasCompetency = EmployeeCompetency::query()
                    ->where('employee_id', $employeeId)
                    ->where('competency_id', $rule->competency_id)
                    ->where(fn ($q) => $q->whereNull('expires_on')->orWhereDate('expires_on', '>=', $date))
                    ->exists();
                if (!$hasCompetency) {
                    $message = 'Employee does not hold the active competency required by a safe-staffing rule for this duty slot.';
                    if ($rule->is_hard_stop) $hardStops[] = $message;
                    else $warnings[] = $message;
                }
            }
        }

        $holiday = ReferenceDataCacheService::publicHolidayOn($date);
        if ($holiday) {
            $warnings[] = 'Public holiday: '.$holiday->name.'. Check applicable holiday duty/OT policy.';
        }

        return ['hard_stops' => array_values(array_unique($hardStops)), 'warnings' => array_values(array_unique($warnings)), 'allowed' => empty($hardStops)];
    }

    public function staffingIssues(int $unitId, string $date): Collection
    {
        $dow = Carbon::parse($date)->dayOfWeek;
        return RosterStaffingRule::query()
            ->where('unit_id', $unitId)->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('day_of_week')->orWhere('day_of_week', $dow))
            ->with(['position','competency'])
            ->get()->map(function ($rule) use ($unitId, $date) {
                $query = RosterAssignment::query()
                    ->where('duty_unit_id', $unitId)->whereDate('duty_date', $date)
                    ->whereNotIn('status', ['cancelled'])
                    ->when($rule->position_id, fn ($q) => $q->where('position_id', $rule->position_id))
                    ->where('start_time', '<', $rule->end_time)->where('end_time', '>', $rule->start_time);

                if ($rule->competency_id) {
                    $query->whereHas('employee.competencies', function ($q) use ($rule, $date) {
                        $q->where('competency_id', $rule->competency_id)
                            ->where(fn ($e) => $e->whereNull('expires_on')->orWhereDate('expires_on', '>=', $date));
                    });
                }

                $count = $query->count();
                return ['rule' => $rule, 'actual' => $count, 'shortfall' => max(0, $rule->minimum_staff - $count)];
            })->filter(fn ($row) => $row['shortfall'] > 0)->values();
    }
}
