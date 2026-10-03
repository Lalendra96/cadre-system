<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Employee;
use App\Models\HrReassignmentCase;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HrWorkloadService
{
    public static function rows(User $viewer): Collection
    {
        $officers = User::active()
            ->havingRole(User::ROLE_SUBJECT_OFFICER)
            ->with('userRoles')
            ->when(! HrIntelligenceService::isManager($viewer), fn ($query) => $query->whereKey($viewer->id))
            ->orderBy('name')
            ->get();
        $employees = Employee::active()->get(['id', 'position_id', 'date_of_birth', 'retirement_age']);
        $ids = $employees->pluck('id');
        $quality = DB::table('data_quality_issues')
            ->whereIn('employee_id', $ids)
            ->where('status', '!=', 'verified')
            ->get(['employee_id', 'assigned_to']);
        $increments = DB::table('employee_increments')
            ->whereIn('employee_id', $ids)
            ->where('is_active', true)
            ->whereNull('granted_date')
            ->where(
                fn ($query) => $query
                    ->whereNull('workflow_status')
                    ->orWhereNotIn('workflow_status', ['granted', 'deferred', 'withheld']),
            )
            ->whereDate('increment_date', '<=', today()->addDays(30))
            ->get(['employee_id', 'responsible_user_id']);
        $retirements = DB::table('retirement_projects')
            ->whereIn('employee_id', $ids)
            ->where('status', '!=', 'completed')
            ->whereNull('completed_at')
            ->get(['employee_id', 'responsible_user_id']);
        $cases = HrReassignmentCase::whereIn('status', HrReassignmentCase::OPEN_STATUSES)->get([
            'employee_id',
            'proposed_user_id',
        ]);

        return $officers->map(function (User $officer) use ($employees, $quality, $increments, $retirements, $cases) {
            $allocatedIds = WorkforceScopeService::allocatedEmployeeIds($officer);
            $covered = $employees->whereIn('id', $allocatedIds->all());
            $positions = $covered->pluck('position_id')->filter()->unique()->values();
            $ids = $covered->pluck('id')->all();
            $q = $quality->whereIn('employee_id', $ids);
            $i = $increments->whereIn('employee_id', $ids);
            $r = $retirements->whereIn('employee_id', $ids);

            return [
                'officer' => $officer,
                'positions' => $positions->count(),
                'employees' => $covered->count(),
                'quality' => $q->count(),
                'increments' => $i->count(),
                'retirement_projects' => $r->count(),
                'retiring' => $covered
                    ->filter(
                        fn (Employee $employee) => $employee->retire_date &&
                            $employee->retire_date->betweenIncluded(today(), today()->addMonths(12)),
                    )
                    ->count(),
                'assigned_work' => $q->where('assigned_to', $officer->id)->count() +
                    $i->where('responsible_user_id', $officer->id)->count() +
                    $r->where('responsible_user_id', $officer->id)->count(),
                'acceptances' => $cases->whereIn('employee_id', $ids)->where('proposed_user_id', $officer->id)->count(),
            ];
        });
    }
}
