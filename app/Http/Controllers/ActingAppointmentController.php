<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDisableToggle;
use App\Models\ActingAppointment;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use App\Services\AdministrativeDecisionService;
use App\Services\AuditLogService;
use App\Services\WorkforceScopeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ActingAppointmentController extends Controller
{
    use HandlesDisableToggle;

    public function index(Request $request)
    {
        $user = $request->user();

        $currentQuery = ActingAppointment::current()
            ->active()
            ->with([
                'employee',
                'actingPosition',
                'substantivePosition',
            ]);

        $historicalQuery = ActingAppointment::where(
            function ($query) {
                $query->where('is_active', false)
                    ->orWhere(
                        'end_date',
                        '<',
                        now()
                    );
            }
        )->with([
            'employee',
            'actingPosition',
        ]);

        if (
            $user->isSubjectOfficer()
            && ! $user->isSuperAdmin()
            && ! $user->isPlanningOfficer()
            && ! $user->isHrRecordsManager()
        ) {
            $employeeIds = WorkforceScopeService::allocatedEmployeeIds(
                $user
            );

            $currentQuery->whereIn(
                'employee_id',
                $employeeIds
            );

            $historicalQuery->whereIn(
                'employee_id',
                $employeeIds
            );
        }

        $current = $currentQuery
            ->orderByDesc('start_date')
            ->paginate(20);

        $historical = $historicalQuery
            ->latest()
            ->paginate(10);

        return view(
            'acting-appointments.index',
            compact(
                'current',
                'historical'
            )
        );
    }

    public function create(Request $request)
    {
        return view(
            'acting-appointments.form',
            [
                'appointment' => new ActingAppointment(),
                'employees' => $this->assignableEmployees(
                    $request->user()
                ),
                'positions' => Position::active()
                    ->orderBy('title')
                    ->get([
                        'id',
                        'title',
                    ]),
            ]
        );
    }

    public function store(
        Request $request
    ): RedirectResponse {
        $data = $request->validate([
            'employee_id' => [
                'required',
                'integer',
                'exists:employees,id',
            ],
            'acting_position_id' => [
                'required',
                'integer',
                'exists:positions,id',
            ],
            'substantive_position_id' => [
                'required',
                'integer',
                'exists:positions,id',
                'different:acting_position_id',
            ],
            'start_date' => [
                'required',
                'date',
            ],
            'end_date' => [
                'nullable',
                'date',
                'after:start_date',
            ],
            'appointment_order_no' => [
                'nullable',
                'string',
                'max:60',
            ],
            'remarks' => [
                'nullable',
                'string',
                'max:500',
            ],
        ]);

        $employee = Employee::findOrFail(
            $data['employee_id']
        );

        abort_unless(
            $request
                ->user()
                ->canManageEmployeeHrRecord(
                    $employee
                ),
            403
        );

        $decision = AdministrativeDecisionService::request(
            type: 'acting_appointment',
            employee: $employee,
            payload: $data,
            requester: $request->user(),
            summary: 'Proposed acting appointment for '
                . $employee->display_name
                . ' starting '
                . $data['start_date'],
            sourceReference: $data['appointment_order_no'] ?? null
        );

        return redirect()
            ->route(
                'administrative-decisions.show',
                $decision
            )
            ->with(
                'success',
                'Acting appointment submitted for independent human approval. It is not active until approved.'
            );
    }

    public function edit(
        Request $request,
        ActingAppointment $actingAppointment
    ) {
        $this->authorizeRecord(
            $request,
            $actingAppointment
        );

        return view(
            'acting-appointments.form',
            [
                'appointment' => $actingAppointment,
                'employees' => $this->assignableEmployees(
                    $request->user()
                ),
                'positions' => Position::active()
                    ->orderBy('title')
                    ->get([
                        'id',
                        'title',
                    ]),
            ]
        );
    }

    public function update(
        Request $request,
        ActingAppointment $actingAppointment
    ): RedirectResponse {
        $this->authorizeRecord(
            $request,
            $actingAppointment
        );

        $data = $request->validate([
            'end_date' => [
                'nullable',
                'date',
                'after:start_date',
            ],
            'appointment_order_no' => [
                'nullable',
                'string',
                'max:60',
            ],
            'remarks' => [
                'nullable',
                'string',
                'max:500',
            ],
            'is_active' => [
                'boolean',
            ],
        ]);

        $data['is_active'] = $request->boolean(
            'is_active'
        );

        $old = $actingAppointment->getOriginal();

        $actingAppointment->update($data);

        AuditLogService::updated(
            $actingAppointment,
            $old,
            'Corrected an approved acting appointment.'
        );

        return redirect()
            ->route('acting-appointments.index')
            ->with(
                'success',
                'Approved acting appointment updated. Correction history is retained.'
            );
    }

    public function toggle(
        Request $request,
        ActingAppointment $actingAppointment
    ): RedirectResponse {
        $this->authorizeRecord(
            $request,
            $actingAppointment
        );

        $name = $actingAppointment->employee->name
            ?? 'appointment';

        return $this->performToggle(
            request: $request,
            model: $actingAppointment,
            label: 'Acting appointment for "'
                . $name
                . '"',
            requireReason: true
        );
    }

    private function authorizeRecord(
        Request $request,
        ActingAppointment $actingAppointment
    ): void {
        abort_unless(
            $request
                ->user()
                ->canManageEmployeeHrRecord(
                    $actingAppointment->employee
                ),
            403
        );
    }

    private function assignableEmployees(User $user)
    {
        if (
            $user->isSuperAdmin()
            || $user->isPlanningOfficer()
            || $user->isHrRecordsManager()
        ) {
            return Employee::active()
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'pay_no',
                ]);
        }

        return WorkforceScopeService::employeeQuery(
            $user
        )
            ->active()
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'pay_no',
            ]);
    }
}
