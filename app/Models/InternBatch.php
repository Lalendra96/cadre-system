<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class InternBatch extends Model
{
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'assigned_subject_officer_id',
        'responsibility_assigned_by',
        'responsibility_assigned_at',
        'is_active',
        'closed_at',
        'closed_by',
        'close_reason',
        'closed_automatically',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'responsibility_assigned_at' => 'datetime',
        'is_active' => 'boolean',
        'closed_at' => 'datetime',
        'closed_automatically' => 'boolean',
    ];

    public function allocations()
    {
        return $this->hasMany(InternUnitAllocation::class);
    }

    public function interns()
    {
        return $this->hasMany(Intern::class)->where('is_active', true);
    }

    public function allInterns()
    {
        return $this->hasMany(Intern::class);
    }

    public function assignments()
    {
        return $this->hasMany(InternAssignment::class);
    }

    public function rhoPlacements()
    {
        return $this->hasMany(InternRhoPlacement::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedSubjectOfficer()
    {
        return $this->belongsTo(User::class, 'assigned_subject_officer_id');
    }

    public function responsibilityAssigner()
    {
        return $this->belongsTo(User::class, 'responsibility_assigned_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getOperationalStatusAttribute(): string
    {
        if (! $this->is_active) {
            return 'closed';
        }

        $today = Carbon::today();

        if ($this->start_date && $today->lt($this->start_date)) {
            return 'upcoming';
        }

        if ($this->end_date && $today->gt($this->end_date)) {
            return 'ended';
        }

        if ($this->start_date && $this->end_date && $today->betweenIncluded($this->start_date, $this->end_date)) {
            return 'ongoing';
        }

        return 'open';
    }
}
