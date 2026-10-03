<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The CAPACITY ("amount") of slots a rotation unit has for a given
 * batch and appointment number (1st or 2nd) — see migration docblock.
 */
class InternUnitAllocation extends Model
{
    protected $fillable = ['intern_batch_id', 'intern_rotation_unit_id', 'appointment_number', 'capacity'];

    protected $casts = ['appointment_number' => 'integer', 'capacity' => 'integer'];

    public function batch()
    {
        return $this->belongsTo(InternBatch::class, 'intern_batch_id');
    }

    public function rotationUnit()
    {
        return $this->belongsTo(InternRotationUnit::class, 'intern_rotation_unit_id');
    }

    /** Assignments actually filling this unit+appointment's slots (not scoped by this specific row's id — matched by batch+unit+appointment). */
    public function filledAssignments()
    {
        return InternAssignment::where('intern_batch_id', $this->intern_batch_id)
            ->where('intern_rotation_unit_id', $this->intern_rotation_unit_id)
            ->where('appointment_number', $this->appointment_number);
    }
}
