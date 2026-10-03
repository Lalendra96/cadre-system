<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeChangeRequest;
use App\Services\AdministrativeDecisionService;
use App\Services\AuditLogService;
use App\Services\WorkforceScopeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmployeeChangeRequestController extends Controller
{
    public function store(Request $request, Employee $employee)
    {
        WorkforceScopeService::authorizeEmployee($request->user(), $employee);
        $data = $request->validate([
            'field_name' => 'required|in:nic_number,date_of_birth,date_of_appointment,date_joined_public_service,retirement_age',
            'requested_value' => 'nullable|string|max:255',
            'reason' => 'required|string|max:2000',
        ]);
        $allowed = [
            'nic_number',
            'date_of_birth',
            'date_of_appointment',
            'date_joined_public_service',
            'retirement_age',
        ];
        $change = EmployeeChangeRequest::create([
            'employee_id' => $employee->id,
            'field_name' => $data['field_name'],
            'old_value' => (string) $employee->{$data['field_name']},
            'requested_value' => $data['requested_value'] ?? null,
            'reason' => $data['reason'],
            'requested_by' => $request->user()->id,
        ]);
        AuditLogService::created($change, 'Sensitive employee field change requested for review.');

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', 'Change request submitted for maker-checker review.');
    }

    public function review(Request $request, EmployeeChangeRequest $changeRequest)
    {
        abort_unless(
            $request->user()->isSuperAdmin() ||
                $request->user()->isPlanningOfficer() ||
                $request->user()->isAdminGroup(),
            403,
        );
        $data = $request->validate([
            'decision' => 'required|in:approved,rejected',
            'review_note' => 'nullable|string|max:2000',
        ]);
        if ($changeRequest->status !== 'pending') {
            abort(409, 'This change request has already been reviewed.');
        }
        if ($data['decision'] === 'approved') {
            $rules = match ($changeRequest->field_name) {
                'nic_number' => 'nullable|regex:/^(\d{9}[VvXx]|\d{12})$/',
                'date_of_birth', 'date_of_appointment', 'date_joined_public_service' => 'nullable|date',
                'retirement_age' => 'required|integer|min:18|max:100',
                default => 'nullable|string|max:255',
            };
            Validator::make(
                [$changeRequest->field_name => $changeRequest->requested_value],
                [$changeRequest->field_name => $rules],
            )->validate();

            $decision = AdministrativeDecisionService::request(
                type: 'employee_correction',
                employee: $changeRequest->employee,
                payload: [
                    'change_request_id' => $changeRequest->id,
                    'field_name' => $changeRequest->field_name,
                    'requested_value' => $changeRequest->requested_value,
                    'old_value' => $changeRequest->old_value,
                    'reason' => $changeRequest->reason,
                    'review_note' => $data['review_note'] ?? null,
                ],
                requester: $request->user(),
                summary: 'Proposed governed correction to '.$changeRequest->field_name.' for '.$changeRequest->employee->display_name,
                sourceReference: 'Employee change request #'.$changeRequest->id,
            );

            $changeRequest->update([
                'status' => 'governance_review',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'review_note' => $data['review_note'] ?? null,
                'administrative_decision_id' => $decision->id,
            ]);

            return redirect()->route('administrative-decisions.show', $decision)
                ->with('success', 'Correction validated and submitted to the governed approval workflow. The employee record remains unchanged until final approval.');
        }

        $changeRequest->update([
            'status' => 'rejected',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_note' => $data['review_note'] ?? null,
        ]);

        return back()->with('success', 'Employee change request rejected.');
    }
}
