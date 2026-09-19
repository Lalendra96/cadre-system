<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDisableToggle;
use App\Models\Employee;
use App\Models\EmployeeIncrement;
use App\Services\AdministrativeDecisionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmployeeIncrementController extends Controller
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

        $increments = $employee->incrementRecords()
            ->active()
            ->with('recordedBy')
            ->orderByDesc('increment_date')
            ->paginate(15);

        return view(
            'employee-increments.index',
            compact(
                'employee',
                'increments'
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
            'employee-increments.form',
            [
                'employee' => $employee,
                'increment' => new EmployeeIncrement(),
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

        $data = $request->validate([
            'increment_date' => [
                'required',
                'date',
            ],
            'amount' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999.99',
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
        ]);

        $decision = AdministrativeDecisionService::request(
            type: 'increment_record',
            employee: $employee,
            payload: $data,
            requester: $request->user(),
            summary: 'Proposed increment record for '
                . $employee->display_name
                . ' dated '
                . $data['increment_date'],
            sourceReference: $data['reference_no'] ?? null
        );

        return redirect()
            ->route(
                'administrative-decisions.show',
                $decision
            )
            ->with(
                'success',
                'Increment record submitted for independent human approval. No consequential record has been applied yet.'
            );
    }

    public function toggle(
        Request $request,
        Employee $employee,
        EmployeeIncrement $increment
    ): RedirectResponse {
        $this->authorizeEmployee(
            $request,
            $employee
        );

        abort_unless(
            $increment->employee_id === $employee->id,
            404
        );

        return $this->performToggle(
            request: $request,
            model: $increment,
            label: 'Increment record dated '
                . $increment->increment_date->format('d M Y'),
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
            'You may only manage increment records for Employee Profiles within your authorised HR scope.'
        );
    }
}
