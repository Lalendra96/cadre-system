<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UtilityBillResponsibilityAssignment extends Model
{
    protected $fillable = ['subject_officer_id', 'effective_from', 'effective_to', 'reference_no', 'reason', 'assigned_by', 'ended_at', 'ended_by', 'end_reason'];
    protected $casts = ['effective_from' => 'date', 'effective_to' => 'date', 'ended_at' => 'datetime'];

    public function subjectOfficer() { return $this->belongsTo(User::class, 'subject_officer_id'); }
    public function assignedBy() { return $this->belongsTo(User::class, 'assigned_by'); }
}
