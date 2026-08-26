<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDisableToggle;
use App\Models\Employee;
use App\Models\EmployeeInterdiction;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Interdiction / disciplinary inquiry history per employee. A sensitive
 * HR record — visible/manageable by the same audience as other employee
 * history (Subject Officer for their own employees, Super Admin/Planning
 * Officer broadly), but every write is audit-logged with extra care
 * given the nature of the data (see AuditLogService calls below).
 */
class EmployeeInterdictionController extends Controller
{
    use HandlesDisableToggle;

    public function index(Request $request, Employee $employee)
    {
        $this->authorizeEmployee($request, $employee);

        $interdictions = $employee->interdictions()
            ->active()
            ->with('recordedBy')
            ->orderByDesc('interdiction_date')
            ->paginate(15);

        return view('employee-interdictions.index', compact('employee', 'interdictions'));
    }

    public function create(Request $request, Employee $employee)
    {
        $this->authorizeEmployee($request, $employee);

        return view('employee-interdictions.form', compact('employee'));
    }

    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorizeEmployee($request, $employee);

        $data = $request->validate([
            'interdiction_date'    => ['required', 'date'],
            'reason'                => ['nullable', 'string', 'max:500'],
            'inquiry_reference_no'  => ['nullable', 'string', 'max:100'],
            'inquiry_status'        => ['required', Rule::in(['ongoing', 'concluded'])],
            'reinstatement_date'    => ['nullable', 'date', 'after_or_equal:interdiction_date'],
            'outcome'               => ['nullable', Rule::in(array_keys(EmployeeInterdiction::OUTCOME_LABELS))],
            'notes'                 => ['nullable', 'string', 'max:500'],
        ]);

        $data['employee_id'] = $employee->id;
        $data['recorded_by'] = $request->user()->id;
        $data['is_active']   = true;

        $interdiction = EmployeeInterdiction::create($data);

        AuditLogService::created(
            $interdiction,
            "Recorded interdiction for {$employee->display_name} effective {$interdiction->interdiction_date->format('d M Y')} (status: {$interdiction->inquiry_status})"
        );

        // Notify Planning Officer — an interdiction affects the effective
        // headcount picture and should not sit unnoticed in one officer's
        // records only.
        $planningOfficers = \App\Models\User::active()->havingRole(\App\Models\User::ROLE_PLANNING_OFFICER)->get();
        NotificationService::sendToMany(
            $planningOfficers,
            type:  'employee_interdiction_recorded',
            title: 'Interdiction recorded',
            body:  "{$employee->display_name} ({$employee->position?->title}) was recorded as interdicted effective {$interdiction->interdiction_date->format('d M Y')}.",
            link:  route('employee-interdictions.index', $employee),
        );

        return redirect()->route('employee-interdictions.index', $employee)
            ->with('success', 'Interdiction record added.');
    }

    public function toggle(Request $request, Employee $employee, EmployeeInterdiction $interdiction): RedirectResponse
    {
        $this->authorizeEmployee($request, $employee);
        abort_unless($interdiction->employee_id === $employee->id, 404);

        return $this->performToggle(
            request:       $request,
            model:         $interdiction,
            label:         "Interdiction record dated {$interdiction->interdiction_date->format('d M Y')}",
            requireReason: true,
        );
    }

    private function authorizeEmployee(Request $request, Employee $employee): void
    {
        abort_unless(
            $request->user()->canManageEmployeeHrRecord($employee),
            403,
            'You may only manage interdiction records for employees under your assigned subject codes, '
            . 'or if your account holds an authorised Admin Group category (Director, Deputy Director General, '
            . 'Deputy Director, Administrative Officer, or Chief Clerk).'
        );
    }
}
