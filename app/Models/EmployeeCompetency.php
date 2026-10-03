<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeCompetency extends Model
{
    protected $fillable = [
        'employee_id',
        'competency_id',
        'level',
        'assessed_on',
        'expires_on',
        'assessed_by',
        'notes',
    ];

    protected $casts = [
        'assessed_on' => 'date',
        'expires_on' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function competency()
    {
        return $this->belongsTo(Competency::class);
    }
}
