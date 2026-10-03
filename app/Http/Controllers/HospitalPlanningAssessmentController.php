<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\HospitalPlanningAssessment;
use App\Services\GovernanceAttestationService;
use Illuminate\Http\Request;

class HospitalPlanningAssessmentController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizePlanningAccess(request());
        $query = HospitalPlanningAssessment::query()
            ->with(['preparer:id,name', 'reviewer:id,name'])
            ->where('is_active', true)
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('planning_area')) {
            $query->where('planning_area', $request->string('planning_area'));
        }

        return view('planning.assessments.index', [
            'assessments' => $query->paginate(20)->withQueryString(),
            'statuses' => HospitalPlanningAssessment::STATUSES,
            'areas' => HospitalPlanningAssessment::AREAS,
        ]);
    }

    public function create()
    {
        $this->authorizePlanningAccess(request());
        return view('planning.assessments.form', [
            'assessment' => new HospitalPlanningAssessment(),
            'areas' => HospitalPlanningAssessment::AREAS,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizePlanningAccess(request());
        $data = $this->validated($request);
        $data['reference_no'] = $this->nextReference();
        $data['prepared_by'] = $request->user()->id;
        $data['status'] = HospitalPlanningAssessment::STATUS_DRAFT;

        $assessment = HospitalPlanningAssessment::create($data);
        $this->audit($request, 'create', $assessment, 'Created hospital planning decision-support assessment.');

        return redirect()
            ->route('planning.assessments.show', $assessment)
            ->with('success', 'Planning assessment saved as a draft. It is decision support only and is not an approval.');
    }

    public function show(HospitalPlanningAssessment $assessment)
    {
        $this->authorizePlanningAccess(request());
        $assessment->load(['preparer:id,name', 'reviewer:id,name']);

        return view('planning.assessments.show', compact('assessment'));
    }

    public function edit(HospitalPlanningAssessment $assessment)
    {
        $this->authorizePlanningAccess(request());
        abort_if($assessment->status === HospitalPlanningAssessment::STATUS_REFERRED, 422, 'A referred assessment is preserved as a planning record. Create a new assessment for material changes.');

        return view('planning.assessments.form', [
            'assessment' => $assessment,
            'areas' => HospitalPlanningAssessment::AREAS,
        ]);
    }

    public function update(Request $request, HospitalPlanningAssessment $assessment)
    {
        $this->authorizePlanningAccess(request());
        abort_if($assessment->status === HospitalPlanningAssessment::STATUS_REFERRED, 422, 'A referred assessment is preserved as a planning record. Create a new assessment for material changes.');

        $old = $assessment->toArray();
        $assessment->update($this->validated($request));
        $this->audit($request, 'update', $assessment, 'Updated hospital planning decision-support assessment.', $old);

        return redirect()
            ->route('planning.assessments.show', $assessment)
            ->with('success', 'Planning assessment updated.');
    }

    public function markReady(Request $request, HospitalPlanningAssessment $assessment)
    {
        $this->authorizePlanningAccess(request());
        $validated = $request->validate([
            'administrative_purpose' => ['required', 'string', 'min:10', 'max:500'],
            'authority_reference' => ['required', 'string', 'max:200'],
            'evidence_reviewed' => ['accepted'],
            'accuracy_confirmed' => ['accepted'],
            'minimum_necessary_confirmed' => ['accepted'],
            'no_conflict_confirmed' => ['accepted'],
        ]);

        abort_unless($assessment->support_only_acknowledged, 422, 'Confirm the decision-support-only boundary in the assessment before referral.');
        abort_unless($assessment->evidence_verified && $assessment->alternatives_considered && $assessment->risks_considered, 422, 'Complete the planning safeguard checks before referral.');

        $assessment->update([
            'status' => HospitalPlanningAssessment::STATUS_READY,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        GovernanceAttestationService::record(
            $request,
            'hospital_planning_review_ready',
            HospitalPlanningAssessment::class,
            $assessment->id,
            $validated
        );

        $this->audit($request, 'review', $assessment, 'Marked planning assessment ready for authorised review.');

        return back()->with('success', 'Assessment marked ready for review. No institutional decision has been made.');
    }

    public function refer(Request $request, HospitalPlanningAssessment $assessment)
    {
        $this->authorizePlanningAccess(request());
        $validated = $request->validate([
            'administrative_purpose' => ['required', 'string', 'min:10', 'max:500'],
            'authority_reference' => ['required', 'string', 'max:200'],
            'evidence_reviewed' => ['accepted'],
            'accuracy_confirmed' => ['accepted'],
            'minimum_necessary_confirmed' => ['accepted'],
            'no_conflict_confirmed' => ['accepted'],
        ]);

        abort_unless($assessment->status === HospitalPlanningAssessment::STATUS_READY, 422, 'Only a reviewed assessment can be referred onward.');

        $assessment->update([
            'status' => HospitalPlanningAssessment::STATUS_REFERRED,
            'referred_at' => now(),
        ]);

        GovernanceAttestationService::record(
            $request,
            'hospital_planning_referred',
            HospitalPlanningAssessment::class,
            $assessment->id,
            $validated
        );

        $this->audit($request, 'refer', $assessment, 'Referred planning recommendation for authorised human decision.');

        return back()->with('success', 'Planning recommendation referred. It remains advisory until the competent authority decides through the applicable official process.');
    }

    private function authorizePlanningAccess(Request $request): void
    {
        abort_unless(
            $request->user()?->canAccessPlanningReports(),
            403,
            'Hospital Planning Decision Support is limited to Planning Officers, Medical Officer Planning, and Super Admin.'
        );
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'planning_area' => ['required', 'in:'.implode(',', array_keys(HospitalPlanningAssessment::AREAS))],
            'planning_question' => ['required', 'string', 'min:10'],
            'objective' => ['required', 'string', 'min:10'],
            'evidence_summary' => ['required', 'string', 'min:10'],
            'assumptions' => ['nullable', 'string'],
            'options_considered' => ['required', 'string', 'min:10'],
            'recommended_option' => ['nullable', 'string'],
            'workforce_impact' => ['nullable', 'string'],
            'financial_impact' => ['nullable', 'string'],
            'service_delivery_impact' => ['nullable', 'string'],
            'risks_and_mitigations' => ['nullable', 'string'],
            'equity_and_access_considerations' => ['nullable', 'string'],
            'authority_reference' => ['nullable', 'string', 'max:200'],
            'data_as_at' => ['nullable', 'string', 'max:80'],
            'support_only_acknowledged' => ['accepted'],
            'evidence_verified' => ['accepted'],
            'alternatives_considered' => ['accepted'],
            'risks_considered' => ['accepted'],
            'minimum_necessary_confirmed' => ['accepted'],
        ]);
    }

    private function nextReference(): string
    {
        $year = now()->format('Y');
        $sequence = HospitalPlanningAssessment::query()
            ->where('reference_no', 'like', "HPA/{$year}/%")
            ->count() + 1;

        return sprintf('HPA/%s/%04d', $year, $sequence);
    }

    private function audit(
        Request $request,
        string $action,
        HospitalPlanningAssessment $assessment,
        string $description,
        ?array $oldValues = null
    ): void {
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'auditable_type' => HospitalPlanningAssessment::class,
            'auditable_id' => $assessment->id,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $assessment->fresh()->toArray(),
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);
    }
}
