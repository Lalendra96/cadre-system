<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Confirmation-in-service — a dedicated, narrowly-scoped controller for
 * exactly the 3 confirmation fields on Employee (is_confirmed,
 * date_confirmed, confirmation_reference_no), rather than folding this
 * into the general employee edit form.
 *
 * WHY SEPARATE FROM EmployeeController: the general employee edit form
 * is Subject-Officer/Super-Admin/Planning-Officer only. Confirmation-in-
 * service specifically should also be manageable by the Admin Group HR
 * categories (Director, Deputy Director General, Deputy Director,
 * Administrative Officer, Chief Clerk) — but opening the WHOLE employee
 * profile (name, NIC, position, unit...) to those categories would grant
 * far more than "manage confirmation status." A dedicated controller
 * scoped to just these 3 fields is the least-privilege way to give them
 * exactly what this request asked for.
 */
class EmployeeConfirmationController extends Controller
{
    public function edit(Request $request, Employee $employee)
    {
        $this->authorizeEmployee($request, $employee);

        return view('employee-confirmation.edit', compact('employee'));
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorizeEmployee($request, $employee);

        $data = $request->validate([
            'is_confirmed'              => ['boolean'],
            'date_confirmed'            => ['nullable', 'date', 'before_or_equal:today', 'required_if:is_confirmed,1'],
            'confirmation_reference_no' => ['nullable', 'string', 'max:60'],
        ], [
            'date_confirmed.required_if' => 'Enter the confirmation date, or uncheck "Confirmed in Service" if this employee is still on probation.',
        ]);

        $data['is_confirmed'] = $request->boolean('is_confirmed');
        if (! $data['is_confirmed']) {
            // Clearing the checkbox clears the supporting detail too — a
            // stale date/reference from a prior confirmation shouldn't
            // linger and look current once the checkbox is off.
            $data['date_confirmed'] = null;
            $data['confirmation_reference_no'] = null;
        }

        $old = $employee->getOriginal();
        $employee->update($data);

        AuditLogService::updated(
            $employee, $old,
            $data['is_confirmed']
                ? "Marked {$employee->display_name} as confirmed in service, effective {$employee->date_confirmed?->format('d M Y')}"
                : "Cleared confirmation-in-service status for {$employee->display_name}"
        );

        return redirect()->route('employee-confirmation.edit', $employee)
            ->with('success', 'Confirmation status updated.');
    }

    private function authorizeEmployee(Request $request, Employee $employee): void
    {
        abort_unless(
            $request->user()->canManageEmployeeHrRecord($employee),
            403,
            'You may only manage confirmation status for employees under your assigned subject codes, '
            . 'or if your account holds an authorised Admin Group category (Director, Deputy Director General, '
            . 'Deputy Director, Administrative Officer, or Chief Clerk).'
        );
    }
}
