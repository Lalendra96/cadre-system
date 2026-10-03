<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HospitalPlanningAssessment extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_READY = 'review_ready';
    public const STATUS_REFERRED = 'referred';
    public const STATUS_CLOSED = 'closed';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_READY => 'Ready for review',
        self::STATUS_REFERRED => 'Referred for authorised decision',
        self::STATUS_CLOSED => 'Closed / superseded',
    ];

    public const AREAS = [
        'workforce' => 'Workforce & Cadre Planning',
        'service_capacity' => 'Service Capacity & Demand',
        'infrastructure' => 'Infrastructure & Space',
        'equipment' => 'Equipment & Technology',
        'finance' => 'Financial / Resource Planning',
        'utility' => 'Utility & Operational Continuity',
        'quality_safety' => 'Quality, Safety & Resilience',
        'other' => 'Other Hospital Planning',
    ];

    protected $guarded = [];

    protected $casts = [
        'support_only_acknowledged' => 'boolean',
        'evidence_verified' => 'boolean',
        'alternatives_considered' => 'boolean',
        'risks_considered' => 'boolean',
        'minimum_necessary_confirmed' => 'boolean',
        'is_active' => 'boolean',
        'reviewed_at' => 'datetime',
        'referred_at' => 'datetime',
        'disabled_at' => 'datetime',
    ];

    public function preparer()
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
