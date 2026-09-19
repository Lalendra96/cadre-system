<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentReport extends Model
{
    public const CATEGORIES = [
        'data_error' => 'Data / Record Error',
        'calculation_error' => 'Calculation / Forecast Error',
        'workflow_error' => 'Workflow / Configuration Error',
        'report_export_error' => 'Report / Export Error',
        'integration_error' => 'Integration / Interface Error',
        'security_privacy' => 'Security / Privacy Concern',
        'availability_performance' => 'Availability / Performance',
        'other' => 'Other',
    ];

    public const SEVERITIES = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'critical' => 'Critical',
    ];

    public const STATUSES = [
        'open' => 'Open / Awaiting Triage',
        'triaged' => 'Triaged',
        'under_investigation' => 'Under Investigation',
        'corrective_action' => 'Corrective Action in Progress',
        'resolved' => 'Resolved — Pending Closure',
        'closed' => 'Closed',
        'reopened' => 'Reopened',
    ];

    protected $fillable = [
        'reference_no',
        'title',
        'category',
        'severity',
        'status',
        'description',
        'impact_summary',
        'immediate_action',
        'root_cause',
        'corrective_action',
        'preventive_action',
        'detected_at',
        'affected_records_count',
        'affected_employee_id',
        'related_model_type',
        'related_model_id',
        'related_reference',
        'reported_by',
        'reported_at',
        'assigned_to',
        'target_resolution_date',
        'resolved_by',
        'resolved_at',
        'closed_by',
        'closed_at',
        'closure_notes',
        'is_active',
    ];

    protected $casts = [
        'detected_at' => 'datetime',
        'reported_at' => 'datetime',
        'target_resolution_date' => 'date',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function reporter()
    {
        return $this->belongsTo(
            User::class,
            'reported_by'
        );
    }

    public function assignee()
    {
        return $this->belongsTo(
            User::class,
            'assigned_to'
        );
    }

    public function resolvedBy()
    {
        return $this->belongsTo(
            User::class,
            'resolved_by'
        );
    }

    public function closedBy()
    {
        return $this->belongsTo(
            User::class,
            'closed_by'
        );
    }

    public function affectedEmployee()
    {
        return $this->belongsTo(
            Employee::class,
            'affected_employee_id'
        );
    }

    public function events()
    {
        return $this->hasMany(
            IncidentEvent::class
        )->orderBy('id');
    }

    public function scopeActive($query)
    {
        return $query->where(
            'is_active',
            true
        );
    }

    public function scopeOpen($query)
    {
        return $query->whereNotIn(
            'status',
            [
                'closed',
            ]
        );
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category]
            ?? ucwords(
                str_replace(
                    '_',
                    ' ',
                    $this->category
                )
            );
    }

    public function getSeverityLabelAttribute(): string
    {
        return self::SEVERITIES[$this->severity]
            ?? ucfirst($this->severity);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status]
            ?? ucwords(
                str_replace(
                    '_',
                    ' ',
                    $this->status
                )
            );
    }
}
