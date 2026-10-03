<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AdministrativeDecision;
use App\Models\Employee;
use App\Models\RetirementProject;
use App\Services\AdministrativeDecisionService;
use App\Services\AuditLogService;
use App\Services\WorkforceScopeService;
use Illuminate\Http\Request;

class RetirementProjectController extends Controller
{
    public function index(Request $request)
    {
        $ids = WorkforceScopeService::employeeQuery($request->user())->pluck('id');
        $projects = RetirementProject::whereIn('employee_id', $ids)->with(['employee.position', 'employee.unit', 'employee.subjectCode'])->orderBy('retirement_date')->paginate(25);
        $upcoming = Employee::whereIn('id', $ids)->where('is_active', true)->whereNotNull('date_of_birth')->get()->filter(fn ($e) => $e->retire_date && $e->retire_date->between(now(), now()->addMonths(24)) && ! RetirementProject::where('employee_id', $e->id)->exists())->sortBy('retire_date');
        $isScoped = $request->user()->isSubjectOfficer() && ! $request->user()->isSuperAdmin() && ! $request->user()->isPlanningOfficer();

        return view('retirement-projects.index', compact('projects', 'upcoming', 'isScoped'));
    }

    public function store(Request $request)
    {
        $employee = Employee::findOrFail($request->input('employee_id'));
        WorkforceScopeService::authorizeEmployee($request->user(), $employee);
        $data = $request->validate(['employee_id' => 'required|exists:employees,id', 'retirement_date' => 'required|date', 'replacement_required' => 'nullable|boolean', 'remarks' => 'nullable|string|max:2000']);
        $data['replacement_required'] = $request->boolean('replacement_required');
        foreach (['dob_verified', 'service_verified', 'contact_verified', 'documents_verified', 'vacancy_created'] as $f) {
            if ($request->has($f)) {
                $data[$f] = $request->boolean($f);
            }
        }
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;
        $project = RetirementProject::updateOrCreate(['employee_id' => $data['employee_id']], $data);
        AuditLogService::created($project, 'Retirement project created/updated');

        return back()->with('success', 'Retirement project started.');
    }

    public function update(Request $request, RetirementProject $retirementProject)
    {
        WorkforceScopeService::authorizeEmployee($request->user(), $retirementProject->employee);
        $data = $request->validate([
            'status' => 'required|in:identified,verified,notification_prepared,employee_notified,documentation,clearance,completed',
            'notification_issued_date' => 'nullable|date',
            'employee_acknowledged_date' => 'nullable|date',
            'replacement_required' => 'nullable|boolean',
            'pension_status' => 'required|in:not_started,in_progress,completed',
            'clearance_status' => 'required|in:not_started,in_progress,completed',
            'handover_status' => 'required|in:not_started,in_progress,completed',
            'dob_verified' => 'nullable|boolean',
            'service_verified' => 'nullable|boolean',
            'contact_verified' => 'nullable|boolean',
            'documents_verified' => 'nullable|boolean',
            'vacancy_created' => 'nullable|boolean',
            'final_working_date' => 'nullable|date',
            'reference_no' => 'nullable|string|max:80',
            'remarks' => 'nullable|string|max:2000',
        ]);
        $data['replacement_required'] = $request->boolean('replacement_required');
        foreach (['dob_verified', 'service_verified', 'contact_verified', 'documents_verified', 'vacancy_created'] as $f) {
            $data[$f] = $request->boolean($f);
        }
        $data['updated_by'] = $request->user()->id;

        if ($data['status'] === 'completed') {
            abort_unless(
                $data['pension_status'] === 'completed'
                    && $data['clearance_status'] === 'completed'
                    && $data['handover_status'] === 'completed'
                    && (bool) $data['dob_verified']
                    && (bool) $data['service_verified']
                    && (bool) $data['contact_verified']
                    && (bool) $data['documents_verified'],
                422,
                'Retirement completion requires completed pension, clearance and handover plus DOB, service, contact and document verification.'
            );

            $existingDecision = $retirementProject->administrative_decision_id
                ? AdministrativeDecision::find($retirementProject->administrative_decision_id)
                : null;
            abort_if($existingDecision && in_array($existingDecision->status, ['pending', 'checked', 'recommended'], true), 409,
                'A retirement completion decision is already awaiting governance approval.');

            $projectUpdate = $data;
            $projectUpdate['status'] = 'clearance';
            unset($projectUpdate['completed_at']);
            $retirementProject->update($projectUpdate);

            $decision = AdministrativeDecisionService::request(
                type: 'retirement_completion',
                employee: $retirementProject->employee,
                payload: [
                    'retirement_project_id' => $retirementProject->id,
                    'retirement_date' => optional($retirementProject->retirement_date)->toDateString(),
                    'final_working_date' => $data['final_working_date'] ?? null,
                    'reference_no' => $data['reference_no'] ?? null,
                    'pension_status' => $data['pension_status'],
                    'clearance_status' => $data['clearance_status'],
                    'handover_status' => $data['handover_status'],
                    'verification' => [
                        'dob' => (bool) $data['dob_verified'],
                        'service' => (bool) $data['service_verified'],
                        'contact' => (bool) $data['contact_verified'],
                        'documents' => (bool) $data['documents_verified'],
                    ],
                ],
                requester: $request->user(),
                summary: 'Retirement completion for '.$retirementProject->employee->display_name,
                sourceReference: $data['reference_no'] ?? ('Retirement project #'.$retirementProject->id),
            );
            $retirementProject->update(['administrative_decision_id' => $decision->id]);

            return redirect()->route('administrative-decisions.show', $decision)
                ->with('success', 'Retirement prerequisites are complete. Final retirement completion now requires independent governance approval.');
        }

        $retirementProject->update($data);
        AuditLogService::updated($retirementProject, [], 'Retirement project workflow updated.');

        return back()->with('success', 'Retirement project updated.');
    }
}
