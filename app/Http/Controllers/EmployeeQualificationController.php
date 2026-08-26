<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDisableToggle;
use App\Models\Employee;
use App\Models\EmployeeQualification;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Education/professional qualification history per employee.
 * Same ownership pattern as EmployeeIncrementController — duplicated
 * (not shared via trait) since it's a 5-line check; see that controller's
 * docblock for the rationale.
 */
class EmployeeQualificationController extends Controller
{
    use HandlesDisableToggle;

    public function index(Request $request, Employee $employee)
    {
        $this->authorizeEmployee($request, $employee);

        $qualifications = $employee->qualifications()
            ->active()
            ->with('recordedBy')
            ->orderByDesc('year_obtained')
            ->paginate(15);

        return view('employee-qualifications.index', compact('employee', 'qualifications'));
    }

    public function create(Request $request, Employee $employee)
    {
        $this->authorizeEmployee($request, $employee);

        return view('employee-qualifications.form', compact('employee'));
    }

    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorizeEmployee($request, $employee);

        $data = $request->validate([
            'qualification_name' => ['required', 'string', 'max:200'],
            'institution'         => ['nullable', 'string', 'max:200'],
            'year_obtained'       => ['nullable', 'integer', 'min:1960', 'max:' . now()->year],
            'reference_no'        => ['nullable', 'string', 'max:60'],
            'notes'               => ['nullable', 'string', 'max:500'],
        ]);

        $data['employee_id'] = $employee->id;
        $data['recorded_by'] = $request->user()->id;
        $data['is_active']   = true;

        $qual = EmployeeQualification::create($data);
        AuditLogService::created($qual, "Recorded qualification for {$employee->display_name}: {$qual->qualification_name}");

        return redirect()->route('employee-qualifications.index', $employee)
            ->with('success', 'Qualification added.');
    }

    public function toggle(Request $request, Employee $employee, EmployeeQualification $qualification): RedirectResponse
    {
        $this->authorizeEmployee($request, $employee);
        abort_unless($qualification->employee_id === $employee->id, 404);

        return $this->performToggle(
            request:       $request,
            model:         $qualification,
            label:         "Qualification \"{$qualification->qualification_name}\"",
            requireReason: true,
        );
    }

    private function authorizeEmployee(Request $request, Employee $employee): void
    {
        $user = $request->user();
        if ($user->isSuperAdmin() || $user->isPlanningOfficer()) {
            return;
        }
        if (! $user->isSubjectOfficer() || ! $user->hasEffectiveSubjectCode($employee->subject_code_id)) {
            abort(403, 'You may only manage qualification records for employees under your assigned subject codes.');
        }
    }
}
