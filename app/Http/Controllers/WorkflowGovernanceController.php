<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DuplicateResolutionCase;
use App\Models\EmployeeDocument;
use App\Models\EmployeePromotion;
use App\Models\HrEscalation;
use App\Models\ReconciliationIssue;
use App\Models\RecruitmentVacancy;
use App\Services\WorkflowGovernanceService;
use Illuminate\Http\Request;

class WorkflowGovernanceController extends Controller
{
    public function verifyDocument(Request $request, EmployeeDocument $document)
    {
        $data = $request->validate(['decision' => 'required|in:verify,reject', 'note' => 'nullable|string|max:2000']);
        WorkflowGovernanceService::verifyDocument(
            $document,
            $request->user(),
            $data['decision'] === 'verify',
            $data['note'] ?? null,
        );

        return back()->with('success', 'Document verification recorded.');
    }

    public function createPromotion(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'from_grade_id' => 'nullable|exists:position_grades,id',
            'to_grade_id' => 'required|exists:position_grades,id',
            'effective_date' => 'required|date',
            'reference_no' => 'nullable|string|max:100',
            'justification' => 'required|string|max:4000',
        ]);
        WorkflowGovernanceService::createPromotion($data, $request->user());

        return back()->with('success', 'Promotion prepared for checking.');
    }

    public function transitionPromotion(Request $request, EmployeePromotion $promotion)
    {
        $data = $request->validate(['transition' => 'required|in:checked,recommended,approved']);
        WorkflowGovernanceService::transitionPromotion($promotion, $request->user(), $data['transition']);

        return back()->with('success', 'Promotion workflow updated.');
    }

    public function resolveReconciliation(Request $request, ReconciliationIssue $issue)
    {
        $data = $request->validate([
            'explanation' => 'required|string|max:4000',
            'resolution_note' => 'required|string|max:4000',
        ]);
        WorkflowGovernanceService::resolveReconciliation(
            $issue,
            $request->user(),
            $data['explanation'],
            $data['resolution_note'],
        );

        return back()->with('success', 'Reconciliation issue resolved and awaiting independent verification.');
    }

    public function openReconciliation(Request $request)
    {
        $data = $request->validate([
            'position_id' => 'required|exists:positions,id',
            'year' => 'required|integer|min:2000|max:2200',
            'month' => 'required|integer|min:1|max:12',
            'severity' => 'required|in:review,high,critical',
            'evidence' => 'required|array',
        ]);
        WorkflowGovernanceService::openReconciliation($data, $request->user());

        return back()->with('success', 'Reconciliation issue opened with a fixed evidence snapshot.');
    }

    public function verifyReconciliation(Request $request, ReconciliationIssue $issue)
    {
        WorkflowGovernanceService::verifyReconciliation($issue, $request->user());

        return back()->with('success', 'Reconciliation issue independently verified.');
    }

    public function advanceVacancy(Request $request, RecruitmentVacancy $vacancy)
    {
        $data = $request->validate([
            'status' => 'required|in:identified,requested,shortlisting,offered,filled,cancelled',
        ]);
        WorkflowGovernanceService::advanceVacancy($vacancy, $request->user(), $data['status']);

        return back()->with('success', 'Recruitment vacancy tracker updated.');
    }

    public function createVacancy(Request $request)
    {
        $data = $request->validate([
            'position_id' => 'required|exists:positions,id',
            'retirement_project_id' => 'nullable|exists:retirement_projects,id',
            'vacancy_count' => 'required|integer|min:1|max:999',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:4000',
        ]);
        WorkflowGovernanceService::createVacancy($data, $request->user());

        return back()->with('success', 'Vacancy added to the recruitment tracker.');
    }

    public function resolveDuplicate(Request $request, DuplicateResolutionCase $case)
    {
        $data = $request->validate(['resolution_note' => 'required|string|max:4000']);
        WorkflowGovernanceService::resolveDuplicate($case, $request->user(), $data['resolution_note']);

        return back()->with('success', 'Duplicate resolution recorded without deleting either employee record.');
    }

    public function acknowledgeEscalation(Request $request, HrEscalation $escalation)
    {
        WorkflowGovernanceService::acknowledgeEscalation($escalation, $request->user());

        return back()->with('success', 'Escalation acknowledged.');
    }

    public function resolveEscalation(Request $request, HrEscalation $escalation)
    {
        $data = $request->validate(['resolution_note' => 'required|string|max:4000']);
        WorkflowGovernanceService::resolveEscalation($escalation, $request->user(), $data['resolution_note']);

        return back()->with('success', 'Escalation resolved.');
    }
}
