<?php

namespace App\Models;

use App\Traits\HasDisableWorkflow;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Position extends Model
{
    use HasDisableWorkflow, HasFactory, SoftDeletes;

    protected $table = 'positions';

    protected $fillable = [
        'code', 'title', 'is_active', 'vacancy_threshold_pct',
        'disabled_by', 'disabled_at', 'disable_reason',
    ];

    protected $casts = [
        'is_active'            => 'boolean',
        'disabled_at'          => 'datetime',
        'vacancy_threshold_pct'=> 'integer',
    ];

    // ── Relations ──────────────────────────────────────────────────────────

    public function subjectCodes()
    {
        return $this->belongsToMany(SubjectCode::class, 'position_subject_code')
            ->withTimestamps();
    }

    public function approvedCarders()
    {
        return $this->hasMany(ApprovedCarder::class, 'position_id');
    }

    public function monthlyEntries()
    {
        return $this->hasMany(CarderMonthlyEntry::class, 'position_id');
    }

    public function employees()
    {
        return $this->hasMany(Employee::class, 'position_id');
    }

    public function grades()
    {
        return $this->hasMany(PositionGrade::class, 'position_id');
    }

    public function vacancyAvailabilityLetters()
    {
        return $this->hasMany(VacancyAvailabilityLetter::class, 'position_id');
    }

    /** The acting-allowance calculation rule when this position is acted IN. */
    public function actingAllowanceRule()
    {
        return $this->hasOne(PositionActingAllowanceRule::class, 'position_id');
    }

    public function groups()
    {
        return $this->belongsToMany(PositionGroup::class, 'position_group_position');
    }

    public function subcategories()
    {
        return $this->hasMany(PositionSubcategory::class, 'parent_position_id');
    }

    /** Units this position has been bound to as relevant/expected — see unit_position migration docblock. */
    public function boundUnits()
    {
        return $this->belongsToMany(Unit::class, 'unit_position')->withTimestamps();
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->whereRaw('title ILIKE ?', ["%{$term}%"])
              ->orWhereRaw('code ILIKE ?', ["%{$term}%"]);
        });
    }
}
