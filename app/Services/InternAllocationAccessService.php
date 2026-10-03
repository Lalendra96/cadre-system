<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InternBatch;
use App\Models\User;

final class InternAllocationAccessService
{
    public static function canViewModule(User $user): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->isSuperAdmin()
            || $user->isAdminGroup()
            || $user->isPlanningOfficer()
            || $user->isSubjectOfficer();
    }

    public static function canCreateBatch(User $user): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->isSuperAdmin()
            || $user->isSubjectOfficer();
    }

    public static function canManageResponsibility(User $user): bool
    {
        return $user->is_active && $user->isSuperAdmin();
    }

    /**
     * Operational edits are intentionally restricted to the Subject Officer
     * explicitly assigned to the batch. Elevated roles retain oversight but
     * do not silently alter the allocation record.
     */
    public static function ownsBatch(User $user, InternBatch $batch): bool
    {
        return $user->is_active
            && $user->isSubjectOfficer()
            && $batch->assigned_subject_officer_id === $user->id;
    }

    public static function canEditBatch(User $user, InternBatch $batch): bool
    {
        return self::ownsBatch($user, $batch) && $batch->is_active;
    }

    /**
     * RHO placement is a post-batch follow-up activity. Once the official
     * batch period has ended, the assigned Subject Officer may continue
     * recording authoritative placement outcomes even though allocation
     * editing has been automatically closed.
     */
    public static function canEditRhoPlacements(User $user, InternBatch $batch): bool
    {
        if (! self::ownsBatch($user, $batch) || $batch->end_date === null) {
            return false;
        }

        return now()->startOfDay()->greaterThan($batch->end_date->copy()->endOfDay());
    }

    public static function assertCanEditRhoPlacements(User $user, InternBatch $batch): void
    {
        abort_unless(
            self::canEditRhoPlacements($user, $batch),
            403,
            'RHO placement follow-up is available only to the Subject Officer assigned to the batch after its official end date.',
        );
    }

    public static function assertCanView(User $user): void
    {
        abort_unless(
            self::canViewModule($user),
            403,
            'You do not have access to Intern Medical Officer Allocation.',
        );
    }

    public static function assertCanCreate(User $user): void
    {
        abort_unless(
            self::canCreateBatch($user),
            403,
            'Only an authorised Subject Officer or Super Administrator can create an intern batch.',
        );
    }

    public static function assertCanEdit(User $user, InternBatch $batch): void
    {
        abort_unless(
            self::canEditBatch($user, $batch),
            403,
            'This batch is read-only for your account. Only the Subject Officer formally assigned to this batch can change allocations, intern details, dates or RHO placements.',
        );
    }
}
