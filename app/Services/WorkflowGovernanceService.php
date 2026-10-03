<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DuplicateResolutionCase;
use App\Models\EmployeeDocument;
use App\Models\EmployeePromotion;
use App\Models\HrEscalation;
use App\Models\ReconciliationIssue;
use App\Models\RecruitmentVacancy;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/** Central, auditable state transitions for the remaining workforce workflows. */
final class WorkflowGovernanceService
{
    public static function openReconciliation(array $data, User $actor): ReconciliationIssue
    {
        abort_unless(self::manager($actor), 403);

        return ReconciliationIssue::updateOrCreate(
            ['position_id' => $data['position_id'], 'year' => $data['year'], 'month' => $data['month']],
            [
                'severity' => $data['severity'],
                'status' => 'open',
                'evidence' => $data['evidence'],
                'assigned_to' => $actor->id,
            ],
        );
    }

    public static function createVacancy(array $data, User $actor): RecruitmentVacancy
    {
        abort_unless(self::manager($actor), 403);

        return RecruitmentVacancy::create(
            $data + ['created_by' => $actor->id, 'identified_on' => today(), 'status' => 'identified'],
        );
    }

    public static function openDuplicate(array $data, User $actor): DuplicateResolutionCase
    {
        abort_unless(self::manager($actor), 403);

        return DuplicateResolutionCase::firstOrCreate(
            [
                'primary_employee_id' => $data['primary_employee_id'],
                'duplicate_employee_id' => $data['duplicate_employee_id'],
            ],
            ['match_key' => $data['match_key'], 'status' => 'open'],
        );
    }

    public static function verifyDocument(
        EmployeeDocument $document,
        User $actor,
        bool $approved,
        ?string $note = null,
    ): EmployeeDocument {
        abort_unless(self::manager($actor), 403);
        $document->update([
            'verification_status' => $approved ? 'verified' : 'rejected',
            'verified_by' => $actor->id,
            'verified_at' => now(),
            'verification_note' => $note,
        ]);
        AuditLogService::updated($document, $document->getOriginal(), 'Document verification decision recorded.');
        if ($approved && Schema::hasTable('records_retention_cases')) {
            DB::table('records_retention_cases')->updateOrInsert(
                ['record_type' => 'employee_document', 'record_id' => $document->id],
                [
                    'record_reference' => $document->reference_no ?: ('Employee document #'.$document->id),
                    'title' => $document->title,
                    'status' => 'active',
                    'closed_on' => null,
                    'legal_hold' => false,
                    'created_by' => $actor->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        return $document->refresh();
    }

    public static function createPromotion(array $data, User $actor): EmployeePromotion
    {
        abort_unless(self::manager($actor), 403);
        GovernanceIntegrationService::assertProcessAuthority('promotion', $data['effective_date'] ?? null, $actor, 'prepare');

        return DB::transaction(function () use ($data, $actor) {
            $promotion = EmployeePromotion::create($data + ['prepared_by' => $actor->id, 'status' => 'prepared']);
            AuditLogService::created($promotion, 'Promotion prepared for independent checking and approval.');

            return $promotion;
        });
    }

    public static function transitionPromotion(EmployeePromotion $promotion, User $actor, string $transition): void
    {
        abort_unless(self::manager($actor), 403);
        $from = $promotion->status;
        if ($transition === 'checked') {
            GovernanceIntegrationService::assertProcessAuthority('promotion', optional($promotion->effective_date)->toDateString(), $actor, 'check');
        }
        if ($transition === 'approved') {
            GovernanceIntegrationService::assertProcessAuthority('promotion', optional($promotion->effective_date)->toDateString(), $actor, 'approve');
        }
        $allowed = ['prepared' => 'checked', 'checked' => 'recommended', 'recommended' => 'approved'];
        if (($allowed[$from] ?? null) !== $transition) {
            throw ValidationException::withMessages([
                'status' => 'Promotion transitions must follow Prepared → Checked → Recommended → Approved.',
            ]);
        }

        $priorActors = array_filter([
            (int) $promotion->prepared_by,
            (int) $promotion->checked_by,
            (int) $promotion->recommended_by,
        ]);
        if (in_array((int) $actor->id, $priorActors, true)) {
            throw ValidationException::withMessages([
                'status' => 'Segregation of Duties requires a different officer for each promotion stage.',
            ]);
        }

        $fields = ['status' => $transition];
        if ($transition === 'checked') {
            $fields += ['checked_by' => $actor->id, 'checked_at' => now()];
        }
        if ($transition === 'recommended') {
            $fields += ['recommended_by' => $actor->id, 'recommended_at' => now()];
        }
        if ($transition === 'approved') {
            $fields += ['approved_by' => $actor->id, 'approved_at' => now()];
        }
        $promotion->update($fields);
        AuditLogService::updated($promotion, ['status' => $from], "Promotion moved to {$transition}.");

        if ($transition === 'approved') {
            $promotion->load('employee.position');
            $prepared = User::findOrFail($promotion->prepared_by);
            $decision = GovernanceIntegrationService::registerCompletedWorkflow(
                'promotion',
                'Promotion of '.($promotion->employee?->display_name ?? 'employee'),
                $prepared,
                $actor,
                $promotion,
                $promotion->employee,
                [
                    'from_grade_id' => $promotion->from_grade_id,
                    'to_grade_id' => $promotion->to_grade_id,
                    'effective_date' => optional($promotion->effective_date)->toDateString(),
                    'reference_no' => $promotion->reference_no,
                    'justification' => $promotion->justification,
                ],
                $promotion->checked_by,
                $promotion->recommended_by,
                $promotion->reference_no,
                optional($promotion->effective_date)->toDateString(),
            );
            $promotion->update(['administrative_decision_id' => $decision->id]);
        }
    }

    public static function resolveReconciliation(
        ReconciliationIssue $issue,
        User $actor,
        string $explanation,
        string $note,
    ): void {
        abort_unless(self::manager($actor), 403);
        if ($issue->status !== 'open' || trim($explanation) === '' || trim($note) === '') {
            throw ValidationException::withMessages([
                'status' => 'An open issue needs evidence and a resolution note.',
            ]);
        }
        $issue->update([
            'status' => 'resolved',
            'explanation' => $explanation,
            'resolution_note' => $note,
            'assigned_to' => $actor->id,
            'resolved_at' => now(),
        ]);
    }

    public static function verifyReconciliation(ReconciliationIssue $issue, User $actor): void
    {
        abort_unless(self::manager($actor) && $issue->status === 'resolved' && $issue->assigned_to !== $actor->id, 403);
        $issue->update(['status' => 'verified', 'verified_by' => $actor->id, 'verified_at' => now()]);
    }

    public static function advanceVacancy(RecruitmentVacancy $vacancy, User $actor, string $status): void
    {
        abort_unless(self::manager($actor), 403);
        $order = [
            'identified' => 0,
            'requested' => 1,
            'shortlisting' => 2,
            'offered' => 3,
            'filled' => 4,
            'cancelled' => 99,
        ];
        if (
            ! array_key_exists($status, $order) ||
            ! array_key_exists($vacancy->status, $order) ||
            ($status !== 'cancelled' && $order[$status] < $order[$vacancy->status])
        ) {
            throw ValidationException::withMessages(['status' => 'Recruitment status cannot move backwards.']);
        }
        $vacancy->update([
            'status' => $status,
            'requested_on' => $status === 'requested' ? today() : $vacancy->requested_on,
            'filled_on' => $status === 'filled' ? today() : $vacancy->filled_on,
        ]);
    }

    public static function resolveDuplicate(DuplicateResolutionCase $case, User $actor, string $note): void
    {
        abort_unless(self::manager($actor), 403);
        if (trim($note) === '') {
            throw ValidationException::withMessages([
                'resolution_note' => 'Explain why the records are duplicates and which record remains authoritative.',
            ]);
        }
        $case->update([
            'status' => 'resolved',
            'resolution_note' => $note,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
        ]);
    }

    public static function escalate(
        string $sourceType,
        int $sourceId,
        string $rule,
        User $recipient,
        string $severity = 'warning',
        ?string $dueAt = null,
    ): HrEscalation {
        return HrEscalation::firstOrCreate(
            ['source_type' => $sourceType, 'source_id' => $sourceId, 'rule_code' => $rule, 'status' => 'open'],
            ['severity' => $severity, 'assigned_to' => $recipient->id, 'due_at' => $dueAt],
        );
    }

    public static function acknowledgeEscalation(HrEscalation $escalation, User $actor): void
    {
        abort_unless($escalation->assigned_to === $actor->id || self::manager($actor), 403);
        $escalation->update(['status' => 'acknowledged', 'acknowledged_at' => now()]);
    }

    public static function resolveEscalation(HrEscalation $escalation, User $actor, string $note): void
    {
        abort_unless(self::manager($actor) && trim($note) !== '', 403);
        $escalation->update(['status' => 'resolved', 'resolved_at' => now(), 'resolution_note' => $note]);
    }

    private static function manager(User $actor): bool
    {
        return $actor->is_active && ($actor->isSuperAdmin() || $actor->isPlanningOfficer() || $actor->isAdminGroup());
    }
}
