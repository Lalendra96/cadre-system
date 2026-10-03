<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Intern;
use App\Models\InternAssignment;
use App\Models\InternBatch;
use App\Models\InternRotationUnit;
use App\Models\InternUnitAllocation;
use App\Models\User;
use Illuminate\Support\Collection;

final class InternAllocationAnalysisService
{
    /**
     * Aggregate current allocation information without exposing personal data.
     * Closed batches are deliberately excluded from all current totals.
     */
    public static function build(User $user): array
    {
        $batchQuery = InternBatch::query()->where('is_active', true);

        if ($user->isSubjectOfficer() && ! $user->isSuperAdmin()) {
            $batchQuery->where('assigned_subject_officer_id', $user->id);
        }

        $batches = $batchQuery
            ->with('assignedSubjectOfficer:id,name,email')
            ->orderBy('start_date')
            ->orderBy('name')
            ->get();

        $batchIds = $batches->pluck('id')->map(fn ($id) => (int) $id)->all();
        $rotationUnits = InternRotationUnit::active()->ordered()->get();

        if ($batchIds === []) {
            return self::emptyAnalysis($batches, $rotationUnits);
        }

        $activeInterns = Intern::query()
            ->whereIn('intern_batch_id', $batchIds)
            ->where('is_active', true)
            ->get(['id', 'intern_batch_id']);

        $activeInternIds = $activeInterns->pluck('id')->map(fn ($id) => (int) $id)->all();
        $allocations = InternUnitAllocation::query()
            ->whereIn('intern_batch_id', $batchIds)
            ->get();

        $assignments = $activeInternIds === []
            ? collect()
            : InternAssignment::query()
                ->whereIn('intern_batch_id', $batchIds)
                ->whereIn('intern_id', $activeInternIds)
                ->get();

        $rotationRows = $rotationUnits->map(function ($unit) use ($allocations, $assignments): array {
            $firstCapacity = self::sumCapacity($allocations, (int) $unit->id, 1);
            $secondCapacity = self::sumCapacity($allocations, (int) $unit->id, 2);
            $firstAssigned = self::countAssigned($assignments, (int) $unit->id, 1);
            $secondAssigned = self::countAssigned($assignments, (int) $unit->id, 2);

            return [
                'rotation_unit_id' => (int) $unit->id,
                'rotation_unit' => $unit->name,
                'first_capacity' => $firstCapacity,
                'first_assigned' => $firstAssigned,
                'first_remaining_slots' => max(0, $firstCapacity - $firstAssigned),
                'first_overallocated' => max(0, $firstAssigned - $firstCapacity),
                'second_capacity' => $secondCapacity,
                'second_assigned' => $secondAssigned,
                'second_remaining_slots' => max(0, $secondCapacity - $secondAssigned),
                'second_overallocated' => max(0, $secondAssigned - $secondCapacity),
            ];
        })->values();

        $totalInterns = $activeInterns->count();
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

        $assignedByIntern = $assignments->groupBy('intern_id');
        $fullyAssignedInterns = $activeInterns->filter(function ($intern) use ($assignedByIntern): bool {
            $appointmentNumbers = $assignedByIntern
                ->get($intern->id, collect())
                ->pluck('appointment_number')
                ->unique();

            return $appointmentNumbers->contains(1) && $appointmentNumbers->contains(2);
        })->count();

        $batchRows = $batches->map(function (InternBatch $batch) use ($activeInterns, $allocations, $assignments): array {
            $internIds = $activeInterns
                ->where('intern_batch_id', $batch->id)
                ->pluck('id');
            $internCount = $internIds->count();
            $batchAssignments = $assignments->where('intern_batch_id', $batch->id);
            $firstAssigned = $batchAssignments
                ->where('appointment_number', 1)
                ->pluck('intern_id')
                ->unique()
                ->count();
            $secondAssigned = $batchAssignments
                ->where('appointment_number', 2)
                ->pluck('intern_id')
                ->unique()
                ->count();
            $batchAllocations = $allocations->where('intern_batch_id', $batch->id);

            return [
                'batch' => $batch,
                'interns' => $internCount,
                'first_capacity' => (int) $batchAllocations->where('appointment_number', 1)->sum('capacity'),
                'first_assigned' => $firstAssigned,
                'first_remaining_interns' => max(0, $internCount - $firstAssigned),
                'second_capacity' => (int) $batchAllocations->where('appointment_number', 2)->sum('capacity'),
                'second_assigned' => $secondAssigned,
                'second_remaining_interns' => max(0, $internCount - $secondAssigned),
            ];
        })->values();

        $firstCapacity = (int) $allocations->where('appointment_number', 1)->sum('capacity');
        $secondCapacity = (int) $allocations->where('appointment_number', 2)->sum('capacity');

        $alerts = collect();
        foreach ($rotationRows as $row) {
            if ($row['first_overallocated'] > 0) {
                $alerts->push("{$row['rotation_unit']} has {$row['first_overallocated']} 1st Appointment assignment(s) above configured capacity.");
            }
            if ($row['second_overallocated'] > 0) {
                $alerts->push("{$row['rotation_unit']} has {$row['second_overallocated']} 2nd Appointment assignment(s) above configured capacity.");
            }
        }
        if ($firstCapacity < $totalInterns) {
            $alerts->push('Total configured 1st Appointment capacity is lower than the active intern count.');
        }
        if ($secondCapacity < $totalInterns) {
            $alerts->push('Total configured 2nd Appointment capacity is lower than the active intern count.');
        }

        return [
            'batches' => $batches,
            'batch_rows' => $batchRows,
            'rotation_rows' => $rotationRows,
            'alerts' => $alerts->values(),
            'summary' => [
                'active_batches' => $batches->count(),
                'active_interns' => $totalInterns,
                'first_capacity' => $firstCapacity,
                'first_assigned' => $firstAssignedInterns,
                'first_remaining_interns' => max(0, $totalInterns - $firstAssignedInterns),
                'second_capacity' => $secondCapacity,
                'second_assigned' => $secondAssignedInterns,
                'second_remaining_interns' => max(0, $totalInterns - $secondAssignedInterns),
                'fully_assigned_interns' => $fullyAssignedInterns,
                'not_fully_assigned_interns' => max(0, $totalInterns - $fullyAssignedInterns),
                'first_utilisation_pct' => $firstCapacity > 0 ? round(($firstAssignedInterns / $firstCapacity) * 100, 1) : 0.0,
                'second_utilisation_pct' => $secondCapacity > 0 ? round(($secondAssignedInterns / $secondCapacity) * 100, 1) : 0.0,
            ],
        ];
    }

    private static function sumCapacity(Collection $allocations, int $unitId, int $appointment): int
    {
        return (int) $allocations
            ->where('intern_rotation_unit_id', $unitId)
            ->where('appointment_number', $appointment)
            ->sum('capacity');
    }

    private static function countAssigned(Collection $assignments, int $unitId, int $appointment): int
    {
        return $assignments
            ->where('intern_rotation_unit_id', $unitId)
            ->where('appointment_number', $appointment)
            ->pluck('intern_id')
            ->unique()
            ->count();
    }

    private static function emptyAnalysis(Collection $batches, Collection $rotationUnits): array
    {
        return [
            'batches' => $batches,
            'batch_rows' => collect(),
            'rotation_rows' => $rotationUnits->map(fn ($unit) => [
                'rotation_unit_id' => (int) $unit->id,
                'rotation_unit' => $unit->name,
                'first_capacity' => 0,
                'first_assigned' => 0,
                'first_remaining_slots' => 0,
                'first_overallocated' => 0,
                'second_capacity' => 0,
                'second_assigned' => 0,
                'second_remaining_slots' => 0,
                'second_overallocated' => 0,
            ])->values(),
            'alerts' => collect(),
            'summary' => [
                'active_batches' => 0,
                'active_interns' => 0,
                'first_capacity' => 0,
                'first_assigned' => 0,
                'first_remaining_interns' => 0,
                'second_capacity' => 0,
                'second_assigned' => 0,
                'second_remaining_interns' => 0,
                'fully_assigned_interns' => 0,
                'not_fully_assigned_interns' => 0,
                'first_utilisation_pct' => 0.0,
                'second_utilisation_pct' => 0.0,
            ],
        ];
    }
}
