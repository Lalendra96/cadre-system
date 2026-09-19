<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActingAppointment;
use App\Models\AdministrativeDecision;
use App\Models\AdministrativeDecisionEvent;
use App\Models\Employee;
use App\Models\EmployeeGradeRecord;
use App\Models\EmployeeIncrement;
use App\Models\EmployeeInterdiction;
use App\Models\EmployeeLeaveRecord;
use App\Models\TransferRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdministrativeDecisionService
{
    public static function request(
        string $type,
        Employee $employee,
        array $payload,
        User $requester,
        string $summary,
        ?string $sourceReference = null,
        ?int $businessRuleId = null
    ): AdministrativeDecision {
        if (! array_key_exists(
            $type,
            AdministrativeDecision::TYPES
        )) {
            throw ValidationException::withMessages([
                'decision_type' => 'Unsupported administrative decision type.',
            ]);
        }

        return DB::transaction(
            function () use (
                $type,
                $employee,
                $payload,
                $requester,
                $summary,
                $sourceReference,
                $businessRuleId
            ): AdministrativeDecision {
                $decision = AdministrativeDecision::create([
                    'decision_type' => $type,
                    'employee_id' => $employee->id,
                    'summary' => $summary,
                    'payload' => $payload,
                    'source_reference' => $sourceReference,
                    'business_rule_id' => $businessRuleId,
                    'status' => AdministrativeDecision::STATUS_PENDING,
                    'requested_by' => $requester->id,
                    'requested_at' => now(),
                ]);

                self::event(
                    $decision,
                    'submitted',
                    $requester,
                    'Submitted for independent human approval.',
                    [
                        'source_reference' => $sourceReference,
                    ]
                );

                $approvers = User::active()
                    ->get()
                    ->filter(
                        fn (User $user) => (
                            $user->isSuperAdmin()
                            || $user->isAdministrativeOfficer()
                        )
                        && $user->id !== $requester->id
                    );

                NotificationService::sendToMany(
                    $approvers,
                    type: 'administrative_decision_pending',
                    title: 'Administrative decision awaiting human approval',
                    body: $summary,
                    link: route(
                        'administrative-decisions.show',
                        $decision
                    )
                );

                return $decision;
            }
        );
    }

    public static function approve(
        AdministrativeDecision $decision,
        User $approver,
        string $reason
    ): AdministrativeDecision {
        if (! $decision->canBeDecidedBy($approver)) {
            throw ValidationException::withMessages([
                'approval' => 'This decision requires an independent Administrative Officer or Super Admin. A requester cannot approve their own request.',
            ]);
        }

        return DB::transaction(
            function () use (
                $decision,
                $approver,
                $reason
            ): AdministrativeDecision {
                $locked = AdministrativeDecision::query()
                    ->lockForUpdate()
                    ->findOrFail($decision->id);

                if (
                    $locked->status
                    !== AdministrativeDecision::STATUS_PENDING
                ) {
                    throw ValidationException::withMessages([
                        'approval' => 'This request has already been decided.',
                    ]);
                }

                [$modelType, $modelId] = self::apply(
                    $locked
                );

                $locked->update([
                    'status' => AdministrativeDecision::STATUS_APPROVED,
                    'decided_by' => $approver->id,
                    'decided_at' => now(),
                    'decision_reason' => $reason,
                    'applied_model_type' => $modelType,
                    'applied_model_id' => $modelId,
                ]);

                self::event(
                    $locked,
                    'approved_and_applied',
                    $approver,
                    $reason,
                    [
                        'applied_model_type' => $modelType,
                        'applied_model_id' => $modelId,
                    ]
                );

                NotificationService::send(
                    $locked->requester,
                    type: 'administrative_decision_approved',
                    title: 'Administrative decision approved',
                    body: $locked->summary,
                    link: route(
                        'administrative-decisions.show',
                        $locked
                    )
                );

                return $locked->fresh();
            }
        );
    }

    public static function reject(
        AdministrativeDecision $decision,
        User $approver,
        string $reason
    ): AdministrativeDecision {
        if (! $decision->canBeDecidedBy($approver)) {
            throw ValidationException::withMessages([
                'approval' => 'This decision requires an independent Administrative Officer or Super Admin. A requester cannot decide their own request.',
            ]);
        }

        return DB::transaction(
            function () use (
                $decision,
                $approver,
                $reason
            ): AdministrativeDecision {
                $locked = AdministrativeDecision::query()
                    ->lockForUpdate()
                    ->findOrFail($decision->id);

                if (
                    $locked->status
                    !== AdministrativeDecision::STATUS_PENDING
                ) {
                    throw ValidationException::withMessages([
                        'approval' => 'This request has already been decided.',
                    ]);
                }

                $locked->update([
                    'status' => AdministrativeDecision::STATUS_REJECTED,
                    'decided_by' => $approver->id,
                    'decided_at' => now(),
                    'decision_reason' => $reason,
                ]);

                self::event(
                    $locked,
                    'rejected',
                    $approver,
                    $reason
                );

                NotificationService::send(
                    $locked->requester,
                    type: 'administrative_decision_rejected',
                    title: 'Administrative decision returned',
                    body: $locked->summary,
                    link: route(
                        'administrative-decisions.show',
                        $locked
                    )
                );

                return $locked->fresh();
            }
        );
    }

    private static function apply(
        AdministrativeDecision $decision
    ): array {
        $employee = $decision->employee;
        $payload = $decision->payload;
        $requesterId = $decision->requested_by;

        return match ($decision->decision_type) {
            'grade_change' => self::applyGradeChange(
                $employee,
                $payload,
                $requesterId
            ),
            'increment_record' => self::applyIncrement(
                $employee,
                $payload,
                $requesterId
            ),
            'transfer_record' => self::applyTransfer(
                $employee,
                $payload,
                $requesterId
            ),
            'acting_appointment' => self::applyActingAppointment(
                $employee,
                $payload,
                $requesterId
            ),
            'interdiction_record' => self::applyInterdiction(
                $employee,
                $payload,
                $requesterId
            ),
            'confirmation_status' => self::applyConfirmation(
                $employee,
                $payload
            ),
            'leave_record' => self::applyLeaveRecord(
                $employee,
                $payload,
                $requesterId
            ),
            default => throw ValidationException::withMessages([
                'decision_type'
                    => 'The requested administrative action is not supported.',
            ]),
        };
    }

    private static function applyGradeChange(
        Employee $employee,
        array $payload,
        int $requesterId
    ): array {
        $employee->gradeRecords()
            ->active()
            ->whereNull('end_date')
            ->update([
                'end_date' => Carbon::parse(
                    $payload['effective_date']
                )->subDay(),
            ]);

        $record = EmployeeGradeRecord::create([
            'employee_id' => $employee->id,
            'position_grade_id' => $payload['position_grade_id'],
            'effective_date' => $payload['effective_date'],
            'reference_no' => $payload['reference_no'] ?? null,
            'notes' => $payload['notes'] ?? null,
            'recorded_by' => $requesterId,
            'is_active' => true,
        ]);

        AuditLogService::created(
            $record,
            'Applied independently approved grade decision.'
        );

        return [
            EmployeeGradeRecord::class,
            $record->id,
        ];
    }

    private static function applyIncrement(
        Employee $employee,
        array $payload,
        int $requesterId
    ): array {
        $record = EmployeeIncrement::create([
            'employee_id' => $employee->id,
            'increment_date' => $payload['increment_date'],
            'amount' => $payload['amount'] ?? null,
            'reference_no' => $payload['reference_no'] ?? null,
            'notes' => $payload['notes'] ?? null,
            'recorded_by' => $requesterId,
            'is_active' => true,
        ]);

        AuditLogService::created(
            $record,
            'Applied independently approved increment record.'
        );

        return [
            EmployeeIncrement::class,
            $record->id,
        ];
    }

    private static function applyTransfer(
        Employee $employee,
        array $payload,
        int $requesterId
    ): array {
        $record = TransferRecord::create(
            $payload + [
                'employee_id' => $employee->id,
                'employee_name' => $employee->name,
                'designation' => $employee->position?->title
                    ?? ($payload['designation'] ?? null),
                'recorded_by' => $requesterId,
                'is_active' => true,
            ]
        );

        AuditLogService::created(
            $record,
            'Applied independently approved transfer record.'
        );

        return [
            TransferRecord::class,
            $record->id,
        ];
    }

    private static function applyActingAppointment(
        Employee $employee,
        array $payload,
        int $requesterId
    ): array {
        $record = ActingAppointment::create(
            $payload + [
                'employee_id' => $employee->id,
                'created_by' => $requesterId,
                'is_active' => true,
            ]
        );

        AuditLogService::created(
            $record,
            'Applied independently approved acting appointment.'
        );

        return [
            ActingAppointment::class,
            $record->id,
        ];
    }

    private static function applyInterdiction(
        Employee $employee,
        array $payload,
        int $requesterId
    ): array {
        $record = EmployeeInterdiction::create(
            $payload + [
                'employee_id' => $employee->id,
                'recorded_by' => $requesterId,
                'is_active' => true,
            ]
        );

        AuditLogService::created(
            $record,
            'Applied independently approved interdiction / disciplinary record.'
        );

        $planningOfficers = User::active()
            ->havingRole(
                User::ROLE_PLANNING_OFFICER
            )
            ->get();

        NotificationService::sendToMany(
            $planningOfficers,
            type: 'employee_interdiction_approved',
            title: 'Approved interdiction record applied',
            body: $employee->display_name
                . ' has an independently approved interdiction record effective '
                . $record->interdiction_date->format('d M Y')
                . '.',
            link: route(
                'employee-interdictions.index',
                $employee
            )
        );

        return [
            EmployeeInterdiction::class,
            $record->id,
        ];
    }

    private static function applyConfirmation(
        Employee $employee,
        array $payload
    ): array {
        $old = $employee->getOriginal();

        $employee->update([
            'is_confirmed' => (bool) $payload['is_confirmed'],
            'date_confirmed' => $payload['date_confirmed'] ?? null,
            'confirmation_reference_no'
                => $payload['confirmation_reference_no'] ?? null,
        ]);

        AuditLogService::updated(
            $employee,
            $old,
            'Applied independently approved confirmation-in-service decision.'
        );

        return [
            Employee::class,
            $employee->id,
        ];
    }

    private static function applyLeaveRecord(
        Employee $employee,
        array $payload,
        int $requesterId
    ): array {
        $record = EmployeeLeaveRecord::create(
            $payload + [
                'employee_id' => $employee->id,
                'recorded_by' => $requesterId,
                'is_active' => true,
            ]
        );

        AuditLogService::created(
            $record,
            'Applied independently approved leave record.'
        );

        return [
            EmployeeLeaveRecord::class,
            $record->id,
        ];
    }

    private static function event(
        AdministrativeDecision $decision,
        string $event,
        ?User $actor,
        ?string $reason = null,
        array $metadata = []
    ): void {
        AdministrativeDecisionEvent::create([
            'administrative_decision_id' => $decision->id,
            'event' => $event,
            'actor_id' => $actor?->id,
            'reason' => $reason,
            'metadata' => $metadata ?: null,
            'created_at' => now(),
        ]);
    }
}
