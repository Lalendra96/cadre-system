<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeHrAllocation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class WorkforceScopeService
{
    public static function employeeQuery(User $user): Builder
    {
        $query = Employee::query();
        if (!$user->is_active) return $query->whereRaw('1 = 0');
        if ($user->isSuperAdmin() || $user->isPlanningOfficer()) return $query;
        if ($user->isAdminGroup()) return $query->whereRaw('1 = 0');
        if ($user->isSubjectOfficer()) return $query->whereIn('id', self::allocatedEmployeeIds($user)->all());
        return $query->whereRaw('1 = 0');
    }
    public static function allocatedEmployeeIds(User $user): \Illuminate\Support\Collection
    {
        if (!$user->is_active || !$user->isSubjectOfficer()) return collect();
        return EmployeeHrAllocation::query()->effective()->where('user_id',$user->id)->whereIn('position_id',$user->effectiveHrPositionIds()->all())->pluck('employee_id')->unique()->values();
    }
    public static function isEmployeeAllocatedTo(User $user, Employee $employee): bool
    {
        if (!$user->is_active || !$user->isSubjectOfficer() || !$employee->position_id) return false;
        return EmployeeHrAllocation::query()->effective()->where('employee_id',$employee->id)->where('user_id',$user->id)->where('position_id',$employee->position_id)->exists();
    }
    public static function authorizeEmployee(User $user, Employee $employee): void
    {
        abort_unless($user->is_active,403);
        if ($user->isSuperAdmin() || $user->isPlanningOfficer()) return;
        if ($user->isAdminGroup()) abort(403,'Administrative users may view employee counts only; individual Employee Profiles are restricted.');
        abort_unless($user->isSubjectOfficer() && self::isEmployeeAllocatedTo($user,$employee),403,'You may only access Employee Profiles specifically allocated to your account.');
    }
}
