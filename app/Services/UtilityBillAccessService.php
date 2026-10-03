<?php

namespace App\Services;

use App\Models\UtilityBillResponsibilityAssignment;
use App\Models\User;

class UtilityBillAccessService
{
    public function currentAssignment(): ?UtilityBillResponsibilityAssignment
    {
        return UtilityBillResponsibilityAssignment::query()->with('subjectOfficer')->whereNull('ended_at')->latest('effective_from')->latest('id')->first();
    }

    public function canManage(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        $assignment = $this->currentAssignment();
        return $assignment && (int) $assignment->subject_officer_id === (int) $user->id;
    }

    public function canViewAnalytics(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdminGroup();
    }
}
