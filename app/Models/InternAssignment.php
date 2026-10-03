<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InternAssignment extends Model
{
    protected $fillable = [
        'intern_id', 'intern_batch_id', 'intern_rotation_unit_id',
        'appointment_number', 'slot_number', 'assigned_by', 'assigned_at',
    ];

    protected $casts = [
        'appointment_number' => 'integer',
        'slot_number' => 'integer',
        'assigned_at' => 'datetime',
    ];

    public function intern()
    {
        return $this->belongsTo(Intern::class);
    }

    public function batch()
    {
        return $this->belongsTo(InternBatch::class, 'intern_batch_id');
    }

    public function rotationUnit()
    {
        return $this->belongsTo(InternRotationUnit::class, 'intern_rotation_unit_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /** True if this slot was filled by the intern themselves via the public portal, not an officer. */
    public function isSelfSelected(): bool
    {
        return $this->assigned_by === null;
    }
}
