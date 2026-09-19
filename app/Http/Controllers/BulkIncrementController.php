<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Position;
use App\Models\SubjectCode;
use App\Services\AdministrativeDecisionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class BulkIncrementController extends Controller
{
    public function create(Request $request)
    {
        $user = $request->user();
        $codeIds = $this->scopedSubjectCodeIds(
            $user
        );

        $positions = Position::active()
            ->whereHas(
                'employees',
                fn ($query) => $query
                    ->active()
                    ->whereIn(
                        'subject_code_id',
                        $codeIds
                    )
            )
            ->orderBy('title')
            ->get();

        $selectedPositionId = $request->query(
            'position_id'
        );

        $employees = collect();

        if ($selectedPositionId) {
            $employees = Employee::active()
                ->where(
                    'position_id',
                    $selectedPositionId
                )
                ->whereIn(
                    'subject_code_id',
                    $codeIds
                )
                ->with([
                    'subjectCode',
                    'incrementRecords' => fn ($query) => $query
                        ->active()
                        ->mostRecent()
                        ->limit(1),
                ])
                ->orderBy('name')
                ->get();
        }

        return view(
            'bulk-increments.create',
            [
                'positions' => $positions,
                'selectedPositionId' => $selectedPositionId,
                'employees' => $employees,
            ]
        );
    }

    public function store(
        Request $request
    ): RedirectResponse {
        $user = $request->user();

        $data = $request->validate([
            'position_id' => [
                'required',
                'integer',
                'exists:positions,id',
            ],
            'employee_id' => [
                'required',
                'array',
            ],
            'employee_id.*' => [
                'integer',
                'exists:employees,id',
            ],
            'increment_date' => [
                'required',
                'array',
            ],
            'increment_date.*' => [
                'nullable',
                'date',
            ],
            'amount' => [
                'nullable',
                'array',
            ],
            'amount.*' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999.99',
            ],
            'reference_no' => [
                'nullable',
                'array',
            ],
            'reference_no.*' => [
                'nullable',
                'string',
                'max:60',
            ],
        ]);

        $allowedCodes = $this->scopedSubjectCodeIds(
            $user
        );

        $submitted = 0;
        $skipped = 0;

        foreach (
            $data['employee_id'] as $index => $employeeId
        ) {
            $date = $data['increment_date'][$index]
                ?? null;

            if (! $date) {
                $skipped++;
                continue;
            }

            $employee = Employee::find(
                $employeeId
            );

            if (
                ! $employee
                || ! $allowedCodes->contains(
                    $employee->subject_code_id
                )
            ) {
                $skipped++;
                continue;
            }

            AdministrativeDecisionService::request(
                type: 'increment_record',
                employee: $employee,
                payload: [
                    'increment_date' => $date,
                    'amount' => $data['amount'][$index]
                        ?? null,
                    'reference_no' => $data['reference_no'][$index]
                        ?? null,
                    'notes' => 'Submitted through bulk increment proposal.',
                ],
                requester: $user,
                summary: 'Bulk-proposed increment record for '
                    . $employee->display_name
                    . ' dated '
                    . $date,
                sourceReference: $data['reference_no'][$index]
                    ?? null
            );

            $submitted++;
        }

        return redirect()
            ->route(
                'administrative-decisions.index',
                [
                    'status' => 'pending',
                ]
            )
            ->with(
                'success',
                $submitted
                    . ' increment proposal(s) submitted for independent human approval'
                    . (
                        $skipped > 0
                            ? ', '
                                . $skipped
                                . ' row(s) skipped.'
                            : '.'
                    )
            );
    }

    private function scopedSubjectCodeIds(
        \App\Models\User $user
    ): Collection {
        if (
            $user->isSuperAdmin()
            || $user->isPlanningOfficer()
        ) {
            return SubjectCode::active()
                ->pluck('id');
        }

        abort_unless(
            $user->isSubjectOfficer(),
            403,
            'You do not have permission to propose increments.'
        );

        return $user->effectiveSubjectCodeIds();
    }
}
