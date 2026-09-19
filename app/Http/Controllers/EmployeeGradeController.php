<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDisableToggle;
use App\Models\Employee;
use App\Models\EmployeeGradeRecord;
use App\Models\PositionGrade;
use App\Services\AdministrativeDecisionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeGradeController extends Controller
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

        $records = $employee->gradeRecords()
            ->active()
            ->with([
                'positionGrade',
                'recordedBy',
            ])
            ->orderByDesc('effective_date')
            ->paginate(15);

        return view(
            'employee-grades.index',
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

        $availableGrades = PositionGrade::where(
            'position_id',
            $employee->position_id
        )
            ->active()
            ->ordered()
            ->get();

        return view(
            'employee-grades.form',
            [
                'employee' => $employee,
                'availableGrades' => $availableGrades,
            ]
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

        $data = $request->validate(
            [
                'position_grade_id' => [
                    'required',
                    'integer',
                    Rule::exists(
                        'position_grades',
                        'id'
                    )->where(
                        'position_id',
                        $employee->position_id
                    ),
                ],
                'effective_date' => [
                    'required',
                    'date',
                ],
                'reference_no' => [
                    'nullable',
                    'string',
                    'max:60',
                ],
                'notes' => [
                    'nullable',
                    'string',
                    'max:500',
                ],
            ],
            [
                'position_grade_id.exists'
                    => 'That grade is not configured for this employee\'s current position.',
            ]
        );

        $grade = PositionGrade::findOrFail(
            $data['position_grade_id']
        );

        $decision = AdministrativeDecisionService::request(
            type: 'grade_change',
            employee: $employee,
            payload: $data,
            requester: $request->user(),
            summary: 'Proposed grade record: '
                . $employee->display_name
                . ' → '
                . $grade->name
                . ' effective '
                . $data['effective_date'],
            sourceReference: $data['reference_no'] ?? null
        );

        return redirect()
            ->route(
                'administrative-decisions.show',
                $decision
            )
            ->with(
                'success',
                'Grade change submitted for independent human approval. No grade record has been applied yet.'
            );
    }

    public function toggle(
        Request $request,
        Employee $employee,
        EmployeeGradeRecord $record
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
            label: 'Grade record effective '
                . $record->effective_date->format('d M Y'),
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
            'You may only manage grade records for Employee Profiles within your authorised HR scope.'
        );
    }
}
