<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeLifecycleEvent extends Model
{
    protected $fillable = ['employee_id', 'event_type', 'status', 'effective_date', 'end_date', 'reference_no', 'title', 'details', 'recorded_by'];

    protected $casts = ['effective_date' => 'date', 'end_date' => 'date'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
