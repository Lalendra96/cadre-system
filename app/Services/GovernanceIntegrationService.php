<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActingAppointment;
use App\Models\AdministrativeDecision;
use App\Models\Employee;
use App\Models\EmployeeChangeRequest;
use App\Models\EmployeeIncrement;
use App\Models\RetirementProject;
use App\Models\TransferRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

final class GovernanceIntegrationService
{
    public static function enrichDecision(
        AdministrativeDecision $decision,
        string $processKey,
        ?Employee $employee,
        array $payload,
        User $requester,
        string $summary,
        ?string $sourceReference = null,
        ?string $sourceType = null,
        ?int $sourceId = null,
    ): AdministrativeDecision {
        if (! Schema::hasColumn('administrative_decisions', 'decision_no')) {
            return $decision;
        }

        $effectiveDate = self::effectiveDate($payload);
        $authority = self::resolveAuthority($processKey, $effectiveDate);
        $rule = self::resolveRule($processKey, $effectiveDate, $employee);
        [$inferredType,$inferredId] = self::inferSource($processKey, $payload);

        $decision->forceFill([
            'decision_no' => $decision->decision_no ?: self::reference('ADM', $decision->id),
            'title' => $decision->title ?: $summary,
            'source_type' => $sourceType ?: $inferredType ?: $processKey,
            'source_id' => $sourceId ?: $inferredId,
            'approval_authority_id' => $authority?->id,
            'rule_version_id' => $rule?->id,
            'authority_reference' => $authority?->reference_no ?: $authority?->authority_source ?: $sourceReference,
            'effective_date' => $effectiveDate,
            'prepared_by' => $requester->id,
            'provenance' => self::provenance($processKey, $employee, $payload, $sourceReference, $rule, $authority),
        ])->save();

        return $decision->refresh();
    }

    public static function registerCompletedWorkflow(
        string $processKey,
        string $title,
        User $preparedBy,
        User $approvedBy,
        Model $source,
        ?Employee $employee,
        array $provenance,
        ?int $checkedBy = null,
        ?int $recommendedBy = null,
        ?string $authorityReference = null,
        ?string $effectiveDate = null,
    ): AdministrativeDecision {
        $existing = AdministrativeDecision::query()
            ->where('source_type', $source::class)
            ->where('source_id', $source->getKey())
            ->first();
        if ($existing) {
            return $existing;
        }

        $authority = self::resolveAuthority($processKey, $effectiveDate);
        $rule = self::resolveRule($processKey, $effectiveDate, $employee);

        $decision = AdministrativeDecision::create([
            'decision_type' => $processKey,
            'employee_id' => $employee?->id,
            'summary' => $title,
            'payload' => $provenance,
            'source_reference' => $authorityReference,
            'status' => AdministrativeDecision::STATUS_APPROVED,
            'requested_by' => $preparedBy->id,
            'requested_at' => now(),
            'decided_by' => $approvedBy->id,
            'decided_at' => now(),
            'decision_reason' => 'Completed through the governed source workflow.',
            'applied_model_type' => $source::class,
            'applied_model_id' => $source->getKey(),
            'decision_no' => null,
            'title' => $title,
            'source_type' => $source::class,
            'source_id' => $source->getKey(),
            'approval_authority_id' => $authority?->id,
            'rule_version_id' => $rule?->id,
            'authority_reference' => $authority?->reference_no ?: $authority?->authority_source ?: $authorityReference,
            'effective_date' => $effectiveDate,
            'prepared_by' => $preparedBy->id,
            'checked_by' => $checkedBy,
            'recommended_by' => $recommendedBy,
            'approved_by' => $approvedBy->id,
            'checked_at' => $checkedBy ? now() : null,
            'recommended_at' => $recommendedBy ? now() : null,
            'approved_at' => now(),
            'provenance' => array_merge($provenance, [
                'process_key' => $processKey,
                'source_type' => $source::class,
                'source_id' => $source->getKey(),
                'rule_version_id' => $rule?->id,
                'authority_id' => $authority?->id,
                'captured_at' => now()->toIso8601String(),
            ]),
        ]);
        $decision->update(['decision_no' => self::reference('ADM', $decision->id)]);

        return $decision->refresh();
    }

    public static function assertAuthorityForStage(AdministrativeDecision $decision, User $user, string $stage): void
    {
        if ($user->isSuperAdmin() || ! $decision->approval_authority_id || ! Schema::hasTable('approval_authorities')) {
            return;
        }
        $authority = DB::table('approval_authorities')->where('id', $decision->approval_authority_id)->first();
        self::assertAuthorityRecord($authority, $user, $stage);
    }

    public static function assertProcessAuthority(string $processKey, ?string $effectiveDate, User $user, string $stage): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }
        $authority = self::resolveAuthority($processKey, $effectiveDate);
        if (! $authority) {
            return;
        }
        self::assertAuthorityRecord($authority, $user, $stage);
    }

    private static function assertAuthorityRecord(?object $authority, User $user, string $stage): void
    {
        if (! $authority || ! $authority->is_active) {
            throw ValidationException::withMessages(['authority' => 'The linked approval authority is not active.']);
        }
        $required = match ($stage) {
            'prepare' => $authority->preparer_role,
            'check' => $authority->checker_role,
            'approve' => $authority->approver_role,
            default => null,
        };
        if (! $required) {
            return;
        }

        $normalise = fn (string $v) => strtolower(trim(str_replace(['-', ' '], '_', $v)));
        $roleMatch = $user->hasRole($required) || collect($user->roles)->contains(fn ($r) => $normalise($r) === $normalise($required));
        $categoryMatch = $user->category && $normalise((string) $user->category->name) === $normalise((string) $required);
        if ($roleMatch || $categoryMatch) {
            return;
        }

        $delegated = false;
        if (Schema::hasTable('authority_delegations')) {
            $today = now()->toDateString();
            $delegated = DB::table('authority_delegations')
                ->where('to_user_id', $user->id)
                ->where('is_active', true)
                ->whereDate('starts_on', '<=', $today)
                ->whereDate('ends_on', '>=', $today)
                ->whereIn('scope', array_filter([$authority->process_key, $authority->process_label, $required]))
                ->exists();
        }
        if (! $delegated) {
            throw ValidationException::withMessages([
                'authority' => 'This stage requires authority role/category "'.$required.'" or a currently effective delegation for '.$authority->process_label.'.',
            ]);
        }
    }

    public static function resolveRule(string $processKey, ?string $effectiveDate, ?Employee $employee = null): ?object
    {
        if (! Schema::hasTable('governance_rule_versions')) {
            return null;
        }
        $date = $effectiveDate ?: now()->toDateString();
        $keys = self::processKeys($processKey);
        $q = DB::table('governance_rule_versions')->whereIn('rule_key', $keys)
            ->whereDate('effective_from', '<=', $date)
            ->where(function ($x) use ($date) {
                $x->whereNull('effective_until')->orWhereDate('effective_until', '>=', $date);
            });
        if ($employee?->position?->title) {
            $title = $employee->position->title;
            $q->where(function ($x) use ($title) {
                $x->whereNull('applicable_category')->orWhere('applicable_category', '')->orWhereRaw('LOWER(applicable_category) = ?', ['all'])->orWhere('applicable_category', $title);
            });
        }

        return $q->orderByDesc('version_no')->first();
    }

    public static function resolveAuthority(string $processKey, ?string $effectiveDate): ?object
    {
        if (! Schema::hasTable('approval_authorities')) {
            return null;
        }
        $date = $effectiveDate ?: now()->toDateString();

        return DB::table('approval_authorities')
            ->whereIn('process_key', self::processKeys($processKey))->where('is_active', true)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_from')->orWhereDate('effective_from', '<=', $date);
            })
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date);
            })
            ->orderByDesc('effective_from')->first();
    }

    private static function inferSource(string $processKey, array $payload): array
    {
        return match ($processKey) {
            'employee_correction' => [EmployeeChangeRequest::class, $payload['change_request_id'] ?? null],
            'retirement_completion' => [RetirementProject::class, $payload['retirement_project_id'] ?? null],
            'transfer_correction' => [TransferRecord::class, $payload['transfer_record_id'] ?? null],
            'acting_appointment_correction' => [ActingAppointment::class, $payload['acting_appointment_id'] ?? null],
            'increment_workflow' => [EmployeeIncrement::class, $payload['increment_id'] ?? null],
            default => [null, null],
        };
    }

    private static function effectiveDate(array $payload): ?string
    {
        foreach (['effective_date', 'increment_date', 'interdiction_date', 'start_date', 'retirement_date', 'date_confirmed', 'final_working_date'] as $key) {
            if (! empty($payload[$key])) {
                return substr((string) $payload[$key], 0, 10);
            }
        }

        return null;
    }

    private static function provenance(string $processKey, ?Employee $employee, array $payload, ?string $sourceReference, ?object $rule, ?object $authority): array
    {
        $sourceFacts = [];
        if ($employee) {
            $sourceFacts = array_filter([
                'date_of_birth' => optional($employee->date_of_birth)->toDateString(),
                'date_of_appointment' => optional($employee->date_of_appointment)->toDateString(),
                'retirement_age' => $employee->retirement_age,
                'position_id' => $employee->position_id,
                'subject_code_id' => $employee->subject_code_id,
                'unit_id' => $employee->unit_id,
                'last_increment_date' => optional($employee->last_increment_date)->toDateString(),
                'next_increment_date' => optional($employee->next_increment_date)->toDateString(),
            ], fn ($v) => ! is_null($v) && $v !== '');
        }

        return [
            'process_key' => $processKey,
            'employee_id' => $employee?->id,
            'employee_pay_no' => $employee?->pay_no,
            'source_reference' => $sourceReference,
            'source_facts' => $sourceFacts,
            'submitted_fields' => array_keys($payload),
            'rule_version_id' => $rule?->id,
            'rule_key' => $rule?->rule_key,
            'rule_version' => $rule?->version_no,
            'rule_effective_from' => $rule?->effective_from,
            'rule_effective_until' => $rule?->effective_until,
            'authority_id' => $authority?->id,
            'authority_reference' => $authority?->reference_no ?? null,
            'authority_source' => $authority?->authority_source ?? null,
            'method' => 'Captured automatically from the authoritative employee/service record and submitted workflow payload at decision submission time.',
            'captured_at' => now()->toIso8601String(),
        ];
    }

    private static function processKeys(string $processKey): array
    {
        $aliases = [
            'grade_change' => ['PROMOTION', 'GRADE_CHANGE'],
            'promotion' => ['PROMOTION', 'GRADE_CHANGE'],
            'increment_record' => ['INCREMENT', 'INCREMENT_RECORD'],
            'increment_workflow' => ['INCREMENT', 'INCREMENT_WORKFLOW'],
            'transfer_record' => ['TRANSFER', 'TRANSFER_RECORD'],
            'transfer_correction' => ['TRANSFER', 'TRANSFER_CORRECTION'],
            'acting_appointment' => ['ACTING_APPOINTMENT'],
            'acting_appointment_correction' => ['ACTING_APPOINTMENT', 'ACTING_APPOINTMENT_CORRECTION'],
            'confirmation_status' => ['CONFIRMATION', 'CONFIRMATION_STATUS'],
            'retirement_completion' => ['RETIREMENT', 'RETIREMENT_COMPLETION'],
            'employee_correction' => ['EMPLOYEE_CORRECTION'],
            'employee_bulk_correction' => ['EMPLOYEE_CORRECTION', 'EMPLOYEE_BULK_CORRECTION'],
            'leave_record' => ['LEAVE', 'LEAVE_RECORD'],
            'interdiction_record' => ['INTERDICTION', 'INTERDICTION_RECORD'],
            'cadre_review' => ['CADRE_REVIEW', 'CADRE_ESTABLISHMENT'],
        ];
        $base = strtoupper(str_replace(['-', ' '], '_', $processKey));
        $keys = array_merge([$processKey, $base, str_replace('_', '-', $base)], $aliases[$processKey] ?? []);

        return array_values(array_unique($keys));
    }

    private static function reference(string $prefix, int $id): string
    {
        return $prefix.'-'.now()->format('Y').'-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }
}
