<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Employee;
use App\Models\HrReassignmentCase;
use App\Models\HrResponsibility;
use App\Models\Position;
use App\Models\User;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HrIntelligenceService
{
    public static function isManager(User $user): bool
    {
        return $user->is_active && ($user->isSuperAdmin() || $user->isPlanningOfficer() || $user->isHrRecordsManager());
    }

    /** Called by the scheduled job and explicit administrative refresh. */
    public static function synchronize(?User $actor = null): array
    {
        try {
            return Cache::lock('hr-intelligence-reconcile', 900)->block(1, fn () => self::runSynchronization($actor));
        } catch (LockTimeoutException $exception) {
            throw ValidationException::withMessages([
                'refresh' => 'An HR intelligence refresh is already running. Try again after it finishes.',
            ]);
        }
    }

    private static function runSynchronization(?User $actor): array
    {
        $map = HrResponsibilityService::recipientMap();
        $changed = 0;
        Employee::withTrashed()
            ->orderBy('id')
            ->chunkById(200, function ($employees) use ($actor, $map, &$changed) {
                foreach ($employees as $employee) {
                    $changed += (int) self::observe($employee->id, $map, $actor);
                }
            });
        self::coverageAlerts($map);
        DB::table('hr_intelligence_status')
            ->where('id', 1)
            ->update(['last_completed_at' => now(), 'changes_recorded' => $changed]);

        return ['changed' => $changed, 'checked_at' => now()->toDateTimeString()];
    }

    public static function observe(int $employeeId, Collection $map, ?User $actor = null): bool
    {
        return DB::transaction(function () use ($employeeId, $map, $actor) {
            // Serializes duplicate observations and case creation for one employee.
            $employee = Employee::withTrashed()->lockForUpdate()->findOrFail($employeeId);
            $state = self::currentState($employee, $map);
            $fingerprint = HrOwnershipPolicy::fingerprint($state);
            $previous = DB::table('hr_employee_ownership')->where('employee_id', $employeeId)->first();
            if ($previous && hash_equals($previous->fingerprint, $fingerprint)) {
                return false;
            }
            $before = $previous
                ? HrOwnershipPolicy::state(
                    $previous->position_id === null ? null : (int) $previous->position_id,
                    (bool) $previous->employee_active,
                    json_decode($previous->owner_ids, true, 512, JSON_THROW_ON_ERROR),
                )
                : null;
            $kind =
                $before === null
                    ? 'baseline'
                    : ($before['position_id'] !== $state['position_id']
                        ? 'position_changed'
                        : 'ownership_changed');
            $eventId = DB::table('hr_ownership_events')->insertGetId([
                'employee_id' => $employeeId,
                'kind' => $kind,
                'previous_state' => $before === null ? null : json_encode($before, JSON_THROW_ON_ERROR),
                'current_state' => json_encode($state, JSON_THROW_ON_ERROR),
                'recorded_by' => $actor?->id,
                'observed_at' => now(),
            ]);
            $values = [
                'position_id' => $state['position_id'],
                'employee_active' => $state['active'],
                'owner_ids' => json_encode($state['owner_ids'], JSON_THROW_ON_ERROR),
                'fingerprint' => $fingerprint,
                'observed_at' => now(),
                'updated_at' => now(),
            ];
            if ($previous) {
                DB::table('hr_employee_ownership')->where('id', $previous->id)->update($values);
            } else {
                DB::table('hr_employee_ownership')->insert(
                    $values + ['employee_id' => $employeeId, 'created_at' => now()],
                );
            }
            HrReassignmentCase::where('employee_id', $employeeId)
                ->whereIn('status', HrReassignmentCase::OPEN_STATUSES)
                ->update(['status' => 'superseded', 'updated_at' => now()]);
            if (HrOwnershipPolicy::needsCase($before, $state)) {
                $case = HrReassignmentCase::create([
                    'employee_id' => $employeeId,
                    'ownership_event_id' => $eventId,
                    'fingerprint' => $fingerprint,
                    'status' => HrOwnershipPolicy::status($state),
                    'proposed_user_id' => count($state['owner_ids']) === 1 ? $state['owner_ids'][0] : null,
                ]);
                AuditLogService::created($case, 'Opened HR work reassignment review');
                if ($case->proposed_user_id) {
                    NotificationService::send(
                        User::find($case->proposed_user_id),
                        'hr_reassignment',
                        'HR work awaiting acceptance',
                        'Review an employee reassignment in your HR queue.',
                        route('hr-intelligence.index'),
                    );
                }
            }

            return true;
        });
    }

    public static function currentState(Employee $employee, ?Collection $map = null): array
    {
        $map ??= HrResponsibilityService::recipientMap();

        return HrOwnershipPolicy::state(
            $employee->position_id === null ? null : (int) $employee->position_id,
            $employee->is_active && ! $employee->trashed(),
            $map->get($employee->position_id, collect())->pluck('id')->all(),
        );
    }

    public static function propose(User $actor, HrReassignmentCase $case, int $officerId, string $note): void
    {
        abort_unless(self::isManager($actor), 403);
        DB::transaction(function () use ($actor, $case, $officerId, $note) {
            $employee = Employee::withTrashed()->lockForUpdate()->findOrFail($case->employee_id);
            $case = HrReassignmentCase::lockForUpdate()->findOrFail($case->id);
            $state = self::guardCurrent($case, $employee);
            if (! in_array($officerId, $state['owner_ids'], true)) {
                throw ValidationException::withMessages([
                    'officer_id' => 'Choose an officer who currently manages this employee’s HR position. Correct the position assignment first if no officer is eligible.',
                ]);
            }
            $old = $case->getAttributes();
            $case->update([
                'proposed_user_id' => $officerId,
                'status' => 'awaiting_acceptance',
                'review_note' => $note,
                'reviewed_by' => $actor->id,
            ]);
            AuditLogService::updated($case, $old, 'Proposed HR work owner');
            if ((int) ($old['proposed_user_id'] ?? 0) !== $officerId) {
                NotificationService::send(
                    User::find($officerId),
                    'hr_reassignment',
                    'HR work awaiting acceptance',
                    'A manager has assigned an employee reassignment for your review.',
                    route('hr-intelligence.index'),
                );
            }
        });
    }

    public static function accept(User $actor, HrReassignmentCase $case, string $note): void
    {
        abort_unless($actor->is_active && $actor->isSubjectOfficer(), 403);
        DB::transaction(function () use ($actor, $case, $note) {
            $employee = Employee::withTrashed()->lockForUpdate()->findOrFail($case->employee_id);
            $case = HrReassignmentCase::lockForUpdate()->findOrFail($case->id);
            abort_unless((int) $case->proposed_user_id === (int) $actor->id, 403);
            // Recheck scope even for repeated submissions, so revocation takes effect.
            $state = self::currentState($employee);
            abort_unless($state['active'] && in_array((int) $actor->id, $state['owner_ids'], true), 403);
            if ($case->status === 'accepted') {
                return;
            }
            self::guardCurrent($case, $employee, $state);
            if ($case->status !== 'awaiting_acceptance') {
                throw ValidationException::withMessages([
                    'case' => 'A manager must select a responsible officer before acceptance.',
                ]);
            }
            $transfers = [];
            $queries = [
                'data_quality_issues' => [
                    DB::table('data_quality_issues')
                        ->where('employee_id', $employee->id)
                        ->where('status', '!=', 'verified'),
                    'assigned_to',
                ],
                'employee_increments' => [
                    DB::table('employee_increments')
                        ->where('employee_id', $employee->id)
                        ->where('is_active', true)
                        ->whereNull('granted_date')
                        ->where(
                            fn ($query) => $query
                                ->whereNull('workflow_status')
                                ->orWhereNotIn('workflow_status', ['granted', 'deferred', 'withheld']),
                        ),
                    'responsible_user_id',
                ],
                'retirement_projects' => [
                    DB::table('retirement_projects')
                        ->where('employee_id', $employee->id)
                        ->where('status', '!=', 'completed')
                        ->whereNull('completed_at'),
                    'responsible_user_id',
                ],
            ];
            foreach ($queries as $table => [$query, $column]) {
                $rows = $query->lockForUpdate()->get(['id', $column]);
                $changed = $rows->filter(fn ($row) => (int) $row->{$column} !== (int) $actor->id);
                $transfers[$table] = $changed
                    ->map(fn ($row) => ['id' => $row->id, 'from' => $row->{$column}, 'to' => $actor->id])
                    ->values()
                    ->all();
                if ($changed->isNotEmpty()) {
                    DB::table($table)
                        ->whereIn('id', $changed->pluck('id'))
                        ->update([$column => $actor->id, 'updated_at' => now()]);
                }
            }
            $old = $case->getAttributes();
            $case->update([
                'status' => 'accepted',
                'accepted_by' => $actor->id,
                'accepted_at' => now(),
                'review_note' => trim(($case->review_note ? $case->review_note."\n" : '').$note),
                'work_transfer' => $transfers,
            ]);
            // The case retains exact old/new task-owner IDs; the audit log records the decision.
            AuditLogService::updated(
                $case,
                array_diff_key($old, ['work_transfer' => true]),
                'Accepted and reassigned open HR work',
            );
        });
    }

    private static function guardCurrent(HrReassignmentCase $case, Employee $employee, ?array $state = null): array
    {
        $state ??= self::currentState($employee);
        if (
            ! in_array($case->status, HrReassignmentCase::OPEN_STATUSES, true) ||
            ! $state['active'] ||
            ! hash_equals($case->fingerprint, HrOwnershipPolicy::fingerprint($state))
        ) {
            throw ValidationException::withMessages([
                'case' => 'Ownership changed or this review is closed. Refresh HR intelligence and open the current case.',
            ]);
        }

        return $state;
    }

    private static function coverageAlerts(Collection $map): void
    {
        $managers = User::active()
            ->with(['userRoles', 'category'])
            ->get()
            ->filter(fn (User $user) => self::isManager($user));
        $assignments = HrResponsibility::effective()->with('user.userRoles')->get()->groupBy('position_id');
        foreach (Position::orderBy('id')->get() as $position) {
            $owners = $map->get($position->id, collect());
            $explicit = $assignments->get($position->id, collect());
            $kinds = [];
            if ($position->is_active && $owners->isEmpty()) {
                $kinds[] = 'unassigned';
            }
            if ($position->is_active && $owners->count() > 1) {
                $kinds[] = 'shared_ownership';
            }
            if (
                $explicit->contains(
                    fn ($assignment) => ! $assignment->user->is_active || ! $assignment->user->isSubjectOfficer(),
                )
            ) {
                $kinds[] = 'inactive_owner';
            }
            if (! $position->is_active && $explicit->isNotEmpty()) {
                $kinds[] = 'disabled_position';
            }
            DB::transaction(function () use ($position, $kinds, $managers) {
                Position::whereKey($position->id)->lockForUpdate()->first();
                foreach (['unassigned', 'shared_ownership', 'inactive_owner', 'disabled_position'] as $kind) {
                    $query = DB::table('hr_coverage_alerts')->where('position_id', $position->id)->where('kind', $kind);
                    $existing = $query->first();
                    if (! in_array($kind, $kinds, true)) {
                        if ($existing && $existing->is_open) {
                            $query->update(['is_open' => false, 'resolved_at' => now(), 'updated_at' => now()]);
                        }

                        continue;
                    }
                    if ($existing && $existing->is_open && $existing->notified_at) {
                        continue;
                    }
                    $values = [
                        'is_open' => true,
                        'opened_at' => now(),
                        'resolved_at' => null,
                        'notified_at' => $managers->isNotEmpty() ? now() : null,
                        'occurrence' => $existing ? $existing->occurrence + (int) ! $existing->is_open : 1,
                        'updated_at' => now(),
                    ];
                    if ($existing) {
                        $query->update($values);
                    } else {
                        DB::table('hr_coverage_alerts')->insert(
                            $values + ['position_id' => $position->id, 'kind' => $kind, 'created_at' => now()],
                        );
                    }
                    NotificationService::sendToMany(
                        $managers,
                        'hr_coverage_alert',
                        'HR responsibility coverage needs review',
                        $position->title.
                            ': '.
                            str_replace('_', ' ', $kind).
                            '. Review before changing assignments.',
                        route('hr-intelligence.index'),
                    );
                }
            });
        }
    }
}
