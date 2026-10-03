<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeePromotion extends Model
{
    protected $fillable = [
        'employee_id',
        'from_grade_id',
        'to_grade_id',
        'effective_date',
        'status',
        'reference_no',
        'justification',
        'prepared_by',
        'checked_by',
        'recommended_by',
        'approved_by',
        'checked_at',
        'recommended_at',
        'approved_at',
        'administrative_decision_id',
    ];

    protected $casts = ['effective_date' => 'date', 'checked_at' => 'datetime', 'recommended_at' => 'datetime', 'approved_at' => 'datetime'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function toGrade()
    {
        return $this->belongsTo(PositionGrade::class, 'to_grade_id');
    }
}
