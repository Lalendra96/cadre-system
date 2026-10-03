<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeTrainingRecord extends Model
{
    protected $fillable = [
        'employee_id',
        'course_name',
        'provider',
        'completed_on',
        'expires_on',
        'certificate_reference',
        'status',
        'recorded_by',
    ];

    protected $casts = [
        'completed_on' => 'date',
        'expires_on' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
