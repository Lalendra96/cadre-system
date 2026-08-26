<?php

namespace App\Models;

use App\Traits\HasDisableWorkflow;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubjectCode extends Model
{
    use HasDisableWorkflow, HasFactory, SoftDeletes;

    protected $table = 'subject_codes';

    protected $fillable = [
        'code', 'name', 'description', 'is_active',
        'disabled_by', 'disabled_at', 'disable_reason',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'disabled_at' => 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────────────────────

    public function positions()
    {
        return $this->belongsToMany(Position::class, 'position_subject_code')
            ->withTimestamps();
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'subject_code_user')
            ->withTimestamps();
    }

    public function monthlyEntries()
    {
        return $this->hasMany(CarderMonthlyEntry::class, 'subject_code_id');
    }

    public function employees()
    {
        return $this->hasMany(Employee::class, 'subject_code_id');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->whereRaw('code ILIKE ?', ["%{$term}%"])
              ->orWhereRaw('name ILIKE ?', ["%{$term}%"]);
        });
    }
}
