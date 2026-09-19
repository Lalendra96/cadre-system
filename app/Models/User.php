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

    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_ADMIN_GROUP = 'admin_group';
    public const ROLE_PLANNING_OFFICER = 'planning_officer';
    public const ROLE_SUBJECT_OFFICER = 'subject_officer';

    public const ROLES = [
        self::ROLE_SUPER_ADMIN,
        self::ROLE_ADMIN_GROUP,
        self::ROLE_PLANNING_OFFICER,
        self::ROLE_SUBJECT_OFFICER,
    ];

    public const ROLE_LABELS = [
        self::ROLE_SUPER_ADMIN => 'Super Admin',
        self::ROLE_ADMIN_GROUP => 'Admin Group',
        self::ROLE_PLANNING_OFFICER => 'Planning Officer',
        self::ROLE_SUBJECT_OFFICER => 'Subject Officer',
    ];

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'category_id',
        'is_active',
        'disabled_by',
        'disabled_at',
        'disable_reason',
        'can_view_employees',
        'can_view_letters',
        'can_manage_circular_groups',
        'can_manage_intern_assignments',
        'force_password_change',
        'password_reset_requested_at',
        'remember_token',
        'current_session_token',
        'mfa_secret',
        'mfa_enabled',
        'mfa_verified_at',
        'employee_id',
        'locale',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'hr_scope_configured' => 'boolean',
        'disabled_at' => 'datetime',
        'can_view_employees' => 'boolean',
        'can_view_letters' => 'boolean',
        'can_manage_circular_groups' => 'boolean',
        'can_manage_intern_assignments' => 'boolean',
        'force_password_change' => 'boolean',
        'password_reset_requested_at' => 'datetime',
        'password' => 'hashed',
        'mfa_secret' => 'encrypted',
        'mfa_enabled' => 'boolean',
        'mfa_verified_at' => 'datetime',
    ];

    public function userRoles()
    {
        return $this->hasMany(UserRole::class, 'user_id');
    }

    public function savedEmployeeViews()
    {
        return $this->hasMany(SavedEmployeeView::class);
    }

    public function roles()
    {
        return $this->hasMany(UserRole::class, 'user_id');
    }

    public function category()
    {
        return $this->belongsTo(UserCategory::class, 'category_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function subjectCodes()
    {
        return $this->belongsToMany(SubjectCode::class, 'subject_code_user')
            ->withPivot(['assigned_by', 'assigned_at'])
            ->withTimestamps();
    }

    public function hrPositions()
    {
        return $this->belongsToMany(Position::class, 'hr_position_user')
            ->withPivot(['assigned_by', 'assigned_at'])
            ->withTimestamps();
    }

    public function actingSubjectOfficerAssignments()
    {
        return $this->hasMany(ActingSubjectOfficer::class, 'user_id');
    }

    public function actingSubjectOfficerAppointmentsMade()
    {
        return $this->hasMany(ActingSubjectOfficer::class, 'appointed_by');
    }

    public function eSignature()
    {
        return $this->hasOne(ESignature::class, 'user_id')->where('is_active', true);
    }

    public function getRolesAttribute(): array
    {
        if ($this->relationLoaded('userRoles')) {
            return $this->userRoles->pluck('role')->all();
        }

        return $this->userRoles()->pluck('role')->all();
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

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

    public function isDirector(): bool
    {
        return $this->isAdminGroup() && $this->category?->name === 'Director';
    }

    public function isExecutiveViewer(): bool
    {
        return $this->isAdminGroup() &&
            in_array($this->category?->name, ['Director', 'Deputy Director', 'Deputy Director General'], true);
    }

    public function isHrRecordsManager(): bool
    {
        return $this->isAdminGroup() &&
            in_array(
                $this->category?->name,
                [
                    'Director',
                    'Deputy Director General',
                    'Deputy Director',
                    'Administrative Officer / Hospital Secretary',
                    'Chief Clerk',
                ],
                true,
            );
    }

    public function canManageEmployeeHrRecord(?\App\Models\Employee $employee = null): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->isSuperAdmin() || $this->isPlanningOfficer() || $this->isHrRecordsManager()) {
            return true;
        }

        if ($this->isSubjectOfficer()) {
            return $employee !== null && \App\Services\WorkforceScopeService::isEmployeeAllocatedTo($this, $employee);
        }

        return false;
    }

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
            'category' => $this->isAdminGroup() &&
                in_array(
                    (int) $this->category_id,
                    array_filter(
                        array_map(
                            'intval',
                            (array) \App\Models\SystemSetting::getJson('entry_verifier_category_ids', []),
                        ),
                    ),
                    true,
                ),
            default => false,
        };
    }

    public function canManageApprovedCarder(): bool
    {
        if ($this->isSuperAdmin() || $this->isPlanningOfficer()) {
            return true;
        }

        if (!$this->isAdminGroup()) {
            return false;
        }

        return in_array(
            $this->category?->name,
            ['Director', 'Deputy Director General', 'Deputy Director', 'Medical Officer Planning'],
            true,
        );
    }

    public function canAccessHrIntelligence(): bool
    {
        return $this->is_active &&
            ($this->isSubjectOfficer() ||
                $this->isSuperAdmin() ||
                $this->isPlanningOfficer() ||
                $this->isHrRecordsManager());
    }

    public function isPlanningOfficer(): bool
    {
        return $this->hasRole(self::ROLE_PLANNING_OFFICER);
    }

    public function isSubjectOfficer(): bool
    {
        return $this->hasRole(self::ROLE_SUBJECT_OFFICER);
    }

    public function getRoleLabelAttribute(): string
    {
        return implode(
            ', ',
            array_map(fn ($r) => self::ROLE_LABELS[$r] ?? ucwords(str_replace('_', ' ', $r)), $this->roles),
        );
    }

    public function assignedPositionIds()
    {
        $codeIds = $this->subjectCodes()->pluck('subject_codes.id');
        if ($codeIds->isEmpty()) {
            return collect();
        }
        return \Illuminate\Support\Facades\DB::table('position_subject_code')
            ->whereIn('subject_code_id', $codeIds)
            ->pluck('position_id')
            ->filter()
            ->unique()
            ->values();
    }

    public function effectiveHrPositionIds(): \Illuminate\Support\Collection
    {
        return \App\Services\HrResponsibilityService::positionIds($this);
    }

    public function effectiveHrSubjectCodeIds(): \Illuminate\Support\Collection
    {
        $positionIds = $this->effectiveHrPositionIds();
        if ($positionIds->isEmpty()) {
            return collect();
        }
        return \Illuminate\Support\Facades\DB::table('position_subject_code')
            ->whereIn('position_id', $positionIds)
            ->pluck('subject_code_id')
            ->filter()
            ->unique()
            ->values();
    }

    public function hasAssignedHrPositions(): bool
    {
        return $this->effectiveHrPositionIds()->isNotEmpty();
    }

    public function managesHrPosition(?int $positionId): bool
    {
        return $positionId !== null && $this->effectiveHrPositionIds()->contains($positionId);
    }

    public function hasAnyPositionBoundCode(): bool
    {
        $codeIds = $this->subjectCodes()->pluck('subject_codes.id');
        if ($codeIds->isEmpty()) {
            return false;
        }
        return \Illuminate\Support\Facades\DB::table('position_subject_code')
            ->whereIn('subject_code_id', $codeIds)
            ->exists();
    }

    public function hasSubjectCode(int $subjectCodeId): bool
    {
        return $this->subjectCodes()->where('subject_codes.id', $subjectCodeId)->exists();
    }

    public function effectiveSubjectCodeIds(): \Illuminate\Support\Collection
    {
        $permanent = $this->subjectCodes()->pluck('subject_codes.id');

        $acting = $this->actingSubjectOfficerAssignments()->currentlyEffective()->pluck('subject_code_id');

        return $permanent->merge($acting)->unique()->values();
    }

    public function hasEffectiveSubjectCode(int $subjectCodeId): bool
    {
        return $this->effectiveSubjectCodeIds()->contains($subjectCodeId);
    }

    public function isAdministrativeOfficer(): bool
    {
        return $this->isAdminGroup() && $this->category?->name === 'Administrative Officer / Hospital Secretary';
    }

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

    public function isLetterRecipientCategory(): bool
    {
        if (!$this->isAdminGroup()) {
            return false;
        }

        return (bool) $this->category?->can_receive_letters;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeHavingRole($query, string $role)
    {
        return $query->whereHas('userRoles', fn ($q) => $q->where('role', $role));
    }
}
