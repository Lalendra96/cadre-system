<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDisableToggle;
use App\Models\Employee;
use App\Models\EmployeeIncrement;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Increment-date history per employee. Reminders are sent automatically
 * 30 days before an upcoming increment_date by the scheduled command
 * App\Console\Commands\NotifyUpcomingIncrements — nothing here triggers
 * a notification directly.
 *
 * ACCESS: Super Admin / Planning Officer (any employee) or the employee's
 * own Subject Officer (their effective subject codes only — see
 * EmployeeController::authorizeOwnEmployee() for the identical pattern
 * this controller reuses).
 */
class EmployeeIncrementController extends Controller
{
    use HandlesDisableToggle;

    public function index(Request $request, Employee $employee)
    {
        $this->authorizeEmployee($request, $employee);

        $increments = $employee->incrementRecords()
            ->active()
            ->with('recordedBy')
            ->orderByDesc('increment_date')
            ->paginate(15);

        return view('employee-increments.index', compact('employee', 'increments'));
    }

    public function create(Request $request, Employee $employee)
    {
        $this->authorizeEmployee($request, $employee);

        return view('employee-increments.form', ['employee' => $employee, 'increment' => new EmployeeIncrement()]);
    }

    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorizeEmployee($request, $employee);

        $data = $request->validate([
            'increment_date' => ['required', 'date'],
            'amount'         => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'reference_no'   => ['nullable', 'string', 'max:60'],
            'notes'          => ['nullable', 'string', 'max:500'],
        ]);

        $data['employee_id']  = $employee->id;
        $data['recorded_by']  = $request->user()->id;
        $data['is_active']    = true;

        $increment = EmployeeIncrement::create($data);
        AuditLogService::created($increment, "Recorded increment for {$employee->display_name} on {$increment->increment_date->format('d M Y')}");

        return redirect()->route('employee-increments.index', $employee)
            ->with('success', 'Increment record added.');
    }

    public function toggle(Request $request, Employee $employee, EmployeeIncrement $increment): RedirectResponse
    {
        $this->authorizeEmployee($request, $employee);
        abort_unless($increment->employee_id === $employee->id, 404);

        return $this->performToggle(
            request:       $request,
            model:         $increment,
            label:         "Increment record dated {$increment->increment_date->format('d M Y')}",
            requireReason: true,
        );
    }

    /**
     * Same ownership pattern as EmployeeController::authorizeOwnEmployee() —
     * duplicated here (rather than extracted to a shared trait) because it
     * is a two-line check; a shared trait would be more indirection than
     * the duplication costs. Revisit if a fourth controller needs it.
     */
    private function authorizeEmployee(Request $request, Employee $employee): void
    {
        $user = $request->user();
        if ($user->isSuperAdmin() || $user->isPlanningOfficer()) {
            return;
        }
        if (! $user->isSubjectOfficer() || ! $user->hasEffectiveSubjectCode($employee->subject_code_id)) {
            abort(403, 'You may only manage increment records for employees under your assigned subject codes.');
        }
    }
}
