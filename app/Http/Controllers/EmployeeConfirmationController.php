<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\AdministrativeDecisionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmployeeConfirmationController extends Controller
{
    public function edit(
        Request $request,
        Employee $employee
    ) {
        $this->authorizeEmployee(
            $request,
            $employee
        );

        return view(
            'employee-confirmation.edit',
            compact('employee')
        );
    }

    public function update(
        Request $request,
        Employee $employee
    ): RedirectResponse {
        $this->authorizeEmployee(
            $request,
            $employee
        );

        $data = $request->validate(
            [
                'is_confirmed' => [
                    'boolean',
                ],
                'date_confirmed' => [
                    'nullable',
                    'date',
                    'before_or_equal:today',
                    'required_if:is_confirmed,1',
                ],
                'confirmation_reference_no' => [
                    'nullable',
                    'string',
                    'max:60',
                ],
            ],
            [
                'date_confirmed.required_if'
                    => 'Enter the confirmation date, or uncheck "Confirmed in Service" if this employee is still on probation.',
            ]
        );

        $data['is_confirmed'] = $request->boolean(
            'is_confirmed'
        );

        if (! $data['is_confirmed']) {
            $data['date_confirmed'] = null;
            $data['confirmation_reference_no'] = null;
        }

        $decision = AdministrativeDecisionService::request(
            type: 'confirmation_status',
            employee: $employee,
            payload: $data,
            requester: $request->user(),
            summary: $data['is_confirmed']
                ? 'Proposed confirmation-in-service for '
                    . $employee->display_name
                    . ' effective '
                    . ($data['date_confirmed'] ?? 'date not recorded')
                : 'Proposed removal/correction of confirmation-in-service status for '
                    . $employee->display_name,
            sourceReference: $data['confirmation_reference_no'] ?? null
        );

        return redirect()
            ->route(
                'administrative-decisions.show',
                $decision
            )
            ->with(
                'success',
                'Confirmation status submitted for independent human approval. The Employee Profile has not been changed yet.'
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
            'You may only manage confirmation status for Employee Profiles within your authorised HR scope.'
        );
    }
}
