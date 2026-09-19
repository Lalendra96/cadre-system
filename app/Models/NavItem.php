<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NavItem extends Model
{
    protected $fillable = [
        'section',
        'label',
        'route_name',
        'route_params',
        'open_in_new_tab',
        'allowed_roles',
        'custom_checks',
        'sort_order',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'route_params' => 'array',
        'open_in_new_tab' => 'boolean',
        'allowed_roles' => 'array',
        'custom_checks' => 'array',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public static function customChecks(): array
    {
        return [
            'canManageCircularGroups' => fn (User $user) => $user->canManageCircularGroups(),
            'canVerifyEntries' => fn (User $user) => $user->canVerifyEntries(),
            'isExecutiveViewer' => fn (User $user) => $user->isExecutiveViewer(),
            'isHrRecordsManager' => fn (User $user) => $user->isHrRecordsManager(),
            'canViewEmployees' => fn (User $user) => (bool) $user->can_view_employees,
            'canManageInternAssignments' => fn (User $user) => $user->isSuperAdmin()
                || $user->isAdminGroup()
                || $user->isPlanningOfficer()
                || (bool) $user->can_manage_intern_assignments,
            'canViewLetters' => fn (User $user) => (bool) $user->can_view_letters,
            'hasAnyPositionBoundCode' => fn (User $user) => $user->hasAnyPositionBoundCode(),
            'hasAssignedHrPositions' => fn (User $user) => $user->hasAssignedHrPositions(),
            'canAccessHrIntelligence' => fn (User $user) => $user->canAccessHrIntelligence(),
        ];
    }

    public static function customCheckLabels(): array
    {
        return [
            'canManageCircularGroups' => 'Can manage circular groups (per-account flag)',
            'canVerifyEntries' => 'Can verify entries (per Admin → Settings verifier config)',
            'isExecutiveViewer' => 'Is Director / Deputy Director / Deputy Director General',
            'isHrRecordsManager' => 'Is Director / DDG / Deputy Director / Administrative Officer / Chief Clerk',
            'canViewEmployees' => 'Has "Can view Employee Profiles" enabled',
            'canManageInternAssignments' => 'Is Admin Group / Planning Officer / Super Admin, or has "Can manage intern assignments" enabled',
            'canViewLetters' => 'Has "Can access Letter Sharing" enabled',
            'hasAnyPositionBoundCode' => 'Has at least one position-bound subject code assigned',
            'hasAssignedHrPositions' => 'Has at least one HR position assigned',
            'canAccessHrIntelligence' => 'Can access HR intelligence and reassignment reviews',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isVisibleTo(User $user): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if (! \App\Services\FeatureToggleService::routeEnabled(
            (string) $this->route_name
        )) {
            return false;
        }

        if (
            ! empty($this->allowed_roles)
            && ! $user->hasAnyRole($this->allowed_roles)
        ) {
            return false;
        }

        $checks = static::customChecks();

        foreach ($this->custom_checks ?? [] as $checkName) {
            if (! isset($checks[$checkName])) {
                continue;
            }

            if (! $checks[$checkName]($user)) {
                return false;
            }
        }

        return true;
    }
}
