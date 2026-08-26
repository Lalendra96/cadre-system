<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDisableToggle;
use App\Models\Employee;
use App\Models\EmployeeGradeRecord;
use App\Models\PositionGrade;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Grade-history records per employee. An employee can hold several grade
 * records over time; adding a new one automatically closes out the
 * previous "current" record's end_date so history stays consistent
 * without the officer having to remember to do it manually.
 */
class EmployeeGradeController extends Controller
{
    use HandlesDisableToggle;

    public function index(Request $request, Employee $employee)
    {
        $this->authorizeEmployee($request, $employee);

        $records = $employee->gradeRecords()
            ->active()
            ->with(['positionGrade', 'recordedBy'])
            ->orderByDesc('effective_date')
            ->paginate(15);

        return view('employee-grades.index', compact('employee', 'records'));
    }

    public function create(Request $request, Employee $employee)
    {
        $this->authorizeEmployee($request, $employee);

        // Only grades configured for the employee's CURRENT position are offered —
        // a grade record for a different position wouldn't make sense against
        // the flexible criteria that position defines.
        $availableGrades = PositionGrade::where('position_id', $employee->position_id)
            ->active()->ordered()->get();

        return view('employee-grades.form', [
            'employee'        => $employee,
            'availableGrades' => $availableGrades,
        ]);
    }

    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorizeEmployee($request, $employee);

        $data = $request->validate([
            'position_grade_id' => [
                'required', 'integer',
                \Illuminate\Validation\Rule::exists('position_grades', 'id')
                    ->where('position_id', $employee->position_id),
            ],
            'effective_date' => ['required', 'date'],
            'reference_no'   => ['nullable', 'string', 'max:60'],
            'notes'          => ['nullable', 'string', 'max:500'],
        ], [
            'position_grade_id.exists' => 'That grade is not configured for this employee\'s current position.',
        ]);

        $record = DB::transaction(function () use ($data, $employee, $request) {
            // Close out whichever record was previously "current" (no end_date)
            // by setting its end_date to the day before the new effective_date.
            $employee->gradeRecords()
                ->active()
                ->whereNull('end_date')
                ->update(['end_date' => \Carbon\Carbon::parse($data['effective_date'])->subDay()]);

            return EmployeeGradeRecord::create([
                'employee_id'        => $employee->id,
                'position_grade_id'  => $data['position_grade_id'],
                'effective_date'     => $data['effective_date'],
                'reference_no'       => $data['reference_no'] ?? null,
                'notes'              => $data['notes'] ?? null,
                'recorded_by'        => $request->user()->id,
                'is_active'          => true,
            ]);
        });

        AuditLogService::created($record, "Recorded grade change for {$employee->display_name} effective {$record->effective_date->format('d M Y')}");

        return redirect()->route('employee-grades.index', $employee)
            ->with('success', 'Grade record added — previous record closed out automatically.');
    }

    public function toggle(Request $request, Employee $employee, EmployeeGradeRecord $record): RedirectResponse
    {
        $this->authorizeEmployee($request, $employee);
        abort_unless($record->employee_id === $employee->id, 404);

        return $this->performToggle(
            request:       $request,
            model:         $record,
            label:         "Grade record effective {$record->effective_date->format('d M Y')}",
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
            abort(403, 'You may only manage grade records for employees under your assigned subject codes.');
        }
    }
}
