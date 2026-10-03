<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetirementProject extends Model
{
    protected $fillable = [
        'employee_id',
        'retirement_date',
        'status',
        'notification_issued_date',
        'employee_acknowledged_date',
        'replacement_required',
        'pension_status',
        'clearance_status',
        'handover_status',
        'final_working_date',
        'completed_at',
        'reference_no',
        'remarks',
        'dob_verified',
        'service_verified',
        'contact_verified',
        'documents_verified',
        'vacancy_created',
        'last_reminder_at',
        'created_by',
        'updated_by',
        'administrative_decision_id',
    ];

    protected $casts = [
        'retirement_date' => 'date',
        'notification_issued_date' => 'date',
        'employee_acknowledged_date' => 'date',
        'replacement_required' => 'boolean',
        'final_working_date' => 'date',
        'completed_at' => 'date',
        'dob_verified' => 'boolean',
        'service_verified' => 'boolean',
        'contact_verified' => 'boolean',
        'documents_verified' => 'boolean',
        'vacancy_created' => 'boolean',
        'last_reminder_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
