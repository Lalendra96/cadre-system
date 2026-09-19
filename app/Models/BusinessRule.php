<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessRule extends Model
{
    public const STATUSES = [
        'draft' => 'Draft / Not Yet Approved',
        'institutional' => 'Institutional Operational Rule',
        'source_verified' => 'Source-Verified by Institution',
        'advisory' => 'Advisory / Planning Assumption',
    ];

    public const AUTHORITY_TYPES = [
        'act' => 'Act / Statute',
        'regulation' => 'Regulation / Gazette',
        'establishments_code' => 'Establishments Code',
        'public_administration_circular' => 'Public Administration Circular',
        'ministry_circular' => 'Ministry Circular / Instruction',
        'service_minute' => 'Service Minute',
        'psc_decision' => 'Public Service Commission Decision',
        'institutional' => 'Institutional Administrative Decision',
        'planning_assumption' => 'Planning Assumption',
        'technical_default' => 'Technical Default / Placeholder',
        'other' => 'Other',
    ];

    protected $fillable = [
        'code',
        'name',
        'system_behavior',
        'authority_type',
        'authority_reference',
        'authority_title',
        'authority_url',
        'effective_date',
        'review_due_date',
        'approved_by_name',
        'approved_by_designation',
        'approval_reference',
        'status',
        'notes',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'review_due_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function updater()
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }

    public function versions()
    {
        return $this->hasMany(
            BusinessRuleVersion::class
        )->orderByDesc('version_no');
    }

    public function decisions()
    {
        return $this->hasMany(
            AdministrativeDecision::class
        );
    }
}
