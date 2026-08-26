<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDisableToggle;
use App\Models\ActingAppointment;
use App\Models\Employee;
use App\Models\Position;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Acting Appointment records — who is acting in which position, since
 * when. See App\Services\ActingAllowanceCalculator for the computed
 * allowance this ties into, and PositionActingAllowanceRuleController for
 * the Super-Admin-only rate configuration behind that calculation.
 *
 * DATA SECURITY — like TransferRecordController, this previously had NO
 * per-record authorization. Fixed with the same
 * User::canManageEmployeeHrRecord() single source of truth. Unlike
 * transfers, employee_id here is always required (never a free-text
 * name), so every check has a real Employee to scope against.
 */
class ActingAppointmentController extends Controller
{
    use HandlesDisableToggle;

    public function index(Request $request)
    {
        $user = $request->user();

        $currentQuery = ActingAppointment::current()
            ->active()
            ->with(['employee', 'actingPosition', 'substantivePosition']);
        $historicalQuery = ActingAppointment::where(function ($q) {
            $q->where('is_active', false)
              ->orWhere('end_date', '<', now());
        })->with(['employee', 'actingPosition']);

        if ($user->isSubjectOfficer() && ! $user->isSuperAdmin() && ! $user->isPlanningOfficer() && ! $user->isHrRecordsManager()) {
            $codeIds = $user->effectiveSubjectCodeIds();
            $currentQuery->whereHas('employee', fn ($q) => $q->whereIn('subject_code_id', $codeIds));
            $historicalQuery->whereHas('employee', fn ($q) => $q->whereIn('subject_code_id', $codeIds));
        }

        $current    = $currentQuery->orderByDesc('start_date')->paginate(20);
        $historical = $historicalQuery->latest()->paginate(10);

        return view('acting-appointments.index', compact('current', 'historical'));
    }

    public function create(Request $request)
    {
        $employees = $this->assignableEmployees($request->user());
        $positions = Position::active()->orderBy('title')->get(['id', 'title']);

        return view('acting-appointments.form', [
            'appointment' => new ActingAppointment(),
            'employees'   => $employees,
            'positions'   => $positions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id'             => ['required', 'integer', 'exists:employees,id'],
            'acting_position_id'      => ['required', 'integer', 'exists:positions,id'],
            'substantive_position_id' => ['required', 'integer', 'exists:positions,id', 'different:acting_position_id'],
            'start_date'              => ['required', 'date'],
            'end_date'                => ['nullable', 'date', 'after:start_date'],
            'appointment_order_no'    => ['nullable', 'string', 'max:60'],
            'remarks'                 => ['nullable', 'string', 'max:500'],
        ]);

        $employee = Employee::find($data['employee_id']);
        abort_unless(
            $request->user()->canManageEmployeeHrRecord($employee),
            403,
            'You may only record acting appointments for employees under your assigned subject codes, '
            . 'or if your account holds an authorised Admin Group category.'
        );

        $data['created_by'] = $request->user()->id;
        $data['is_active']  = true;

        $appt = ActingAppointment::create($data);

        AuditLogService::created(
            $appt,
            "Acting appointment: {$appt->employee->name} acting as {$appt->actingPosition->title}"
        );

        return redirect()->route('acting-appointments.index')
            ->with('success', 'Acting appointment recorded.');
    }

    public function edit(Request $request, ActingAppointment $actingAppointment)
    {
        $this->authorizeRecord($request, $actingAppointment);

        $employees = $this->assignableEmployees($request->user());
        $positions = Position::active()->orderBy('title')->get(['id', 'title']);

        return view('acting-appointments.form', [
            'appointment' => $actingAppointment,
            'employees'   => $employees,
            'positions'   => $positions,
        ]);
    }

    public function update(Request $request, ActingAppointment $actingAppointment): RedirectResponse
    {
        $this->authorizeRecord($request, $actingAppointment);

        $data = $request->validate([
            'end_date'             => ['nullable', 'date', 'after:start_date'],
            'appointment_order_no' => ['nullable', 'string', 'max:60'],
            'remarks'              => ['nullable', 'string', 'max:500'],
            'is_active'            => ['boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $old = $actingAppointment->getOriginal();
        $actingAppointment->update($data);

        AuditLogService::updated($actingAppointment, $old, 'Acting appointment updated');

        return redirect()->route('acting-appointments.index')
            ->with('success', 'Acting appointment updated.');
    }

    public function toggle(Request $request, ActingAppointment $actingAppointment): RedirectResponse
    {
        $this->authorizeRecord($request, $actingAppointment);

        $name = $actingAppointment->employee->name ?? 'appointment';

        return $this->performToggle(
            request:       $request,
            model:         $actingAppointment,
            label:         "Acting appointment for \"{$name}\"",
            requireReason: false,
        );
    }

    private function authorizeRecord(Request $request, ActingAppointment $actingAppointment): void
    {
        abort_unless(
            $request->user()->canManageEmployeeHrRecord($actingAppointment->employee),
            403,
            'You may only manage acting appointments for employees under your assigned subject codes, '
            . 'or if your account holds an authorised Admin Group category.'
        );
    }

    private function assignableEmployees(\App\Models\User $user)
    {
        if ($user->isSuperAdmin() || $user->isPlanningOfficer() || $user->isHrRecordsManager()) {
            return Employee::active()->orderBy('name')->get(['id', 'name', 'pay_no']);
        }

        return Employee::active()
            ->whereIn('subject_code_id', $user->effectiveSubjectCodeIds())
            ->orderBy('name')
            ->get(['id', 'name', 'pay_no']);
    }
}
