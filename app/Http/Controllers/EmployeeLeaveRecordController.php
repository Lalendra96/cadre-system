<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDisableToggle;
use App\Models\Employee;
use App\Models\EmployeeLeaveRecord;
use App\Services\AdministrativeDecisionService;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeLeaveRecordController extends Controller
{
    use HandlesDisableToggle;

    public function index(
        Request $request,
        Employee $employee
    ) {
        $this->authorizeEmployee(
            $request,
            $employee
        );

        $records = $employee->leaveRecords()
            ->active()
            ->with('recordedBy')
            ->orderByDesc('start_date')
            ->paginate(15);

        return view(
            'employee-leave-records.index',
            compact(
                'employee',
                'records'
            )
        );
    }

    public function create(
        Request $request,
        Employee $employee
    ) {
        $this->authorizeEmployee(
            $request,
            $employee
        );

        return view(
            'employee-leave-records.form',
            compact('employee')
        );
    }

    public function store(
        Request $request,
        Employee $employee
    ): RedirectResponse {
        $this->authorizeEmployee(
            $request,
            $employee
        );

        $data = $request->validate([
            'leave_type' => [
                'required',
                Rule::in([
                    'no_pay_leave',
                    'other',
                ]),
            ],
            'start_date' => [
                'required',
                'date',
            ],
            'expected_return_date' => [
                'nullable',
                'date',
                'after:start_date',
            ],
            'actual_return_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],
            'reference_no' => [
                'nullable',
                'string',
                'max:100',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:500',
            ],
        ]);

        $decision = AdministrativeDecisionService::request(
            type: 'leave_record',
            employee: $employee,
            payload: $data,
            requester: $request->user(),
            summary: 'Proposed '
                . str_replace('_', ' ', $data['leave_type'])
                . ' record for '
                . $employee->display_name
                . ' starting '
                . $data['start_date'],
            sourceReference: $data['reference_no'] ?? null
        );

        return redirect()
            ->route(
                'administrative-decisions.show',
                $decision
            )
            ->with(
                'success',
                'Leave record submitted for independent human approval. The record is not active until approved.'
            );
    }

    public function markReturned(
        Request $request,
        Employee $employee,
        EmployeeLeaveRecord $record
    ): RedirectResponse {
        $this->authorizeEmployee(
            $request,
            $employee
        );

        abort_unless(
            $record->employee_id === $employee->id,
            404
        );

        abort_unless(
            $record->actual_return_date === null,
            400,
            'This record is already marked as returned.'
        );

        $data = $request->validate([
            'actual_return_date' => [
                'required',
                'date',
                'after_or_equal:'
                    . $record->start_date->toDateString(),
            ],
        ]);

        $old = $record->getOriginal();

        $record->update([
            'actual_return_date' => $data['actual_return_date'],
        ]);

        AuditLogService::updated(
            $record,
            $old,
            'Recorded factual return-from-leave date for '
                . $employee->display_name
                . '.'
        );

        return redirect()
            ->route(
                'employee-leave-records.index',
                $employee
            )
            ->with(
                'success',
                'Return-from-leave date recorded with correction history.'
            );
    }

    public function toggle(
        Request $request,
        Employee $employee,
        EmployeeLeaveRecord $record
    ): RedirectResponse {
        $this->authorizeEmployee(
            $request,
            $employee
        );

        abort_unless(
            $record->employee_id === $employee->id,
            404
        );

        return $this->performToggle(
            request: $request,
            model: $record,
            label: 'Leave record starting '
                . $record->start_date->format('d M Y'),
            requireReason: true
        );
    }

    private function authorizeEmployee(
        Request $request,
        Employee $employee
    ): void {
        abort_unless(
            $request
                ->user()
                ->canManageEmployeeHrRecord(
                    $employee
                ),
            403,
            'You may only manage leave records for Employee Profiles within your authorised HR scope.'
        );
    }
}
