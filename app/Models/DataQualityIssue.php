<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataQualityIssue extends Model
{
    protected $fillable = ['employee_id', 'rule_code', 'label', 'severity', 'status', 'assigned_to', 'due_date', 'resolution_note', 'resolved_by', 'resolved_at', 'verified_by', 'verified_at'];

    protected $casts = ['due_date' => 'date', 'resolved_at' => 'datetime', 'verified_at' => 'datetime'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
