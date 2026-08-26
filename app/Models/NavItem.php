<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NavItem extends Model
{
    protected $fillable = [
        'section', 'label', 'route_name', 'route_params', 'open_in_new_tab',
        'allowed_roles', 'custom_checks', 'sort_order', 'is_active', 'created_by',
    ];

    protected $casts = [
        'route_params'     => 'array',
        'open_in_new_tab'  => 'boolean',
        'allowed_roles'    => 'array',
        'custom_checks'    => 'array',
        'sort_order'       => 'integer',
        'is_active'        => 'boolean',
    ];

    /**
     * Whitelisted business-logic checks a nav item may require, ALL of
     * which must pass (AND logic) alongside any allowed_roles restriction.
     * NEVER call an arbitrary method name pulled from the database — only
     * keys present in this fixed array are ever invoked. Values are
     * closures taking the authenticated User and returning bool.
     *
     * This is the exact set of custom conditions the nav used before this
     * feature existed — nothing here is new business logic, it's a
     * pointer to logic that already lived on User.
     */
    public static function customChecks(): array
    {
        return [
            'canManageCircularGroups' => fn (User $u) => $u->canManageCircularGroups(),
            'canVerifyEntries'        => fn (User $u) => $u->canVerifyEntries(),
            'isExecutiveViewer'       => fn (User $u) => $u->isExecutiveViewer(),
            'isHrRecordsManager'      => fn (User $u) => $u->isHrRecordsManager(),
            'canViewEmployees'        => fn (User $u) => (bool) $u->can_view_employees,
            'canViewLetters'          => fn (User $u) => (bool) $u->can_view_letters,
            'hasAnyPositionBoundCode' => fn (User $u) => $u->hasAnyPositionBoundCode(),
        ];
    }

    /** Human-readable labels for the whitelist, used in the Super Admin config UI. */
    public static function customCheckLabels(): array
    {
        return [
            'canManageCircularGroups' => 'Can manage circular groups (per-account flag)',
            'canVerifyEntries'        => 'Can verify entries (per Admin → Settings verifier config)',
            'isExecutiveViewer'       => 'Is Director / Deputy Director / Deputy Director General',
            'isHrRecordsManager'      => 'Is Director / DDG / Deputy Director / Administrative Officer / Chief Clerk',
            'canViewEmployees'        => 'Has "Can view Employee Profiles" enabled',
            'canViewLetters'          => 'Has "Can access Letter Sharing" enabled',
            'hasAnyPositionBoundCode' => 'Has at least one position-bound subject code assigned',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Whether this item should render for the given user — the single
     * source of truth every consumer (the nav itself, the Super Admin
     * preview screen) must use, so they can never disagree.
     */
    public function isVisibleTo(User $user): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if (! empty($this->allowed_roles) && ! $user->hasAnyRole($this->allowed_roles)) {
            return false;
        }

        $checks = static::customChecks();
        foreach ($this->custom_checks ?? [] as $checkName) {
            if (! isset($checks[$checkName])) {
                continue; // unknown/stale check name — fail open on THIS check, not the whole item, matching how a removed capability shouldn't silently hide an otherwise-valid item
            }
            if (! $checks[$checkName]($user)) {
                return false;
            }
        }

        return true;
    }
}
