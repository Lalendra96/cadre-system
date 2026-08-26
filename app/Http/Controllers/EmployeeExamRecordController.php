<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDisableToggle;
use App\Models\Employee;
use App\Models\EmployeeExamRecord;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Competitive Examination and Efficiency Bar (E-Bar) exam history per
 * employee. Both exam types share this one table (exam_type column)
 * since they follow an identical shape (name, date, result, reference).
 */
class EmployeeExamRecordController extends Controller
{
    use HandlesDisableToggle;

    public function index(Request $request, Employee $employee)
    {
        $this->authorizeEmployee($request, $employee);

        $records = $employee->examRecords()
            ->active()
            ->with('recordedBy')
            ->orderByDesc('exam_date')
            ->paginate(15);

        return view('employee-exam-records.index', compact('employee', 'records'));
    }

    public function create(Request $request, Employee $employee)
    {
        $this->authorizeEmployee($request, $employee);

        return view('employee-exam-records.form', compact('employee'));
    }

    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorizeEmployee($request, $employee);

        $data = $request->validate([
            'exam_type'    => ['required', Rule::in(array_keys(EmployeeExamRecord::TYPE_LABELS))],
            'exam_name'    => ['nullable', 'string', 'max:200'],
            'exam_date'    => ['nullable', 'date'],
            'result'       => ['required', Rule::in(array_keys(EmployeeExamRecord::RESULT_LABELS))],
            'reference_no' => ['nullable', 'string', 'max:60'],
            'notes'        => ['nullable', 'string', 'max:500'],
        ]);

        $data['employee_id'] = $employee->id;
        $data['recorded_by'] = $request->user()->id;
        $data['is_active']   = true;

        $record = EmployeeExamRecord::create($data);
        AuditLogService::created(
            $record,
            "Recorded {$record->exam_type} exam result for {$employee->display_name}: " . ucfirst($record->result)
        );

        return redirect()->route('employee-exam-records.index', $employee)
            ->with('success', 'Exam record added.');
    }

    public function toggle(Request $request, Employee $employee, EmployeeExamRecord $record): RedirectResponse
    {
        $this->authorizeEmployee($request, $employee);
        abort_unless($record->employee_id === $employee->id, 404);

        return $this->performToggle(
            request:       $request,
            model:         $record,
            label:         'Exam record',
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
            abort(403, 'You may only manage exam records for employees under your assigned subject codes.');
        }
    }
}
