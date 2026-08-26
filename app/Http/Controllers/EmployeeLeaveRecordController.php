<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDisableToggle;
use App\Models\Employee;
use App\Models\EmployeeLeaveRecord;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Leave Without Pay (LWOP) history per employee, with an expected return
 * date — see migration docblock for how this differs from the aggregate
 * monthly no_pay_leave COUNT already on carder_monthly_entries.
 */
class EmployeeLeaveRecordController extends Controller
{
    use HandlesDisableToggle;

    public function index(Request $request, Employee $employee)
    {
        $this->authorizeEmployee($request, $employee);

        $records = $employee->leaveRecords()
            ->active()
            ->with('recordedBy')
            ->orderByDesc('start_date')
            ->paginate(15);

        return view('employee-leave-records.index', compact('employee', 'records'));
    }

    public function create(Request $request, Employee $employee)
    {
        $this->authorizeEmployee($request, $employee);

        return view('employee-leave-records.form', compact('employee'));
    }

    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorizeEmployee($request, $employee);

        $data = $request->validate([
            'leave_type'            => ['required', Rule::in(['no_pay_leave', 'other'])],
            'start_date'            => ['required', 'date'],
            'expected_return_date'  => ['nullable', 'date', 'after:start_date'],
            'actual_return_date'    => ['nullable', 'date', 'after_or_equal:start_date'],
            'reference_no'          => ['nullable', 'string', 'max:100'],
            'notes'                 => ['nullable', 'string', 'max:500'],
        ]);

        $data['employee_id'] = $employee->id;
        $data['recorded_by'] = $request->user()->id;
        $data['is_active']   = true;

        $record = EmployeeLeaveRecord::create($data);

        AuditLogService::created(
            $record,
            "Recorded {$record->leave_type} for {$employee->display_name} starting {$record->start_date->format('d M Y')}"
        );

        return redirect()->route('employee-leave-records.index', $employee)
            ->with('success', 'Leave record added.');
    }

    /** Officer marks an employee as returned — sets actual_return_date without a full edit form. */
    public function markReturned(Request $request, Employee $employee, EmployeeLeaveRecord $record): RedirectResponse
    {
        $this->authorizeEmployee($request, $employee);
        abort_unless($record->employee_id === $employee->id, 404);
        abort_unless($record->actual_return_date === null, 400, 'This record is already marked as returned.');

        $data = $request->validate([
            'actual_return_date' => ['required', 'date', 'after_or_equal:' . $record->start_date->toDateString()],
        ]);

        $old = $record->getOriginal();
        $record->update(['actual_return_date' => $data['actual_return_date']]);

        AuditLogService::updated($record, $old, "Marked {$employee->display_name} as returned from leave on {$data['actual_return_date']}");

        return redirect()->route('employee-leave-records.index', $employee)
            ->with('success', 'Marked as returned.');
    }

    public function toggle(Request $request, Employee $employee, EmployeeLeaveRecord $record): RedirectResponse
    {
        $this->authorizeEmployee($request, $employee);
        abort_unless($record->employee_id === $employee->id, 404);

        return $this->performToggle(
            request:       $request,
            model:         $record,
            label:         "Leave record starting {$record->start_date->format('d M Y')}",
            requireReason: true,
        );
    }

    private function authorizeEmployee(Request $request, Employee $employee): void
    {
        abort_unless(
            $request->user()->canManageEmployeeHrRecord($employee),
            403,
            'You may only manage leave records for employees under your assigned subject codes, '
            . 'or if your account holds an authorised Admin Group category (Director, Deputy Director General, '
            . 'Deputy Director, Administrative Officer, or Chief Clerk).'
        );
    }
}
