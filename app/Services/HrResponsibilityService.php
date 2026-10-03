<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\HrHandover;
use App\Models\HrResponsibility;
use App\Models\Position;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HrResponsibilityService
{
    public static function positionIds(User $user): Collection
    {
        if (! $user->is_active || ! $user->isSubjectOfficer()) {
            return collect();
        }

        // Once explicitly configured, even zero/expired assignments mean zero access.
        if ($user->hr_scope_configured) {
            return HrResponsibility::query()
                ->effective()
                ->where('user_id', $user->id)
                ->whereHas('position', fn ($query) => $query->where('is_active', true))
                ->pluck('position_id')
                ->unique()
                ->values();
        }

        return DB::table('position_subject_code')
            ->join('positions', 'positions.id', '=', 'position_subject_code.position_id')
            ->where('positions.is_active', true)
            ->whereIn('subject_code_id', $user->effectiveSubjectCodeIds())
            ->pluck('position_id')
            ->unique()
            ->values();
    }

    /** Build once per reminder run; access and notification ownership stay identical. */
    public static function recipientMap(): Collection
    {
        $map = collect();
        User::active()
            ->havingRole(User::ROLE_SUBJECT_OFFICER)
            ->with('userRoles')
            ->get()
            ->each(function (User $user) use ($map) {
                foreach (self::positionIds($user) as $positionId) {
                    $map->put($positionId, $map->get($positionId, collect())->push($user));
                }
            });

        return $map;
    }

    public static function assign(User $actor, array $data): HrResponsibility
    {
        self::authorizeAdmin($actor);

        return DB::transaction(function () use ($actor, $data) {
            self::lockPosition((int) $data['position_id']);
            $officer = self::lockOfficer((int) $data['user_id']);
            self::validateWindow($data);
            self::assertNoOverlap(
                $officer->id,
                (int) $data['position_id'],
                $data['starts_on'],
                $data['ends_on'] ?? null,
            );
            $assignment = HrResponsibility::create([
                'position_id' => $data['position_id'],
                'user_id' => $officer->id,
                'kind' => $data['kind'],
                'starts_on' => $data['starts_on'],
                'ends_on' => $data['ends_on'] ?? null,
                'assigned_by' => $actor->id,
                'notes' => $data['notes'] ?? null,
            ]);
            $officer->forceFill(['hr_scope_configured' => true])->save();
            AuditLogService::created($assignment, 'Assigned HR position responsibility');
            NotificationService::send(
                $officer,
                'hr_assignment',
                'HR responsibility assigned',
                'Review your position assignment and effective dates.',
                route('hr-responsibilities.index'),
            );

            return $assignment;
        });
    }

    public static function end(User $actor, HrResponsibility $assignment, string $reason): void
    {
        self::authorizeAdmin($actor);
        DB::transaction(function () use ($actor, $assignment, $reason) {
            self::lockPosition($assignment->position_id, false);
            $assignment = HrResponsibility::query()->lockForUpdate()->findOrFail($assignment->id);
            if ($assignment->ended_at !== null) {
                return;
            }
            if (HrHandover::where('hr_responsibility_id', $assignment->id)->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages([
                    'assignment' => 'Cancel the pending handover before ending this responsibility.',
                ]);
            }
            $old = $assignment->getAttributes();
            $assignment->update(['ended_at' => now(), 'ended_by' => $actor->id, 'end_reason' => $reason]);
            AuditLogService::updated($assignment, $old, 'Ended HR responsibility; history retained');
            NotificationService::send(
                $assignment->user,
                'hr_assignment_ended',
                'HR responsibility ended',
                'An HR position assignment has ended. Review your responsibility history.',
                route('hr-responsibilities.index'),
            );
        });
    }

    public static function requestHandover(User $actor, HrResponsibility $assignment, array $data): HrHandover
    {
        self::authorizeAdmin($actor);

        return DB::transaction(function () use ($actor, $assignment, $data) {
            self::lockPosition($assignment->position_id);
            $assignment = HrResponsibility::query()->lockForUpdate()->findOrFail($assignment->id);
            $recipient = self::lockOfficer((int) $data['to_user_id']);
            if (
                $recipient->id === $assignment->user_id ||
                $assignment->kind !== 'permanent' ||
                $assignment->ended_at ||
                $assignment->ends_on ||
                $assignment->starts_on->isFuture() ||
                $data['effective_on'] < today()->toDateString()
            ) {
                throw ValidationException::withMessages([
                    'handover' => 'Choose an ongoing permanent assignment, a different officer and a current or future effective date.',
                ]);
            }
            if (HrHandover::where('hr_responsibility_id', $assignment->id)->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages([
                    'handover' => 'This responsibility already has a pending handover.',
                ]);
            }
            $handover = HrHandover::create([
                'hr_responsibility_id' => $assignment->id,
                'to_user_id' => $recipient->id,
                'effective_on' => $data['effective_on'],
                'notes' => $data['notes'],
                'outstanding_actions' => $data['outstanding_actions'],
                'requested_by' => $actor->id,
                'status' => 'pending',
            ]);
            AuditLogService::created($handover, 'Requested HR responsibility handover');
            NotificationService::send(
                $recipient,
                'hr_handover_pending',
                'HR handover awaiting acceptance',
                'Review the handover notes and outstanding actions before accepting.',
                route('hr-responsibilities.index'),
            );

            return $handover;
        });
    }

    public static function accept(User $actor, HrHandover $handover): void
    {
        abort_unless($actor->is_active && $actor->isSubjectOfficer() && $actor->id === $handover->to_user_id, 403);
        DB::transaction(function () use ($actor, $handover) {
            self::lockPosition($handover->responsibility->position_id);
            $handover = HrHandover::query()->lockForUpdate()->findOrFail($handover->id);
            if ($handover->status === 'accepted') {
                return;
            }
            if ($handover->status !== 'pending' || $handover->effective_on->lt(today())) {
                throw ValidationException::withMessages([
                    'handover' => 'This handover is cancelled or overdue. Ask the administrator to create a new handover with a current effective date.',
                ]);
            }
            $source = HrResponsibility::query()->lockForUpdate()->findOrFail($handover->hr_responsibility_id);
            if ($source->ended_at || $source->ends_on) {
                throw ValidationException::withMessages([
                    'handover' => 'The source responsibility has changed. Create a new handover.',
                ]);
            }
            $recipient = self::lockOfficer($actor->id);
            $start = $handover->effective_on->toDateString();
            self::assertNoOverlap($recipient->id, $source->position_id, $start, null);
            $oldSource = $source->getAttributes();
            $source->update(
                $handover->effective_on->isToday()
                    ? [
                        'ended_at' => now(),
                        'ended_by' => $actor->id,
                        'end_reason' => 'Accepted handover #'.$handover->id,
                    ]
                    : ['ends_on' => $handover->effective_on->copy()->subDay()->toDateString()],
            );
            $new = HrResponsibility::create([
                'position_id' => $source->position_id,
                'user_id' => $recipient->id,
                'kind' => 'permanent',
                'starts_on' => $start,
                'assigned_by' => $handover->requested_by,
                'notes' => 'Accepted handover #'.$handover->id,
            ]);
            $recipient->forceFill(['hr_scope_configured' => true])->save();
            $oldHandover = $handover->getAttributes();
            $handover->update([
                'status' => 'accepted',
                'accepted_by' => $actor->id,
                'accepted_at' => now(),
                'new_responsibility_id' => $new->id,
            ]);
            AuditLogService::updated($source, $oldSource, 'Scheduled end through accepted HR handover');
            AuditLogService::created($new, 'Responsibility created through accepted HR handover');
            AuditLogService::updated($handover, $oldHandover, 'Recipient accepted HR handover');
            NotificationService::sendToMany(
                User::active()
                    ->whereIn('id', [$source->user_id, $recipient->id])
                    ->get(),
                'hr_handover_accepted',
                'HR handover accepted',
                'The responsibility changes on '.$start.'. Review the handover record.',
                route('hr-responsibilities.index'),
            );
        });
    }

    public static function cancel(User $actor, HrHandover $handover, string $reason): void
    {
        self::authorizeAdmin($actor);
        DB::transaction(function () use ($actor, $handover, $reason) {
            self::lockPosition($handover->responsibility->position_id, false);
            $handover = HrHandover::query()->lockForUpdate()->findOrFail($handover->id);
            if ($handover->status !== 'pending') {
                throw ValidationException::withMessages(['handover' => 'Only pending handovers can be cancelled.']);
            }
            $old = $handover->getAttributes();
            $handover->update([
                'status' => 'cancelled',
                'cancelled_by' => $actor->id,
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
            ]);
            AuditLogService::updated($handover, $old, 'Cancelled HR handover');
        });
    }

    private static function authorizeAdmin(User $actor): void
    {
        abort_unless($actor->is_active && $actor->isSuperAdmin(), 403);
    }

    private static function lockPosition(int $id, bool $requireActive = true): Position
    {
        $position = Position::query()->lockForUpdate()->findOrFail($id);
        if ($requireActive && ! $position->is_active) {
            throw ValidationException::withMessages(['position_id' => 'Choose an active position.']);
        }

        return $position;
    }

    private static function lockOfficer(int $id): User
    {
        $officer = User::query()->lockForUpdate()->findOrFail($id);
        if (! $officer->is_active || ! $officer->isSubjectOfficer()) {
            throw ValidationException::withMessages(['user_id' => 'Choose an active Subject Officer.']);
        }

        return $officer;
    }

    private static function validateWindow(array $data): void
    {
        validator($data, [
            'kind' => ['required', 'in:permanent,temporary'],
            'starts_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'ends_on' => [
                'nullable',
                'required_if:kind,temporary',
                'prohibited_if:kind,permanent',
                'date_format:Y-m-d',
                'after_or_equal:starts_on',
            ],
        ])->validate();
    }

    private static function assertNoOverlap(int $userId, int $positionId, string $start, ?string $end): void
    {
        $exists = HrResponsibility::where('user_id', $userId)
            ->where('position_id', $positionId)
            ->whereNull('ended_at')
            ->whereDate('starts_on', '<=', $end ?? '9999-12-31')
            ->where(fn ($query) => $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', $start))
            ->exists();
        if ($exists) {
            throw ValidationException::withMessages([
                'assignment' => 'This officer already has an overlapping assignment for the selected position.',
            ]);
        }
    }
}
