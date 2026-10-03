<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class AdvancedGovernanceService
{
    public function refreshEligibility(): int
    {
        if (! Schema::hasTable('administrative_eligibility_findings')) {
            return 0;
        }

        $count = 0;
        Employee::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->chunkById(250, function (Collection $employees) use (&$count): void {
                foreach ($employees as $employee) {
                    foreach ($this->evaluateEmployee($employee) as $finding) {
                        DB::table('administrative_eligibility_findings')->updateOrInsert(
                            [
                                'employee_id' => $employee->id,
                                'finding_type' => $finding['finding_type'],
                                'target_date' => $finding['target_date'],
                            ],
                            $finding + [
                                'employee_id' => $employee->id,
                                'updated_at' => now(),
                                'created_at' => now(),
                            ]
                        );
                        $count++;
                    }
                }
            });

        return $count;
    }

    public function evaluateEmployee(Employee $employee): array
    {
        $findings = [];
        $today = now()->startOfDay();

        if (! $employee->is_confirmed && $employee->date_of_appointment) {
            $target = Carbon::parse($employee->date_of_appointment)->addYears(3);
            if ($target->lte($today->copy()->addMonths(6))) {
                $findings[] = $this->finding(
                    'confirmation',
                    $target,
                    $target->isPast() ? 'due' : 'approaching',
                    ['date_of_appointment' => (string) $employee->date_of_appointment],
                    ['appointment_letter', 'service_report'],
                    'Employee is approaching or has passed the configured confirmation review point. Human administrative review is required.'
                );
            }
        }

        if ($employee->next_increment_date) {
            $target = Carbon::parse($employee->next_increment_date);
            if ($target->lte($today->copy()->addMonths(3))) {
                $findings[] = $this->finding(
                    'increment',
                    $target,
                    $target->isPast() ? 'due' : 'approaching',
                    ['next_increment_date' => (string) $employee->next_increment_date],
                    ['increment_certificate', 'attendance_or_leave_review'],
                    'Increment date is approaching. This is a review prompt only and does not grant the increment.'
                );
            }
        }

        if ($employee->professional_registration_expiry) {
            $target = Carbon::parse($employee->professional_registration_expiry);
            if ($target->lte($today->copy()->addMonths(6))) {
                $findings[] = $this->finding(
                    'registration_expiry',
                    $target,
                    $target->isPast() ? 'overdue' : 'approaching',
                    ['registration_no' => $employee->professional_registration_no],
                    ['current_professional_registration'],
                    'Professional registration is approaching expiry or has expired and should be verified.'
                );
            }
        }

        if ($employee->date_of_birth && $employee->retirement_age) {
            $target = Carbon::parse($employee->date_of_birth)->addYears((int) $employee->retirement_age);
            if ($target->lte($today->copy()->addMonths(18))) {
                $findings[] = $this->finding(
                    'retirement',
                    $target,
                    $target->isPast() ? 'due' : 'approaching',
                    [
                        'date_of_birth' => (string) $employee->date_of_birth,
                        'retirement_age' => (int) $employee->retirement_age,
                    ],
                    ['verified_dob', 'service_record', 'pension_documents', 'clearance'],
                    'Projected retirement date is within the review horizon. Final retirement action remains an authorised administrative decision.'
                );
            }
        }

        if (Schema::hasTable('employee_exam_records')) {
            $passedEb = DB::table('employee_exam_records')
                ->where('employee_id', $employee->id)
                ->where('exam_type', 'efficiency_bar')
                ->where('result', 'pass')
                ->exists();

            if (! $passedEb && $employee->date_current_grade) {
                $target = Carbon::parse($employee->date_current_grade)->addYears(3);
                if ($target->lte($today->copy()->addMonths(9))) {
                    $findings[] = $this->finding(
                        'efficiency_bar',
                        $target,
                        $target->isPast() ? 'due' : 'approaching',
                        ['date_current_grade' => (string) $employee->date_current_grade],
                        ['efficiency_bar_result'],
                        'No passed efficiency-bar record was found and the review point is approaching.'
                    );
                }
            }
        }

        if (Schema::hasTable('position_grades') && $employee->position_id && $employee->date_current_grade) {
            $nextGrade = DB::table('position_grades')
                ->where('position_id', $employee->position_id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->first();

            if ($nextGrade && Carbon::parse($employee->date_current_grade)->lte($today->copy()->subYears(3))) {
                $findings[] = $this->finding(
                    'promotion_review',
                    $today,
                    'review',
                    ['position_id' => $employee->position_id, 'date_current_grade' => (string) $employee->date_current_grade],
                    ['service_minute_criteria', 'performance_or_service_evidence', 'eb_evidence'],
                    'Service duration indicates that a promotion eligibility review may be appropriate. The rules engine and authorised officers must determine applicability.'
                );
            }
        }

        return $findings;
    }

    public function forecast(Carbon $asAt, Carbon $until): array
    {
        $active = Employee::query()->where('is_active', true)->count();
        $retirements = Employee::query()
            ->where('is_active', true)
            ->whereNotNull('date_of_birth')
            ->get(['date_of_birth', 'retirement_age'])
            ->filter(function (Employee $employee) use ($asAt, $until): bool {
                $date = Carbon::parse($employee->date_of_birth)->addYears((int) $employee->retirement_age);

                return $date->betweenIncluded($asAt, $until);
            })
            ->count();

        $transfersOut = Schema::hasTable('transfer_records')
            ? DB::table('transfer_records')
                ->where('is_active', true)
                ->where('direction', 'OUT')
                ->whereBetween('effective_date', [$asAt->toDateString(), $until->toDateString()])
                ->count()
            : 0;

        $transfersIn = Schema::hasTable('transfer_records')
            ? DB::table('transfer_records')
                ->where('is_active', true)
                ->where('direction', 'IN')
                ->whereBetween('effective_date', [$asAt->toDateString(), $until->toDateString()])
                ->count()
            : 0;

        $appointments = Schema::hasTable('acting_appointments')
            ? DB::table('acting_appointments')
                ->where('is_active', true)
                ->whereBetween('start_date', [$asAt->toDateString(), $until->toDateString()])
                ->count()
            : 0;

        return [
            'as_at' => $asAt->toDateString(),
            'until' => $until->toDateString(),
            'current_active' => $active,
            'confirmed_retirements' => $retirements,
            'transfers_out' => $transfersOut,
            'transfers_in' => $transfersIn,
            'appointments' => $appointments,
            'projected_active' => max(0, $active - $retirements - $transfersOut + $transfersIn),
        ];
    }

    public function missingDocumentIntelligence(int $limit = 50): Collection
    {
        if (! Schema::hasTable('document_requirements')
            || ! Schema::hasTable('employee_lifecycle_events')
            || ! Schema::hasTable('employee_documents')) {
            return collect();
        }

        $rows = DB::table('employee_lifecycle_events as event')
            ->join('employees as employee', 'employee.id', '=', 'event.employee_id')
            ->join('document_requirements as requirement', function ($join): void {
                $join->on('requirement.lifecycle_event', '=', 'event.event_type')
                    ->where('requirement.is_active', true)
                    ->where('requirement.is_mandatory', true);
            })
            ->leftJoin('employee_documents as document', function ($join): void {
                $join->on('document.employee_id', '=', 'event.employee_id')
                    ->on('document.category', '=', 'requirement.document_category');
            })
            ->whereNull('document.id')
            ->select([
                'event.id as lifecycle_event_id',
                'event.event_type',
                'event.effective_date',
                'employee.id as employee_id',
                'requirement.document_category',
                'requirement.label as requirement_label',
            ])
            ->orderByDesc('event.effective_date')
            ->limit($limit)
            ->get();

        return PersonnelDisplayService::hydrateEmployeeRows($rows);
    }

    public function temporalSnapshot(Carbon $date): array
    {
        $events = Schema::hasTable('establishment_temporal_events')
            ? DB::table('establishment_temporal_events')
                ->whereDate('effective_date', '<=', $date->toDateString())
                ->orderBy('effective_date')
                ->orderBy('id')
                ->get()
            : collect();

        $state = [];
        foreach ($events as $event) {
            $key = $event->entity_type.':'.$event->entity_id;
            $state[$key] = json_decode((string) $event->after_state, true) ?? [];
        }

        return [
            'date' => $date->toDateString(),
            'event_count' => $events->count(),
            'recent_events' => $events->slice(-12)->values()->map(fn ($event) => [
                'effective_date' => Carbon::parse($event->effective_date)->toDateString(),
                'event_type' => str_replace('_', ' ', $event->event_type),
                'entity_type' => str_replace('_', ' ', $event->entity_type),
            ])->all(),
            'entities' => $state,
            'coverage_note' => $events->isEmpty()
                ? 'No temporal events have been captured yet. Historical accuracy improves as authoritative events are recorded or backfilled.'
                : 'Snapshot reconstructed from effective-dated authoritative temporal events.',
        ];
    }

    private function finding(
        string $type,
        Carbon $target,
        string $status,
        array $facts,
        array $missingEvidence,
        string $explanation
    ): array {
        return [
            'finding_type' => $type,
            'target_date' => $target->toDateString(),
            'status' => $status,
            'severity' => in_array($status, ['due', 'overdue'], true) ? 'warning' : 'info',
            'facts' => json_encode($facts, JSON_THROW_ON_ERROR),
            'missing_evidence' => json_encode($missingEvidence, JSON_THROW_ON_ERROR),
            'matched_rules' => json_encode([], JSON_THROW_ON_ERROR),
            'explanation' => $explanation,
            'requires_human_decision' => true,
            'evaluated_at' => now(),
        ];
    }
}
