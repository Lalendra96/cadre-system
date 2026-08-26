<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use \App\Traits\HasDisableWorkflow;
    use HasFactory, Notifiable;

    /** Valid role values stored in the user_roles pivot table. */
    public const ROLE_SUPER_ADMIN      = 'super_admin';
    public const ROLE_ADMIN_GROUP      = 'admin_group';
    public const ROLE_PLANNING_OFFICER = 'planning_officer';
    public const ROLE_SUBJECT_OFFICER  = 'subject_officer';

    public const ROLES = [
        self::ROLE_SUPER_ADMIN,
        self::ROLE_ADMIN_GROUP,
        self::ROLE_PLANNING_OFFICER,
        self::ROLE_SUBJECT_OFFICER,
    ];

    public const ROLE_LABELS = [
        self::ROLE_SUPER_ADMIN      => 'Super Admin',
        self::ROLE_ADMIN_GROUP      => 'Admin Group',
        self::ROLE_PLANNING_OFFICER => 'Planning Officer',
        self::ROLE_SUBJECT_OFFICER  => 'Subject Officer',
    ];

    protected $table = 'users';

    protected $fillable = [
        'name', 'email', 'password',
        'category_id', 'is_active',
        'disabled_by', 'disabled_at', 'disable_reason',
        'can_view_employees', 'can_view_letters', 'can_manage_circular_groups',
        'force_password_change',
        'password_reset_requested_at',
        'remember_token',
        'current_session_token',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at'           => 'datetime',
        'is_active'                   => 'boolean',
        'disabled_at'                 => 'datetime',
        'can_view_employees'          => 'boolean',
        'can_view_letters'            => 'boolean',
        'can_manage_circular_groups'  => 'boolean',
        'force_password_change'       => 'boolean',
        'password_reset_requested_at' => 'datetime',
        'password'                    => 'hashed',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    /** All role rows for this user via the user_roles pivot table. */
    public function userRoles()
    {
        return $this->hasMany(UserRole::class, 'user_id');
    }

    /**
     * Alias for userRoles() — allows ->with('roles') in eager-loads
     * and keeps the API consistent with common Laravel conventions.
     */
    public function roles()
    {
        return $this->hasMany(UserRole::class, 'user_id');
    }

    public function category()
    {
        return $this->belongsTo(UserCategory::class, 'category_id');
    }

    public function subjectCodes()
    {
        return $this->belongsToMany(SubjectCode::class, 'subject_code_user')
            ->withPivot(['assigned_by', 'assigned_at'])
            ->withTimestamps();
    }

    /** Acting Subject Officer grants held BY this user (Super-Admin-appointed). */
    public function actingSubjectOfficerAssignments()
    {
        return $this->hasMany(ActingSubjectOfficer::class, 'user_id');
    }

    /** Acting Subject Officer grants this user (as Super Admin) has appointed to others. */
    public function actingSubjectOfficerAppointmentsMade()
    {
        return $this->hasMany(ActingSubjectOfficer::class, 'appointed_by');
    }

    /** The user's single active registered e-signature, if any. */
    public function eSignature()
    {
        return $this->hasOne(ESignature::class, 'user_id')->where('is_active', true);
    }

    // ── Role helpers ─────────────────────────────────────────────────────────

    /** Returns the user's roles as a plain array of strings, e.g. ['admin_group','planning_officer']. */
    public function getRolesAttribute(): array
    {
        // Avoid N+1 — if the relation is already loaded, use it.
        if ($this->relationLoaded('userRoles')) {
            return $this->userRoles->pluck('role')->all();
        }

        return $this->userRoles()->pluck('role')->all();
    }

    /** True if the user holds ALL of the given roles. */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    /** True if the user holds AT LEAST ONE of the given roles. */
    public function hasAnyRole(array $roles): bool
    {
        return (bool) array_intersect($roles, $this->roles);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(self::ROLE_SUPER_ADMIN);
    }

    public function isAdminGroup(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN_GROUP);
    }

    /**
     * True if this user is an Admin Group member whose User Category is "Director".
     *
     * Used to gate Approved Carder write access — the Director is the only
     * Admin Group sub-role authorised to set and amend MoH-approved figures.
     *
     * Super Admin bypasses this check separately in the controller.
     */
    public function isDirector(): bool
    {
        return $this->isAdminGroup()
            && $this->category?->name === 'Director';
    }

    /**
     * True for Director, Deputy Director, or Deputy Director General —
     * the three Admin Group categories with access to the executive-level
     * Unit-wise Breakdown summary (read-only, narrative-first view distinct
     * from the operational report available to the broader Admin Group /
     * Planning Officer / Super Admin via UnitBreakdownController).
     *
     * Category names matched exactly as seeded in UserCategorySeeder —
     * NOT the parenthetical "(I–N)" display text used in the README, which
     * is documentation only and never the literal stored value.
     */
    public function isExecutiveViewer(): bool
    {
        return $this->isAdminGroup()
            && in_array($this->category?->name, ['Director', 'Deputy Director', 'Deputy Director General'], true);
    }

    /**
     * True for the specific Admin Group categories authorised to manage
     * the sensitive HR record types: Transfer Board/PSC transfers,
     * Interdictions, Acting Appointments, LWOP/leave records, and
     * Confirmation-in-service. Deliberately a NARROWER set than
     * isAdminGroup() as a whole — Chief Accountant and Medical Officer
     * Planning, for example, are Admin Group but not in this list.
     */
    public function isHrRecordsManager(): bool
    {
        return $this->isAdminGroup() && in_array($this->category?->name, [
            'Director',
            'Deputy Director General',
            'Deputy Director',
            'Administrative Officer / Hospital Secretary',
            'Chief Clerk',
        ], true);
    }

    /**
     * Single source of truth for who may create/edit an HR record
     * (Transfer, Interdiction, Acting Appointment, Leave/LWOP record,
     * Confirmation-in-service) — used identically by every controller
     * managing one of those five record types, so the rule can never
     * drift between them.
     *
     * Allowed:
     *   - Super Admin / Planning Officer (existing hospital-wide access)
     *   - isHrRecordsManager() — Director/DDG/Deputy Director/AO/Chief Clerk
     *   - Subject Officer, but ONLY for an employee under their own
     *     effective (permanent + acting) subject code
     *
     * $employee may be null for record types that can reference someone
     * not yet in the system (e.g. an incoming transfer for a person with
     * no Employee profile here yet) — in that case there is no subject
     * code to scope against, so a Subject Officer is allowed through;
     * there is nothing more specific to check.
     */
    public function canManageEmployeeHrRecord(?\App\Models\Employee $employee = null): bool
    {
        if ($this->isSuperAdmin() || $this->isPlanningOfficer() || $this->isHrRecordsManager()) {
            return true;
        }

        if ($this->isSubjectOfficer()) {
            return $employee === null || $this->hasEffectiveSubjectCode($employee->subject_code_id);
        }

        return false;
    }

    /**
     * True if this user is currently permitted to verify carder monthly
     * entries, under whichever verifier mode is configured in
     * Admin → Settings (entry_verifier_role: super_admin / planning_officer
     * / category).
     *
     * This is the single source of truth for that decision — mirrors
     * EntryAmendmentController::authorizeVerifier()/authorizeByCategory()
     * exactly, minus their abort() messages, so the two can never
     * disagree. Used to decide whether to SHOW the "Verify Entries" nav
     * link; the controller's own checks remain the actual enforcement —
     * this method being wrong would at worst show or hide a link
     * incorrectly, never grant access the controller wouldn't also grant.
     */
    /**
     * True if this user may create Position Groups and dispatch (send)
     * circulars to them. Mirrors EnsureFeatureAccess middleware's
     * bypass logic exactly — elevated roles (Super Admin, Admin Group,
     * Planning Officer) always pass; a Subject Officer needs the
     * per-account can_manage_circular_groups flag, set by Super Admin.
     *
     * This method exists for checks OUTSIDE route middleware (nav
     * visibility, controller-level re-checks) — the middleware itself
     * (feature:circular-groups) remains the actual route-level enforcement.
     */
    public function canManageCircularGroups(): bool
    {
        if ($this->isSuperAdmin() || $this->isAdminGroup() || $this->isPlanningOfficer()) {
            return true;
        }

        return $this->isSubjectOfficer() && (bool) $this->can_manage_circular_groups;
    }

    public function canVerifyEntries(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $configuredRole = \App\Models\SystemSetting::get('entry_verifier_role', 'planning_officer');

        return match ($configuredRole) {
            'planning_officer' => $this->isPlanningOfficer(),
            'category' => $this->isAdminGroup() && in_array(
                (int) $this->category_id,
                array_filter(array_map('intval', (array) \App\Models\SystemSetting::getJson('entry_verifier_category_ids', []))),
                true
            ),
            default => false, // includes 'super_admin' mode — already handled above
        };
    }

    /**
     * Approved Carder write access gate.
     *
     * The following roles/categories may create, edit, and disable
     * approved carder records:
     *
     *   • Super Admin             — unrestricted system oversight
     *   • Planning Officer        — responsible for workforce planning
     *   • Admin Group categories that directly govern staffing:
     *       – Director
     *       – Deputy Director General
     *       – Deputy Director (I–N)
     *       – Medical Officer Planning
     *
     * The Admin Group "can_receive_letters" flag is intentionally NOT
     * used here — this is a governance check, not a communication check.
     *
     * Used in ApprovedCarderController::authorizeWrite() AND in
     * StoreApprovedCarderRequest::authorize() so both layers agree.
     */
    public function canManageApprovedCarder(): bool
    {
        if ($this->isSuperAdmin() || $this->isPlanningOfficer()) {
            return true;
        }

        if (! $this->isAdminGroup()) {
            return false;
        }

        return in_array($this->category?->name, [
            'Director',
            'Deputy Director General',
            'Deputy Director',
            'Medical Officer Planning',
        ], true);
    }

    public function isPlanningOfficer(): bool
    {
        return $this->hasRole(self::ROLE_PLANNING_OFFICER);
    }

    public function isSubjectOfficer(): bool
    {
        return $this->hasRole(self::ROLE_SUBJECT_OFFICER);
    }

    /** Human-readable comma-separated role list for display. */
    public function getRoleLabelAttribute(): string
    {
        return implode(', ', array_map(
            fn ($r) => self::ROLE_LABELS[$r] ?? ucwords(str_replace('_', ' ', $r)),
            $this->roles
        ));
    }

    // ── Convenience methods ───────────────────────────────────────────────────

    public function assignedPositionIds()
    {
        $codeIds = $this->subjectCodes()->pluck('subject_codes.id');
        if ($codeIds->isEmpty()) return collect();
        return \Illuminate\Support\Facades\DB::table('position_subject_code')
            ->whereIn('subject_code_id', $codeIds)
            ->pluck('position_id')->filter()->unique()->values();
    }

    /**
     * True if at least one of the user's assigned subject codes is linked to
     * at least one position via the pivot. When false, Monthly Entries and
     * Employee Profiles have nothing position-related to show and are hidden.
     */
    public function hasAnyPositionBoundCode(): bool
    {
        $codeIds = $this->subjectCodes()->pluck('subject_codes.id');
        if ($codeIds->isEmpty()) return false;
        return \Illuminate\Support\Facades\DB::table('position_subject_code')
            ->whereIn('subject_code_id', $codeIds)
            ->exists();
    }

    public function hasSubjectCode(int $subjectCodeId): bool
    {
        return $this->subjectCodes()->where('subject_codes.id', $subjectCodeId)->exists();
    }

    /**
     * Subject codes this user may currently act for — permanent assignments
     * PLUS any Super-Admin-granted acting assignment whose date window
     * covers today. This is the canonical scope check for anything where a
     * temporary acting Subject Officer should have the same access as a
     * permanent one (Employee Profiles, Service Letters).
     *
     * Deliberately NOT merged into hasSubjectCode()/subjectCodes() itself —
     * those remain "permanent assignment only" for callers (e.g. letter
     * recipient logic) that specifically mean permanent assignment.
     */
    public function effectiveSubjectCodeIds(): \Illuminate\Support\Collection
    {
        $permanent = $this->subjectCodes()->pluck('subject_codes.id');

        $acting = $this->actingSubjectOfficerAssignments()
            ->currentlyEffective()
            ->pluck('subject_code_id');

        return $permanent->merge($acting)->unique()->values();
    }

    public function hasEffectiveSubjectCode(int $subjectCodeId): bool
    {
        return $this->effectiveSubjectCodeIds()->contains($subjectCodeId);
    }

    /**
     * True if this Admin Group user's category is the Administrative
     * Officer / Hospital Secretary category — the required approver for
     * Service Letters drafted by Subject Officers.
     */
    public function isAdministrativeOfficer(): bool
    {
        return $this->isAdminGroup()
            && $this->category?->name === 'Administrative Officer / Hospital Secretary';
    }


    /** Called on login — stores the new session ID to prevent concurrent sessions. */
    public function updateSessionToken(string $sessionId): void
    {
        $this->update(['current_session_token' => $sessionId]);
    }

    public function isCurrentSession(string $sessionId): bool
    {
        return $this->current_session_token === $sessionId;
    }

    public function lettersCreated()
    {
        return $this->hasMany(Letter::class, 'created_by');
    }

    public function letterRecipients()
    {
        return $this->hasMany(LetterRecipient::class, 'user_id');
    }

    /**
     * True if this Admin Group user's category is configured to receive
     * letters in the Letter Sharing workflow. The flag is set per category
     * by Super Admin via the User Categories screen — no code deploy needed
     * to add or remove a category from the eligible pool.
     */
    public function isLetterRecipientCategory(): bool
    {
        if (! $this->isAdminGroup()) {
            return false;
        }

        return (bool) ($this->category?->can_receive_letters);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeHavingRole($query, string $role)
    {
        return $query->whereHas('userRoles', fn ($q) => $q->where('role', $role));
    }
}
