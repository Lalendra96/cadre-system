<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\PersonnelDisplayService;
use App\Models\AdministrativeDecision;
use App\Models\Employee;
use App\Services\AdministrativeDecisionService;
use App\Services\GovernanceIntegrationService;
use App\Services\GovernanceAttestationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class GovernanceControlController extends Controller
{
    public function dashboard(Request $request)
    {
        $this->allowGovernance($request);
        $this->ensureTables();

        $metrics = [
            'draft_decisions' => DB::table('administrative_decisions')->whereIn('status', ['draft', 'pending', 'checked', 'recommended'])->count(),
            'missing_authority' => DB::table('administrative_decisions')->whereIn('status', ['draft', 'pending', 'checked', 'recommended', 'approved'])->whereNull('approval_authority_id')->whereNull('authority_reference')->whereNull('source_reference')->count(),
            'sod_overrides' => DB::table('administrative_decisions')->where('sod_override', true)->count(),
            'expired_delegations' => Schema::hasTable('authority_delegations') ? DB::table('authority_delegations')->where('is_active', true)->whereDate('ends_on', '<', now()->toDateString())->count() : 0,
            'regulatory_open' => DB::table('regulatory_changes')->whereNotIn('status', ['implemented', 'closed', 'not_applicable'])->count(),
            'retention_due' => DB::table('records_retention_cases')->where('legal_hold', false)->whereNotNull('eligible_disposal_on')->whereDate('eligible_disposal_on', '<=', now()->toDateString())->where('status', '!=', 'disposed')->count(),
            'handover_pending' => DB::table('handover_certificates')->whereNotIn('status', ['verified', 'cancelled'])->count(),
            'high_dq' => Schema::hasTable('data_quality_issues') ? DB::table('data_quality_issues')->where('severity', 'high')->whereNotIn('status', ['resolved', 'verified'])->count() : 0,
            'access_review_overdue' => Schema::hasTable('access_reviews') ? DB::table('access_reviews')->where('status', '!=', 'completed')->whereNotNull('due_on')->whereDate('due_on', '<', now()->toDateString())->count() : 0,
            'restore_overdue' => Schema::hasTable('backup_assurance_records') ? DB::table('backup_assurance_records')->whereNull('restore_tested_at')->count() : 0,
            'missing_provenance' => DB::table('administrative_decisions')->whereIn('status', ['pending', 'checked', 'recommended', 'approved'])->whereNull('provenance')->count(),
            'missing_rule' => DB::table('administrative_decisions')->whereIn('status', ['pending', 'checked', 'recommended', 'approved'])->whereNull('rule_version_id')->count(),
            'regulatory_overdue' => DB::table('regulatory_changes')->whereNotIn('status', ['implemented', 'closed', 'not_applicable'])->whereNotNull('target_effective_on')->whereDate('target_effective_on', '<', now()->toDateString())->count(),
            'retention_policy_missing' => DB::table('records_retention_cases')->whereNull('retention_years')->where('status', '!=', 'disposed')->count(),
            'safeguard_attestations_30d' => Schema::hasTable('governance_action_attestations') ? DB::table('governance_action_attestations')->where('attested_at', '>=', now()->subDays(30))->count() : 0,
        ];

        $decisions = DB::table('administrative_decisions as d')
            ->leftJoin('users as p', 'p.id', '=', 'd.prepared_by')
            ->leftJoin('users as rq', 'rq.id', '=', 'd.requested_by')
            ->leftJoin('employees as e', 'e.id', '=', 'd.employee_id')
            ->select('d.*', DB::raw('COALESCE(p.name, rq.name) as preparer_name'))
            ->orderByDesc('d.updated_at')->limit(12)->get();
        $decisions = PersonnelDisplayService::hydrateEmployeeRows($decisions);
        $changes = DB::table('regulatory_changes')->orderByDesc('updated_at')->limit(8)->get();

        return view('governance-control.dashboard', compact('metrics', 'decisions', 'changes'));
    }


    public function attestations(Request $request)
    {
        $this->allowGovernance($request);
        abort_unless(Schema::hasTable('governance_action_attestations'), 404);

        $query = DB::table('governance_action_attestations as a')
            ->join('users as u', 'u.id', '=', 'a.user_id')
            ->select('a.*', 'u.name as user_name', 'u.email as user_email')
            ->orderByDesc('a.attested_at');

        if ($request->filled('action_key')) {
            $query->where('a.action_key', $request->string('action_key'));
        }
        if ($request->filled('from')) {
            $query->whereDate('a.attested_at', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('a.attested_at', '<=', $request->date('to'));
        }

        $attestations = $query->paginate(50)->withQueryString();
        $actionKeys = DB::table('governance_action_attestations')->distinct()->orderBy('action_key')->pluck('action_key');

        return view('governance-control.attestations', compact('attestations', 'actionKeys'));
    }

    public function decisions(Request $request)
    {
        $this->allowGovernance($request);
        $this->ensureTables();
        $decisions = DB::table('administrative_decisions as d')
            ->leftJoin('employees as e', 'e.id', '=', 'd.employee_id')
            ->leftJoin('users as p', 'p.id', '=', 'd.prepared_by')
            ->leftJoin('users as rq', 'rq.id', '=', 'd.requested_by')
            ->leftJoin('users as c', 'c.id', '=', 'd.checked_by')
            ->leftJoin('users as rec', 'rec.id', '=', 'd.recommended_by')
            ->leftJoin('users as a', 'a.id', '=', 'd.approved_by')
            ->select('d.*', DB::raw('COALESCE(p.name, rq.name) as preparer_name'), 'c.name as checker_name', 'rec.name as recommender_name', 'a.name as approver_name')
            ->orderByDesc('d.created_at')->limit(100)->get();
        $decisions = PersonnelDisplayService::hydrateEmployeeRows($decisions);
        $employees = Schema::hasTable('employees') ? \App\Models\Employee::query()->where('is_active', true)->orderBy('id')->limit(1500)->get(['id', 'name', 'pay_no'])->sortBy(fn ($e) => mb_strtolower((string) $e->name, 'UTF-8'))->values() : collect();
        $authorities = Schema::hasTable('approval_authorities') ? DB::table('approval_authorities')->where('is_active', true)->orderBy('process_label')->get() : collect();
        $rules = DB::table('governance_rule_versions')->where('is_active', true)->orderBy('rule_key')->orderByDesc('version_no')->get();

        return view('governance-control.decisions', compact('decisions', 'employees', 'authorities', 'rules'));
    }

    public function storeDecision(Request $request): RedirectResponse
    {
        $this->allowGovernance($request);
        $this->ensureTables();
        $data = $request->validate([
            'decision_type' => ['required', 'string', 'max:80'], 'title' => ['required', 'string', 'max:200'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'], 'approval_authority_id' => ['nullable', 'integer', 'exists:approval_authorities,id'],
            'rule_version_id' => ['nullable', 'integer', 'exists:governance_rule_versions,id'], 'authority_reference' => ['nullable', 'string', 'max:160'],
            'effective_date' => ['nullable', 'date'], 'decision_text' => ['nullable', 'string', 'max:6000'], 'supporting_evidence' => ['nullable', 'string', 'max:6000'],
            'source_type' => ['nullable', 'string', 'max:100'], 'source_id' => ['nullable', 'integer'],
            'provenance_source' => ['nullable', 'string', 'max:2000'], 'provenance_method' => ['nullable', 'string', 'max:2000'],
        ]);

        $employee = ! empty($data['employee_id']) ? Employee::find($data['employee_id']) : null;
        $resolvedAuthority = empty($data['approval_authority_id']) ? GovernanceIntegrationService::resolveAuthority($data['decision_type'], $data['effective_date'] ?? null) : null;
        $resolvedRule = empty($data['rule_version_id']) ? GovernanceIntegrationService::resolveRule($data['decision_type'], $data['effective_date'] ?? null, $employee) : null;

        $decision = AdministrativeDecision::create([
            'decision_type' => $data['decision_type'],
            'employee_id' => $data['employee_id'] ?? null,
            'summary' => $data['title'],
            'payload' => [],
            'source_reference' => $data['authority_reference'] ?? null,
            'status' => 'draft',
            'requested_by' => $request->user()->id,
            'requested_at' => now(),
            'decision_no' => null,
            'title' => $data['title'],
            'source_type' => $data['source_type'] ?? null,
            'source_id' => $data['source_id'] ?? null,
            'approval_authority_id' => $data['approval_authority_id'] ?? $resolvedAuthority?->id,
            'rule_version_id' => $data['rule_version_id'] ?? $resolvedRule?->id,
            'authority_reference' => $data['authority_reference'] ?? $resolvedAuthority?->reference_no ?? $resolvedAuthority?->authority_source,
            'effective_date' => $data['effective_date'] ?? null,
            'decision_text' => $data['decision_text'] ?? null,
            'supporting_evidence' => $data['supporting_evidence'] ?? null,
            'prepared_by' => $request->user()->id,
            'provenance' => [
                'source' => $data['provenance_source'] ?? null,
                'method' => $data['provenance_method'] ?? 'Manually registered governance decision.',
                'rule_version_id' => $data['rule_version_id'] ?? $resolvedRule?->id,
                'authority_id' => $data['approval_authority_id'] ?? $resolvedAuthority?->id,
                'recorded_at' => now()->toIso8601String(),
            ],
        ]);
        $decision->update(['decision_no' => 'ADM-'.now()->format('Y').'-'.str_pad((string) $decision->id, 6, '0', STR_PAD_LEFT)]);
        $this->audit($request, 'created', 'AdministrativeDecision', $decision->id, 'Created administrative decision');

        return back()->with('success', 'Administrative decision registered as draft.');
    }

    public function advanceDecision(Request $request, int $decision): RedirectResponse
    {
        $this->allowGovernance($request);
        $this->ensureTables();
        $row = DB::table('administrative_decisions')->where('id', $decision)->first();
        abort_unless($row, 404);
        $action = $request->validate([
            'action' => ['required', 'in:check,recommend,approve'],
            'decision_reason' => ['nullable', 'string', 'max:1000'],
            'override_reason' => ['nullable', 'string', 'max:3000'],
            'override_authority' => ['nullable', 'string', 'max:160'],
            'administrative_purpose' => ['required', 'string', 'max:500', 'not_regex:/^\s/'],
            'evidence_reviewed' => ['accepted'],
            'accuracy_confirmed' => ['accepted'],
            'minimum_necessary_confirmed' => ['accepted'],
            'no_conflict_confirmed' => ['accepted'],
            'authority_confirmed' => ['nullable', 'accepted'],
        ]);

        if ($action['action'] === 'approve' && empty(trim((string) ($action['decision_reason'] ?? '')))) {
            throw ValidationException::withMessages(['decision_reason' => 'A final decision/approval reason is required.']);
        }
        if ($action['action'] === 'approve' && ! $request->boolean('authority_confirmed')) {
            throw ValidationException::withMessages(['authority_confirmed' => 'Confirm that the approval authority/reference and applicable rule were checked before approval.']);
        }

        if (array_key_exists($row->decision_type, AdministrativeDecision::TYPES) && $row->status !== 'draft') {
            $model = AdministrativeDecision::findOrFail($decision);
            if ($action['action'] === 'approve') {
                AdministrativeDecisionService::approve(
                    $model, $request->user(),
                    $action['decision_reason'] ?: 'Approved via Governance Control Register after independent review.',
                    $action['override_reason'] ?? null, $action['override_authority'] ?? null
                );
            } else {
                AdministrativeDecisionService::advanceReview(
                    $model, $request->user(), $action['action'],
                    $action['decision_reason'] ?? null,
                    $action['override_reason'] ?? null, $action['override_authority'] ?? null
                );
            }

            GovernanceAttestationService::record($request, 'decision_'.$action['action'], 'AdministrativeDecision', $decision, [
                'administrative_purpose' => $action['administrative_purpose'],
                'authority_reference' => $action['override_authority'] ?? $row->authority_reference ?? $row->source_reference ?? null,
                'evidence_reviewed' => true,
                'accuracy_confirmed' => true,
                'minimum_necessary_confirmed' => true,
                'no_conflict_confirmed' => true,
            ]);

            return back()->with('success', 'Transactional decision workflow updated and governed application rules preserved.');
        }

        $manualModel = AdministrativeDecision::findOrFail($decision);
        GovernanceIntegrationService::assertAuthorityForStage($manualModel, $request->user(), $action['action']);
        $expected = ['check' => 'draft', 'recommend' => 'checked', 'approve' => 'recommended'];
        if (($expected[$action['action']] ?? null) !== $row->status) {
            throw ValidationException::withMessages(['action' => 'Workflow order is enforced: Draft → Checked → Recommended → Approved. Current status: '.ucfirst((string) $row->status).'.']);
        }
        $userId = (int) $request->user()->id;
        $conflict = false;
        if ($action['action'] === 'check') {
            $conflict = $userId === (int) $row->prepared_by;
        }
        if ($action['action'] === 'recommend') {
            $conflict = in_array($userId, array_filter([(int) $row->prepared_by, (int) $row->checked_by]), true);
        }
        if ($action['action'] === 'approve') {
            $conflict = in_array($userId, array_filter([(int) $row->prepared_by, (int) $row->checked_by, (int) $row->recommended_by]), true);
        }

        if ($conflict && empty($action['override_reason'])) {
            throw ValidationException::withMessages(['override_reason' => 'Segregation of Duties blocks this action. Provide an exceptional override reason and authority reference.']);
        }
        if ($conflict && empty($action['override_authority'])) {
            throw ValidationException::withMessages(['override_authority' => 'An authority/reference is required for an SoD override.']);
        }

        $update = ['updated_at' => now()];
        if ($conflict) {
            $update['sod_override'] = true;
            $update['sod_override_reason'] = $action['override_reason'];
            $update['sod_override_authority'] = $action['override_authority'];
        }
        if ($action['action'] === 'check') {
            $update += ['checked_by' => $userId, 'checked_at' => now(), 'status' => 'checked'];
        }
        if ($action['action'] === 'recommend') {
            $update += ['recommended_by' => $userId, 'recommended_at' => now(), 'status' => 'recommended'];
        }
        if ($action['action'] === 'approve') {
            if (empty($row->approval_authority_id) && empty($row->authority_reference)) {
                throw ValidationException::withMessages(['action' => 'Approval is blocked until an approval authority or authority reference is attached.']);
            }
            $update += ['approved_by' => $userId, 'approved_at' => now(), 'decided_by' => $userId, 'decided_at' => now(), 'status' => 'approved'];
        }
        DB::table('administrative_decisions')->where('id', $decision)->update($update);
        if ($action['action'] === 'approve' && Schema::hasTable('records_retention_cases')) {
            DB::table('records_retention_cases')->updateOrInsert(
                ['record_type' => 'administrative_decision', 'record_id' => $decision],
                ['record_reference' => $row->decision_no ?: ('Decision #'.$decision), 'title' => $row->title ?: $row->summary, 'status' => 'active', 'closed_on' => now()->toDateString(), 'legal_hold' => false, 'created_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]
            );
        }
        $this->audit($request, 'updated', 'AdministrativeDecision', $decision, 'Advanced decision workflow: '.$action['action']);
        GovernanceAttestationService::record($request, 'decision_'.$action['action'], 'AdministrativeDecision', $decision, [
            'administrative_purpose' => $action['administrative_purpose'],
            'authority_reference' => $action['override_authority'] ?? $row->authority_reference ?? $row->source_reference ?? null,
            'evidence_reviewed' => true,
            'accuracy_confirmed' => true,
            'minimum_necessary_confirmed' => true,
            'no_conflict_confirmed' => true,
        ]);

        return back()->with('success', 'Decision workflow updated with safeguard attestation retained.');
    }

    public function rules(Request $request)
    {
        $this->allowGovernance($request);
        $this->ensureTables();
        $rules = DB::table('governance_rule_versions')->orderBy('rule_key')->orderByDesc('version_no')->get();

        return view('governance-control.rules', compact('rules'));
    }

    public function storeRule(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin() || $request->user()->hasAnyRole(['planning_officer', 'admin_group']), 403);
        $this->ensureTables();
        $data = $request->validate([
            'rule_key' => ['required', 'string', 'max:100'], 'title' => ['required', 'string', 'max:180'], 'authority_type' => ['nullable', 'string', 'max:80'],
            'authority_reference' => ['nullable', 'string', 'max:120'], 'effective_from' => ['required', 'date'], 'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'applicable_category' => ['nullable', 'string', 'max:180'], 'rule_definition' => ['required', 'string', 'max:10000'], 'implementation_notes' => ['nullable', 'string', 'max:6000'],
        ]);
        $latest = DB::table('governance_rule_versions')->where('rule_key', $data['rule_key'])->orderByDesc('version_no')->first();
        if ($latest && $data['effective_from'] <= $latest->effective_from) {
            throw ValidationException::withMessages([
                'effective_from' => 'A new version must start after the latest version effective date ('.$latest->effective_from.').',
            ]);
        }

        $newEnd = $data['effective_until'] ?? '9999-12-31';
        $overlapOlder = DB::table('governance_rule_versions')
            ->where('rule_key', $data['rule_key'])
            ->when($latest, fn ($q) => $q->where('id', '!=', $latest->id))
            ->whereDate('effective_from', '<=', $newEnd)
            ->where(function ($q) use ($data) {
                $q->whereNull('effective_until')->orWhereDate('effective_until', '>=', $data['effective_from']);
            })->exists();
        if ($overlapOlder) {
            throw ValidationException::withMessages(['effective_from' => 'This version overlaps an older historical rule period.']);
        }

        $version = ($latest->version_no ?? 0) + 1;
        $id = DB::transaction(function () use ($data, $version, $latest, $request) {
            if ($latest) {
                $previousEnd = Carbon::parse($data['effective_from'])->subDay()->toDateString();
                DB::table('governance_rule_versions')->where('id', $latest->id)->update([
                    'is_active' => false,
                    'effective_until' => $previousEnd,
                    'updated_at' => now(),
                ]);
            }

            return DB::table('governance_rule_versions')->insertGetId($data + [
                'version_no' => $version,
                'supersedes_id' => $latest->id ?? null,
                'is_active' => true,
                'created_by' => $request->user()->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
        $this->audit($request, 'created', 'GovernanceRuleVersion', $id, 'Created governance rule version '.$data['rule_key'].' v'.$version);

        return back()->with('success', 'New rule version created; prior version preserved for historical decisions.');
    }

    public function regulatory(Request $request)
    {
        $this->allowGovernance($request);
        $this->ensureTables();
        $changes = DB::table('regulatory_changes as r')->leftJoin('users as u', 'u.id', '=', 'r.owner_user_id')->select('r.*', 'u.name as owner_name')->orderByDesc('r.updated_at')->get();
        $users = $this->activeUsers();
        $circulars = Schema::hasTable('circulars') ? DB::table('circulars')->where('is_active', true)->orderByDesc('created_at')->limit(500)->get(['id', 'title', 'category', 'created_at']) : collect();
        $rules = DB::table('governance_rule_versions')->orderBy('rule_key')->orderByDesc('version_no')->get(['id', 'rule_key', 'version_no', 'title', 'effective_from', 'effective_until']);

        return view('governance-control.regulatory', compact('changes', 'users', 'circulars', 'rules'));
    }

    public function updateRegulatory(Request $request, int $change): RedirectResponse
    {
        $this->allowGovernance($request);
        $this->ensureTables();
        $row = DB::table('regulatory_changes')->where('id', $change)->first();
        abort_unless($row, 404);
        $data = $request->validate([
            'status' => ['required', 'in:received,assessing,planned,implementing,testing,implemented,not_applicable,closed'],
            'applicable' => ['nullable', 'in:1,0'], 'applicability_assessment' => ['nullable', 'string', 'max:6000'],
            'affected_rules_modules' => ['nullable', 'string', 'max:6000'], 'target_effective_on' => ['nullable', 'date'],
            'test_evidence' => ['nullable', 'string', 'max:6000'], 'implementation_evidence' => ['nullable', 'string', 'max:6000'],
            'circular_id' => ['nullable', 'integer', 'exists:circulars,id'],
            'affected_rule_version_ids' => ['nullable', 'array'], 'affected_rule_version_ids.*' => ['integer', 'exists:governance_rule_versions,id'],
            'implementation_tasks_text' => ['nullable', 'string', 'max:10000'],
            'owner_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);
        $order = ['received' => 0, 'assessing' => 1, 'planned' => 2, 'implementing' => 3, 'testing' => 4, 'implemented' => 5, 'closed' => 6];
        if ($data['status'] !== 'not_applicable') {
            if ($row->status === 'not_applicable' && $data['status'] === 'closed') {
                // permitted terminal close after a documented non-applicability decision
            } elseif (! isset($order[$data['status']]) || ! isset($order[$row->status]) || $order[$data['status']] < $order[$row->status] || $order[$data['status']] > $order[$row->status] + 1) {
                throw ValidationException::withMessages(['status' => 'Regulatory change stages must progress sequentially: Received → Assessing → Planned → Implementing → Testing → Implemented → Closed.']);
            }
        }
        if (in_array($data['status'], ['planned', 'implementing', 'testing', 'implemented'], true) && ($data['applicable'] ?? null) !== '1') {
            throw ValidationException::withMessages(['applicable' => 'Applicability must be positively assessed before implementation planning can proceed.']);
        }
        $ownerId = $data['owner_user_id'] ?? $row->owner_user_id;
        if (in_array($data['status'], ['planned', 'implementing', 'testing', 'implemented'], true) && empty($ownerId)) {
            throw ValidationException::withMessages(['owner_user_id' => 'Assign an implementation owner before advancing beyond applicability assessment.']);
        }
        $tasks = $this->parseImplementationTasks($data['implementation_tasks_text'] ?? null);
        if ($data['status'] === 'implemented' && collect($tasks)->contains(fn ($t) => ! $t['completed'])) {
            throw ValidationException::withMessages(['implementation_tasks_text' => 'All implementation tasks must be marked [x] before the change can be marked implemented.']);
        }
        if ($data['status'] === 'implemented' && (int) $ownerId === (int) $request->user()->id && ! $request->user()->isSuperAdmin()) {
            throw ValidationException::withMessages(['status' => 'Independent verification is required: the implementation owner cannot verify their own regulatory change.']);
        }
        if ($data['status'] === 'implemented' && empty($data['implementation_evidence'])) {
            throw ValidationException::withMessages(['implementation_evidence' => 'Implementation evidence is required before a regulatory change can be marked implemented.']);
        }
        if ($data['status'] === 'implemented' && empty($data['test_evidence'])) {
            throw ValidationException::withMessages(['test_evidence' => 'Testing/verification evidence is required before a regulatory change can be marked implemented.']);
        }
        $data['implemented_on'] = $data['status'] === 'implemented' ? now()->toDateString() : $row->implemented_on;
        if ($data['status'] === 'implemented') {
            $data['verified_on'] = now()->toDateString();
            $data['verified_by'] = $request->user()->id;
            $data['approved_by'] = $request->user()->id;
            $data['approved_at'] = now();
        }
        $data['affected_rule_version_ids'] = json_encode(array_values($data['affected_rule_version_ids'] ?? []));
        $data['implementation_tasks'] = json_encode($tasks);
        unset($data['implementation_tasks_text']);
        $data['updated_at'] = now();
        DB::table('regulatory_changes')->where('id', $change)->update($data);
        if ($data['status'] === 'implemented' && Schema::hasTable('records_retention_cases')) {
            DB::table('records_retention_cases')->updateOrInsert(
                ['record_type' => 'regulatory_change', 'record_id' => $change],
                [
                    'record_reference' => $row->change_no,
                    'title' => $row->title,
                    'status' => 'active',
                    'closed_on' => now()->toDateString(),
                    'legal_hold' => false,
                    'created_by' => $request->user()->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
        $this->audit($request, 'updated', 'RegulatoryChange', $change, 'Updated regulatory implementation status to '.$data['status']);

        return back()->with('success', 'Regulatory change updated.');
    }

    public function storeRegulatory(Request $request): RedirectResponse
    {
        $this->allowGovernance($request);
        $this->ensureTables();
        $data = $request->validate([
            'source_type' => ['required', 'string', 'max:60'], 'source_reference' => ['required', 'string', 'max:120'], 'title' => ['required', 'string', 'max:200'],
            'received_on' => ['nullable', 'date'], 'target_effective_on' => ['nullable', 'date'], 'owner_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'applicable' => ['nullable', 'in:1,0'], 'applicability_assessment' => ['nullable', 'string', 'max:6000'], 'affected_rules_modules' => ['nullable', 'string', 'max:6000'],
            'status' => ['required', 'in:received,assessing,planned,implementing,testing,implemented,not_applicable,closed'],
            'test_evidence' => ['nullable', 'string', 'max:6000'], 'implementation_evidence' => ['nullable', 'string', 'max:6000'],
            'circular_id' => ['nullable', 'integer', 'exists:circulars,id'],
            'affected_rule_version_ids' => ['nullable', 'array'], 'affected_rule_version_ids.*' => ['integer', 'exists:governance_rule_versions,id'],
            'implementation_tasks_text' => ['nullable', 'string', 'max:10000'],
        ]);
        if (in_array($data['status'], ['implementing', 'testing', 'implemented', 'closed'], true)) {
            throw ValidationException::withMessages(['status' => 'Register the change first, then progress it through the controlled implementation stages.']);
        }
        if (in_array($data['status'], ['planned'], true) && (($data['applicable'] ?? null) !== '1' || empty($data['owner_user_id']))) {
            throw ValidationException::withMessages(['status' => 'A planned change requires a positive applicability assessment and assigned owner.']);
        }
        $data['affected_rule_version_ids'] = json_encode(array_values($data['affected_rule_version_ids'] ?? []));
        $data['implementation_tasks'] = json_encode($this->parseImplementationTasks($data['implementation_tasks_text'] ?? null));
        unset($data['implementation_tasks_text']);
        $id = DB::table('regulatory_changes')->insertGetId($data + ['change_no' => $this->nextRef('GOV-CHG', 'regulatory_changes', 'id'), 'implemented_on' => null, 'created_at' => now(), 'updated_at' => now()]);
        $this->audit($request, 'created', 'RegulatoryChange', $id, 'Registered regulatory change '.$data['source_reference']);

        return back()->with('success', 'Regulatory change registered for implementation tracking.');
    }

    public function retention(Request $request)
    {
        $this->allowGovernance($request);
        $this->ensureTables();
        $cases = DB::table('records_retention_cases')->orderByDesc('updated_at')->get();

        return view('governance-control.retention', compact('cases'));
    }

    public function storeRetention(Request $request): RedirectResponse
    {
        $this->allowGovernance($request);
        $this->ensureTables();
        $data = $request->validate([
            'record_type' => ['required', 'string', 'max:100'], 'record_id' => ['nullable', 'integer'], 'record_reference' => ['nullable', 'string', 'max:160'], 'title' => ['required', 'string', 'max:200'],
            'closed_on' => ['nullable', 'date'], 'archived_on' => ['nullable', 'date'], 'retention_years' => ['nullable', 'integer', 'min:0', 'max:100'], 'legal_hold' => ['nullable', 'boolean'], 'hold_reason' => ['nullable', 'string', 'max:4000'],
        ]);
        $eligible = null;
        if (! empty($data['archived_on']) && isset($data['retention_years'])) {
            $eligible = Carbon::parse($data['archived_on'])->addYears((int) $data['retention_years'])->toDateString();
        }
        DB::table('records_retention_cases')->insert($data + ['status' => ! empty($data['archived_on']) ? 'archived' : 'active', 'legal_hold' => (bool) ($data['legal_hold'] ?? false), 'eligible_disposal_on' => $eligible, 'created_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('success', 'Retention/archival record created.');
    }

    public function retentionAction(Request $request, int $retention): RedirectResponse
    {
        $this->allowGovernance($request);
        $this->ensureTables();
        $row = DB::table('records_retention_cases')->where('id', $retention)->first();
        abort_unless($row, 404);
        $data = $request->validate([
            'action' => ['required', 'in:set_retention,archive,hold,release_hold,authorize_disposal,dispose'],
            'reason' => ['nullable', 'string', 'max:4000'],
            'evidence_reference' => ['nullable', 'string', 'max:255'],
            'retention_years' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);
        $u = ['updated_at' => now()];
        if ($data['action'] === 'set_retention') {
            if (! array_key_exists('retention_years', $data) || is_null($data['retention_years'])) {
                throw ValidationException::withMessages(['retention_years' => 'Enter the approved retention period.']);
            }
            $u['retention_years'] = (int) $data['retention_years'];
            if ($row->archived_on) {
                $u['eligible_disposal_on'] = Carbon::parse($row->archived_on)->addYears((int) $data['retention_years'])->toDateString();
            }
        }
        if ($data['action'] === 'archive') {
            $archive = now()->toDateString();
            $u['archived_on'] = $archive;
            $u['status'] = 'archived';
            if (! is_null($row->retention_years)) {
                $u['eligible_disposal_on'] = now()->addYears((int) $row->retention_years)->toDateString();
            }
        }
        if ($data['action'] === 'hold') {
            if (empty($data['reason'])) {
                throw ValidationException::withMessages(['reason' => 'A hold reason is required.']);
            } $u += ['legal_hold' => true, 'hold_reason' => $data['reason']];
        }
        if ($data['action'] === 'release_hold') {
            $u += ['legal_hold' => false, 'hold_released_at' => now(), 'hold_released_by' => $request->user()->id];
        }
        if ($data['action'] === 'authorize_disposal') {
            if ($row->legal_hold) {
                throw ValidationException::withMessages(['action' => 'Disposal cannot be authorized while a legal/administrative hold is active.']);
            }
            if (is_null($row->retention_years)) {
                throw ValidationException::withMessages(['action' => 'Assign an approved retention period before authorizing disposal.']);
            }
            if ((int) $row->created_by === (int) $request->user()->id && ! $request->user()->isSuperAdmin()) {
                throw ValidationException::withMessages(['action' => 'Independent disposal authorization is required; the officer who registered the retention case cannot authorize its disposal.']);
            }
            if (! $row->eligible_disposal_on || $row->eligible_disposal_on > now()->toDateString()) {
                throw ValidationException::withMessages(['action' => 'The record has not reached its disposal eligibility date.']);
            }
            $u += ['disposal_authorized_by' => $request->user()->id, 'status' => 'disposal_authorized'];
        }
        if ($data['action'] === 'dispose') {
            if ($row->legal_hold) {
                throw ValidationException::withMessages(['action' => 'Disposal is blocked by legal/administrative hold.']);
            }
            if (! $row->disposal_authorized_by) {
                throw ValidationException::withMessages(['action' => 'Disposal must be authorized first.']);
            }
            if ((int) $row->disposal_authorized_by === (int) $request->user()->id && ! $request->user()->isSuperAdmin()) {
                throw ValidationException::withMessages(['action' => 'The officer authorizing disposal cannot also record the physical/logical disposal.']);
            }
            if (empty($data['evidence_reference'])) {
                throw ValidationException::withMessages(['evidence_reference' => 'Disposition evidence/reference is required.']);
            }
            $u += ['disposed_at' => now(), 'disposed_by' => $request->user()->id, 'status' => 'disposed', 'disposal_evidence_reference' => $data['evidence_reference']];
        }
        DB::table('records_retention_cases')->where('id', $retention)->update($u);
        $this->audit($request, 'updated', 'RecordsRetentionCase', $retention, 'Retention action: '.$data['action']);

        return back()->with('success', 'Retention lifecycle updated.');
    }

    public function handovers(Request $request)
    {
        $this->allowOperational($request);
        $this->ensureTables();
        $handovers = DB::table('handover_certificates as h')->join('users as f', 'f.id', '=', 'h.from_user_id')->join('users as t', 't.id', '=', 'h.to_user_id')->leftJoin('users as s', 's.id', '=', 'h.supervisor_user_id')->select('h.*', 'f.name as from_name', 't.name as to_name', 's.name as supervisor_name')->orderByDesc('h.created_at')->get();
        $users = $this->activeUsers();

        return view('governance-control.handovers', compact('handovers', 'users'));
    }

    public function storeHandover(Request $request): RedirectResponse
    {
        $this->allowOperational($request);
        $this->ensureTables();
        $data = $request->validate([
            'from_user_id' => ['required', 'integer', 'exists:users,id'],
            'to_user_id' => ['required', 'integer', 'different:from_user_id', 'exists:users,id'],
            'supervisor_user_id' => ['required', 'integer', 'different:from_user_id', 'different:to_user_id', 'exists:users,id'],
            'effective_on' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $canCreateForOthers = $request->user()->isSuperAdmin() || $request->user()->hasAnyRole(['planning_officer', 'admin_group']);
        if (! $canCreateForOthers && (int) $data['from_user_id'] !== (int) $request->user()->id) {
            abort(403, 'Subject Officers may initiate only their own handover.');
        }
        $snapshot = $this->workloadSnapshot((int) $data['from_user_id']);
        $id = DB::table('handover_certificates')->insertGetId($data + ['handover_no' => $this->nextRef('HND', 'handover_certificates', 'id'), 'status' => 'draft', 'workload_snapshot' => json_encode($snapshot), 'created_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->audit($request, 'created', 'HandoverCertificate', $id, 'Created formal handover certificate');

        return back()->with('success', 'Handover certificate created with a point-in-time workload snapshot.');
    }

    public function acknowledgeHandover(Request $request, int $handover): RedirectResponse
    {
        $this->allowOperational($request);
        $this->ensureTables();
        $row = DB::table('handover_certificates')->where('id', $handover)->first();
        abort_unless($row, 404);
        $action = $request->validate(['action' => ['required', 'in:handover,accept,verify']])['action'];
        $uid = (int) $request->user()->id;
        $u = ['updated_at' => now()];
        $signatureId = Schema::hasTable('e_signatures')
            ? DB::table('e_signatures')->where('user_id', $uid)->where('is_active', true)->orderByDesc('id')->value('id')
            : null;

        if ($action === 'handover') {
            abort_unless($uid === (int) $row->from_user_id, 403, 'Only the outgoing officer may record the outgoing sign-off.');
            $u += ['handed_over_at' => now(), 'status' => 'handed_over', 'from_e_signature_id' => $signatureId];
        }
        if ($action === 'accept') {
            abort_unless($uid === (int) $row->to_user_id, 403, 'Only the incoming officer may accept the handover.');
            abort_if(! $row->handed_over_at, 422, 'Outgoing officer must sign off before incoming acceptance.');
            $u += ['accepted_at' => now(), 'status' => 'accepted', 'to_e_signature_id' => $signatureId];
        }
        if ($action === 'verify') {
            abort_unless($uid === (int) $row->supervisor_user_id, 403, 'Only the named supervisor may verify the handover.');
            abort_if(! $row->accepted_at, 422, 'Incoming officer must accept before supervisor verification.');
            $hash = hash('sha256', $row->handover_no.'|'.$row->workload_snapshot.'|'.$row->from_user_id.'|'.$row->to_user_id.'|'.now()->toIso8601String());
            $u += ['verified_at' => now(), 'status' => 'verified', 'content_hash' => $hash, 'supervisor_e_signature_id' => $signatureId];
        }
        DB::table('handover_certificates')->where('id', $handover)->update($u);

        if ($action === 'verify' && Schema::hasTable('records_retention_cases')) {
            DB::table('records_retention_cases')->updateOrInsert(
                ['record_type' => 'handover_certificate', 'record_id' => $handover],
                [
                    'record_reference' => $row->handover_no,
                    'title' => 'Formal responsibility handover certificate '.$row->handover_no,
                    'status' => 'active',
                    'closed_on' => now()->toDateString(),
                    'legal_hold' => false,
                    'created_by' => $request->user()->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
        $this->audit($request, 'updated', 'HandoverCertificate', $handover, 'Formal handover action: '.$action);

        return back()->with('success', 'Handover status updated and evidentiary sign-off retained.');
    }

    public function handoverPdf(Request $request, int $handover)
    {
        $this->allowOperational($request);
        $this->ensureTables();
        $row = DB::table('handover_certificates as h')->join('users as f', 'f.id', '=', 'h.from_user_id')->join('users as t', 't.id', '=', 'h.to_user_id')->leftJoin('users as s', 's.id', '=', 'h.supervisor_user_id')->where('h.id', $handover)->select('h.*', 'f.name as from_name', 't.name as to_name', 's.name as supervisor_name')->first();
        abort_unless($row, 404);
        $snapshot = json_decode($row->workload_snapshot ?: '{}', true) ?: [];
        $signatures = [
            'from' => $this->signatureDataUri($row->from_e_signature_id ?? null),
            'to' => $this->signatureDataUri($row->to_e_signature_id ?? null),
            'supervisor' => $this->signatureDataUri($row->supervisor_e_signature_id ?? null),
        ];

        return Pdf::loadView('governance-control.handover-pdf', compact('row', 'snapshot', 'signatures'))->setPaper('a4')->download($row->handover_no.'.pdf');
    }

    private function workloadSnapshot(int $userId): array
    {
        $employeeIds = [];
        if (Schema::hasTable('hr_responsibilities') && Schema::hasTable('employees')) {
            $positionIds = DB::table('hr_responsibilities')->where('user_id', $userId)->whereNull('ended_at')->where(function ($q) {
                $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', now()->toDateString());
            })->pluck('position_id');
            if ($positionIds->isNotEmpty()) {
                $employeeIds = DB::table('employees')->where('is_active', true)->whereIn('position_id', $positionIds)->pluck('id')->all();
            }
        }

        return [
            'employee_files' => count($employeeIds),
            'pending_promotions' => Schema::hasTable('employee_promotions') ? DB::table('employee_promotions')->whereIn('employee_id', $employeeIds ?: [-1])->where('status', '!=', 'approved')->count() : 0,
            'increment_cases' => Schema::hasTable('employee_increments') ? DB::table('employee_increments')->whereIn('employee_id', $employeeIds ?: [-1])->where('is_active', true)->count() : 0,
            'retirement_cases' => Schema::hasTable('retirement_projects') ? DB::table('retirement_projects')->whereIn('employee_id', $employeeIds ?: [-1])->where('status', '!=', 'completed')->count() : 0,
            'data_quality_issues' => Schema::hasTable('data_quality_issues') ? DB::table('data_quality_issues')->whereIn('employee_id', $employeeIds ?: [-1])->whereNotIn('status', ['resolved', 'verified'])->count() : 0,
            'open_escalations' => Schema::hasTable('hr_escalations') ? DB::table('hr_escalations')->where('assigned_to', $userId)->where('status', 'open')->count() : 0,
            'pending_decisions' => Schema::hasTable('administrative_decisions') ? DB::table('administrative_decisions')->where(function ($q) use ($userId, $employeeIds) {
                $q->where('requested_by', $userId)->orWhereIn('employee_id', $employeeIds ?: [-1]);
            })->whereIn('status', ['pending', 'draft', 'checked', 'recommended'])->count() : 0,
            'pending_corrections' => Schema::hasTable('employee_change_requests') ? DB::table('employee_change_requests')->whereIn('employee_id', $employeeIds ?: [-1])->whereIn('status', ['pending', 'governance_review'])->count() : 0,
            'acting_appointments' => Schema::hasTable('acting_appointments') ? DB::table('acting_appointments')->whereIn('employee_id', $employeeIds ?: [-1])->where('is_active', true)->where(function ($q) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', now()->toDateString());
            })->count() : 0,
            'service_letters' => Schema::hasTable('service_letters') ? DB::table('service_letters')->where('drafted_by', $userId)->whereIn('status', ['draft', 'pending_approval'])->count() : 0,
            'regulatory_actions' => Schema::hasTable('regulatory_changes') ? DB::table('regulatory_changes')->where('owner_user_id', $userId)->whereNotIn('status', ['implemented', 'closed', 'not_applicable'])->count() : 0,
            'captured_at' => now()->toIso8601String(),
        ];
    }

    private function parseImplementationTasks(?string $text): array
    {
        if (! $text) {
            return [];
        }

        return collect(preg_split('/\r\n|\r|\n/', $text))
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            ->map(function ($line) {
                $completed = (bool) preg_match('/^\[x\]\s*/i', $line);
                $task = preg_replace('/^\[(?:x| )\]\s*/i', '', $line);

                return ['task' => $task, 'completed' => $completed];
            })->values()->all();
    }

    private function signatureDataUri(?int $id): ?string
    {
        if (! $id || ! Schema::hasTable('e_signatures')) {
            return null;
        }
        $sig = DB::table('e_signatures')->where('id', $id)->where('is_active', true)->first();
        if (! $sig || ! $sig->file_path || ! Storage::disk('local')->exists($sig->file_path)) {
            return null;
        }

        return 'data:'.($sig->mime_type ?: 'image/png').';base64,'.base64_encode(Storage::disk('local')->get($sig->file_path));
    }

    private function activeUsers()
    {
        return Schema::hasTable('users') ? DB::table('users')->where('is_active', true)->orderBy('name')->get(['id', 'name', 'email']) : collect();
    }

    private function allowOperational(Request $r): void
    {
        abort_unless($r->user()->isSuperAdmin() || $r->user()->hasAnyRole(['planning_officer', 'admin_group', 'subject_officer']), 403);
    }

    private function allowGovernance(Request $r): void
    {
        abort_unless($r->user()->isSuperAdmin() || $r->user()->hasAnyRole(['planning_officer', 'admin_group']), 403);
    }

    private function ensureTables(): void
    {
        foreach (['governance_rule_versions', 'administrative_decisions', 'regulatory_changes', 'records_retention_cases', 'handover_certificates'] as $t) {
            abort_unless(Schema::hasTable($t), 503, 'Run php artisan migrate to enable Governance Control Phase.');
        }
        abort_unless(Schema::hasColumn('administrative_decisions', 'decision_no') && Schema::hasColumn('administrative_decisions','provenance'), 503, 'Run php artisan migrate to complete the unified Governance Decision schema.');
    }

    private function nextRef(string $prefix,string $table,string $column): string
    {
        $next = ((int) DB::table($table)->max($column)) + 1;

        return $prefix.'-'.now()->format('Y').'-'.str_pad((string) $next,4,'0',STR_PAD_LEFT);
    }

    private function audit(Request $r,string $action,string $type,int $id,string $description): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        } DB::table('audit_logs')->insert(['user_id' => $r->user()->id, 'action' => $action, 'auditable_type' => $type, 'auditable_id' => $id, 'description' => $description, 'old_values' => null, 'new_values' => null, 'ip_address' => $r->ip(), 'created_at' => now()]);
    }
}
