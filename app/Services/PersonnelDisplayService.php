<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Collection;

class PersonnelDisplayService
{
    /**
     * Hydrate decrypted employee display fields onto DB-query rows without
     * ever asking PostgreSQL to return encrypted PII as a display value.
     */
    public static function hydrateEmployeeRows(Collection $rows, string $employeeIdProperty = 'employee_id'): Collection
    {
        $ids = $rows->pluck($employeeIdProperty)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return $rows;
        }

        $employees = Employee::query()->whereIn('id', $ids)->get(['id', 'name', 'pay_no'])->keyBy('id');

        return $rows->map(function ($row) use ($employees, $employeeIdProperty) {
            $employee = $employees->get($row->{$employeeIdProperty} ?? null);
            $row->employee_name = $employee?->name;
            $row->pay_no = $employee?->pay_no;
            return $row;
        });
    }
}
