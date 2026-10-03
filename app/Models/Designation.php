<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Designation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'designations';

    protected $fillable = ['code', 'title', 'grade', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function positions()
    {
        return $this->hasMany(Position::class, 'designation_id');
    }

    /** All Subject Codes reachable through this designation's Positions. */
    public function subjectCodes()
    {
        return $this->hasManyThrough(SubjectCode::class, Position::class, 'designation_id', 'position_id');
    }

    /** All Approved Carder rows reachable through this designation's Positions. */
    public function approvedCarders()
    {
        return $this->hasManyThrough(ApprovedCarder::class, Position::class, 'designation_id', 'position_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->whereRaw('title ILIKE ?', ["%{$term}%"])
                ->orWhereRaw('code ILIKE ?', ["%{$term}%"]);
        });
    }
}
