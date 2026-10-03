<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\InternAssignment;
use App\Models\InternBatch;
use App\Models\InternRotationUnit;
use App\Models\InternUnitAllocation;
use App\Services\AuditLogService;
use App\Services\InternAllocationAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InternUnitAllocationController extends Controller
{
    public function edit(Request $request, InternBatch $batch)
    {
        InternAllocationAccessService::assertCanView($request->user());

        $rotationUnits = InternRotationUnit::active()->ordered()->get();
        $allocations = InternUnitAllocation::where('intern_batch_id', $batch->id)->get();
        $capacityMap = [];

        foreach ($allocations as $allocation) {
            $capacityMap[$allocation->intern_rotation_unit_id][$allocation->appointment_number] = $allocation->capacity;
        }

        $canEdit = InternAllocationAccessService::canEditBatch($request->user(), $batch);
        $activeInternCount = $batch->interns()->count();
        $assignments = InternAssignment::query()
            ->where('intern_batch_id', $batch->id)
            ->whereHas('intern', fn ($query) => $query->where('is_active', true))
            ->get();

        $allocationBreakdown = $rotationUnits->map(function ($unit) use ($capacityMap, $assignments): array {
            $firstCapacity = (int) ($capacityMap[$unit->id][1] ?? 0);
            $secondCapacity = (int) ($capacityMap[$unit->id][2] ?? 0);
            $firstAssigned = $assignments
                ->where('intern_rotation_unit_id', $unit->id)
                ->where('appointment_number', 1)
                ->pluck('intern_id')
                ->unique()
                ->count();
            $secondAssigned = $assignments
                ->where('intern_rotation_unit_id', $unit->id)
                ->where('appointment_number', 2)
                ->pluck('intern_id')
                ->unique()
                ->count();

            return [
                'unit' => $unit,
                'first_capacity' => $firstCapacity,
                'first_assigned' => $firstAssigned,
                'first_remaining_slots' => max(0, $firstCapacity - $firstAssigned),
                'second_capacity' => $secondCapacity,
                'second_assigned' => $secondAssigned,
                'second_remaining_slots' => max(0, $secondCapacity - $secondAssigned),
            ];
        });

        $firstAssignedInterns = $assignments
            ->where('appointment_number', 1)
            ->pluck('intern_id')
            ->unique()
            ->count();
        $secondAssignedInterns = $assignments
            ->where('appointment_number', 2)
            ->pluck('intern_id')
            ->unique()
            ->count();

        $allocationSummary = [
            'active_interns' => $activeInternCount,
            'first_assigned' => $firstAssignedInterns,
            'first_remaining_interns' => max(0, $activeInternCount - $firstAssignedInterns),
            'second_assigned' => $secondAssignedInterns,
            'second_remaining_interns' => max(0, $activeInternCount - $secondAssignedInterns),
        ];

        return view('intern-batches.allocations', compact(
            'batch',
            'rotationUnits',
            'capacityMap',
            'canEdit',
            'allocationBreakdown',
            'allocationSummary',
        ));
    }

    public function update(Request $request, InternBatch $batch): RedirectResponse
    {
        InternAllocationAccessService::assertCanView($request->user());
        InternAllocationAccessService::assertCanEdit($request->user(), $batch);

        $data = $request->validate([
            'capacity' => ['required', 'array'],
            'capacity.*.*' => ['required', 'integer', 'min:0', 'max:50'],
        ]);

        foreach ($data['capacity'] as $rotationUnitId => $byAppointment) {
            foreach ($byAppointment as $appointmentNumber => $capacity) {
                $allocation = InternUnitAllocation::firstOrNew([
                    'intern_batch_id' => $batch->id,
                    'intern_rotation_unit_id' => (int) $rotationUnitId,
                    'appointment_number' => (int) $appointmentNumber,
                ]);

                $old = $allocation->exists ? $allocation->getOriginal() : [];
                $allocation->capacity = (int) $capacity;
                $allocation->save();

                if ($old) {
                    AuditLogService::updated($allocation, $old, 'Updated intern rotation capacity.');
                } else {
                    AuditLogService::created($allocation, 'Created intern rotation capacity.');
                }
            }
        }

        return redirect()
            ->route('intern-batches.allocations.edit', $batch)
            ->with('success', 'Capacity updated for '.$batch->name.'.');
    }
}
