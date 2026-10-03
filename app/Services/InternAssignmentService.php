<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Intern;
use App\Models\InternAssignment;
use App\Models\InternRotationUnit;
use App\Models\InternUnitAllocation;
use Illuminate\Support\Facades\DB;

/**
 * Shared assignment logic — used by BOTH the officer-side manual
 * assignment screen AND the public self-selection portal, so the two
 * paths can never enforce different rules. Every write goes through
 * assign() here; neither controller writes to intern_assignments directly.
 */
class InternAssignmentService
{
    /**
     * @throws \RuntimeException if the intern already has an assignment
     *                           for this appointment number, or the chosen slot is full/taken.
     */
    public static function assign(
        Intern $intern,
        InternRotationUnit $rotationUnit,
        int $appointmentNumber,
        ?int $assignedByUserId,
    ): InternAssignment {
        return DB::transaction(function () use ($intern, $rotationUnit, $appointmentNumber, $assignedByUserId) {
            $existing = InternAssignment::where('intern_id', $intern->id)
                ->where('appointment_number', $appointmentNumber)
                ->first();
            if ($existing) {
                throw new \RuntimeException("{$intern->name} already has a {$appointmentNumber}".self::ordinalSuffix($appointmentNumber)." Appointment assignment ({$existing->rotationUnit->name}). Remove it first to reassign.");
            }

            $allocation = InternUnitAllocation::where('intern_batch_id', $intern->intern_batch_id)
                ->where('intern_rotation_unit_id', $rotationUnit->id)
                ->where('appointment_number', $appointmentNumber)
                ->lockForUpdate()
                ->first();

            if (! $allocation || $allocation->capacity <= 0) {
                throw new \RuntimeException("{$rotationUnit->name} has no capacity configured for the {$appointmentNumber}".self::ordinalSuffix($appointmentNumber).' Appointment.');
            }

            $takenSlots = InternAssignment::where('intern_batch_id', $intern->intern_batch_id)
                ->where('intern_rotation_unit_id', $rotationUnit->id)
                ->where('appointment_number', $appointmentNumber)
                ->lockForUpdate()
                ->pluck('slot_number')
                ->all();

            if (count($takenSlots) >= $allocation->capacity) {
                throw new \RuntimeException("{$rotationUnit->name} is full for the {$appointmentNumber}".self::ordinalSuffix($appointmentNumber)." Appointment ({$allocation->capacity} of {$allocation->capacity} slots taken).");
            }

            // First free slot number within capacity (1-indexed, matching the numbered form).
            $slotNumber = 1;
            while (in_array($slotNumber, $takenSlots, true)) {
                $slotNumber++;
            }

            return InternAssignment::create([
                'intern_id' => $intern->id,
                'intern_batch_id' => $intern->intern_batch_id,
                'intern_rotation_unit_id' => $rotationUnit->id,
                'appointment_number' => $appointmentNumber,
                'slot_number' => $slotNumber,
                'assigned_by' => $assignedByUserId,
                'assigned_at' => now(),
            ]);
        });
    }

    /** Remaining open slots for a rotation unit + appointment, within a batch. */
    public static function remainingCapacity(int $batchId, int $rotationUnitId, int $appointmentNumber): int
    {
        $allocation = InternUnitAllocation::where('intern_batch_id', $batchId)
            ->where('intern_rotation_unit_id', $rotationUnitId)
            ->where('appointment_number', $appointmentNumber)
            ->first();
        if (! $allocation) {
            return 0;
        }
        $taken = InternAssignment::where('intern_batch_id', $batchId)
            ->where('intern_rotation_unit_id', $rotationUnitId)
            ->where('appointment_number', $appointmentNumber)
            ->count();

        return max($allocation->capacity - $taken, 0);
    }

    private static function ordinalSuffix(int $n): string
    {
        return match (true) {
            $n === 1 => 'st',
            $n === 2 => 'nd',
            $n === 3 => 'rd',
            default => 'th',
        };
    }
}
