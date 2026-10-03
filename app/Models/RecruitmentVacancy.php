<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecruitmentVacancy extends Model
{
    protected $fillable = [
        'position_id',
        'retirement_project_id',
        'vacancy_count',
        'status',
        'identified_on',
        'requested_on',
        'filled_on',
        'reference_no',
        'notes',
        'created_by',
    ];

    protected $casts = ['identified_on' => 'date', 'requested_on' => 'date', 'filled_on' => 'date'];

    public function position()
    {
        return $this->belongsTo(Position::class);
    }
}
