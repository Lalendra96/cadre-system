<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDisableToggle;
use App\Models\Employee;
use App\Models\EmployeeInterdiction;
use App\Services\AdministrativeDecisionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeInterdictionController extends Controller
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

        $interdictions = $employee->interdictions()
            ->active()
            ->with('recordedBy')
            ->orderByDesc('interdiction_date')
            ->paginate(15);

        return view(
            'employee-interdictions.index',
            compact(
                'employee',
                'interdictions'
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
            'employee-interdictions.form',
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
            'interdiction_date' => [
                'required',
                'date',
            ],
            'reason' => [
                'nullable',
                'string',
                'max:500',
            ],
            'inquiry_reference_no' => [
                'nullable',
                'string',
                'max:100',
            ],
            'inquiry_status' => [
                'required',
                Rule::in([
                    'ongoing',
                    'concluded',
                ]),
            ],
            'reinstatement_date' => [
                'nullable',
                'date',
                'after_or_equal:interdiction_date',
            ],
            'outcome' => [
                'nullable',
                Rule::in(
                    array_keys(
                        EmployeeInterdiction::OUTCOME_LABELS
                    )
                ),
            ],
            'notes' => [
                'nullable',
                'string',
                'max:500',
            ],
        ]);

        $decision = AdministrativeDecisionService::request(
            type: 'interdiction_record',
            employee: $employee,
            payload: $data,
            requester: $request->user(),
            summary: 'Proposed interdiction / disciplinary record for '
                . $employee->display_name
                . ' effective '
                . $data['interdiction_date'],
            sourceReference: $data['inquiry_reference_no'] ?? null
        );

        return redirect()
            ->route(
                'administrative-decisions.show',
                $decision
            )
            ->with(
                'success',
                'Sensitive HR action submitted for independent human approval. No interdiction record has been applied yet.'
            );
    }

    public function toggle(
        Request $request,
        Employee $employee,
        EmployeeInterdiction $interdiction
    ): RedirectResponse {
        $this->authorizeEmployee(
            $request,
            $employee
        );

        abort_unless(
            $interdiction->employee_id === $employee->id,
            404
        );

        return $this->performToggle(
            request: $request,
            model: $interdiction,
            label: 'Interdiction record dated '
                . $interdiction->interdiction_date->format('d M Y'),
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
            'You may only manage sensitive HR records for Employee Profiles within your authorised HR scope.'
        );
    }
}
