<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasDisableWorkflow;
use Illuminate\Database\Eloquent\Model;

class EmployeeQualification extends Model
{
    use HasDisableWorkflow;

    protected $fillable = [
        'employee_id', 'qualification_name', 'institution', 'year_obtained',
        'reference_no', 'notes', 'recorded_by',
        'is_active', 'disabled_by', 'disabled_at', 'disable_reason',
    ];

    protected $casts = [
        'year_obtained' => 'integer',
        'is_active'     => 'boolean',
        'disabled_at'   => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
