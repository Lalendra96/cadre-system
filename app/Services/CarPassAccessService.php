<?php

namespace App\Services;

use App\Models\CarPassRequest;
use App\Models\CarPassResponsibilityAssignment;
use App\Models\SystemSetting;
use App\Models\User;

class CarPassAccessService
{
    public function currentAssignment(): ?CarPassResponsibilityAssignment
    {
        return CarPassResponsibilityAssignment::query()
            ->with('subjectOfficer')
            ->whereNull('ended_at')
            ->whereDate('effective_from', '<=', today())
            ->where(function ($query) {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', today());
            })
            ->latest('effective_from')
            ->latest('id')
            ->first();
    }

    public function isAssignedSubjectOfficer(User $user): bool
    {
        $assignment = $this->currentAssignment();

        return $user->is_active
            && $user->isSubjectOfficer()
            && $assignment !== null
            && (int) $assignment->subject_officer_id === (int) $user->id;
    }

    public function approvalRole(): string
    {
        $role = (string) SystemSetting::get('car_pass_approval_role', User::ROLE_ADMIN_GROUP);

        return in_array($role, [
            User::ROLE_ADMIN_GROUP,
            User::ROLE_PLANNING_OFFICER,
            User::ROLE_SUPER_ADMIN,
        ], true) ? $role : User::ROLE_ADMIN_GROUP;
    }

    public function canApprove(User $user): bool
    {
        return $user->is_active && $user->hasRole($this->approvalRole());
    }

    public function canViewPass(User $user, CarPassRequest $pass): bool
    {
        if ($user->isSuperAdmin() || $user->isAdminGroup() || $user->isPlanningOfficer()) {
            return true;
        }

        if (! $user->isSubjectOfficer()) {
            return false;
        }

        return $this->isAssignedSubjectOfficer($user)
            || (int) $pass->prepared_by === (int) $user->id;
    }

    public function canView(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->isAdminGroup()
            || $user->isPlanningOfficer()
            || $user->isSubjectOfficer();
    }
}
