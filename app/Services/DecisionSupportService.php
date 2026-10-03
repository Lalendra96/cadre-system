<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DataQualityIssue;
use App\Models\Employee;
use App\Models\EmployeeIncrement;
use App\Models\TransferRecord;
use App\Models\Unit;
use App\Models\UnitPositionAllocation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Builds aggregate-only decision-support datasets.
 *
 * This service deliberately returns counts and grouped summaries rather than
 * employee profiles or direct identifiers. It is used for management/planning
 * views where the purpose is operational decision support, not case handling.
 */
class DecisionSupportService
{
    /**
     * Institution-wide planning/admin pulse.
     *
     * @return array<string, mixed>
     */
    public function institutionPulse(): array
    {
        $employees = Employee::query()->where('employees.is_active', true);
        $employeeIds = (clone $employees)->pluck('id');

        return $this->buildPulse($employees, $employeeIds);
    }

    /**
     * Unit-scoped pulse for one or more assigned units.
     *
     * @param  Collection<int, Unit>  $units
     * @return array<string, mixed>
     */
    public function unitPulse(Collection $units): array
    {
        $unitIds = $units->pluck('id')->map(fn ($id) => (int) $id)->values();

        if ($unitIds->isEmpty()) {
            return $this->emptyPulse();
        }

        $employees = Employee::query()
            ->where('employees.is_active', true)
            ->whereIn('employees.unit_id', $unitIds);

        $employeeIds = (clone $employees)->pluck('id');
        $pulse = $this->buildPulse($employees, $employeeIds);

        $year = (int) now()->year;
        $allocations = UnitPositionAllocation::query()
            ->with(['unit:id,name,code', 'position:id,title'])
            ->whereIn('unit_id', $unitIds)
            ->forYear($year)
            ->mainLine()
            ->get();

        $pulse['allocatedPosts'] = (int) $allocations->sum('allocated_posts');
        $pulse['actualInPost'] = (int) $allocations->sum('actual_in_post');
        $pulse['vacancyGap'] = (int) $allocations->sum(
            fn (UnitPositionAllocation $row) => max((int) $row->allocated_posts - (int) $row->actual_in_post, 0)
        );
        $pulse['allocationFillRate'] = $pulse['allocatedPosts'] > 0
            ? round(($pulse['actualInPost'] / $pulse['allocatedPosts']) * 100, 1)
            : 0.0;

        $pulse['topVacancyRows'] = $allocations
            ->map(fn (UnitPositionAllocation $row) => [
                'unit' => $row->unit?->name ?? 'Unknown unit',
                'position' => $row->position?->title ?? 'Unknown position',
                'allocated' => (int) $row->allocated_posts,
                'actual' => (int) $row->actual_in_post,
                'gap' => max((int) $row->allocated_posts - (int) $row->actual_in_post, 0),
            ])
            ->sortByDesc('gap')
            ->take(8)
            ->values();

        return $pulse;
    }

    /**
     * @param  Builder<Employee>  $employees
     * @param  Collection<int, int>  $employeeIds
     * @return array<string, mixed>
     */
    private function buildPulse($employees, Collection $employeeIds): array
    {
        $now = now();
        $activeCount = (clone $employees)->count();

        $positionMix = (clone $employees)
            ->leftJoin('positions', 'positions.id', '=', 'employees.position_id')
            ->selectRaw("COALESCE(positions.title, 'Unassigned') AS label, COUNT(employees.id) AS total")
            ->groupBy('positions.title')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn ($row) => ['label' => (string) $row->label, 'total' => (int) $row->total]);

        $statusMix = (clone $employees)
            ->selectRaw("COALESCE(employment_status, 'unknown') AS label, COUNT(*) AS total")
            ->groupBy('employment_status')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'label' => ucwords(str_replace('_', ' ', (string) $row->label)),
                'total' => (int) $row->total,
            ]);

        $retirementRows = (clone $employees)
            ->whereNotNull('date_of_birth')
            ->get(['date_of_birth', 'retirement_age']);

        $retirements12m = $retirementRows
            ->filter(fn (Employee $employee) => $employee->retire_date
                && $employee->retire_date->between($now->copy()->startOfDay(), $now->copy()->addMonths(12)->endOfDay()))
            ->count();

        $retirements24m = $retirementRows
            ->filter(fn (Employee $employee) => $employee->retire_date
                && $employee->retire_date->between($now->copy()->startOfDay(), $now->copy()->addMonths(24)->endOfDay()))
            ->count();

        $increments30 = $employeeIds->isEmpty()
            ? 0
            : EmployeeIncrement::query()
                ->where('is_active', true)
                ->whereIn('employee_id', $employeeIds)
                ->whereDate('increment_date', '>=', $now->toDateString())
                ->whereDate('increment_date', '<=', $now->copy()->addDays(30)->toDateString())
                ->count();

        $qualityOpen = $employeeIds->isEmpty()
            ? 0
            : DataQualityIssue::query()
                ->whereIn('employee_id', $employeeIds)
                ->whereNotIn('status', ['resolved', 'verified'])
                ->count();

        $registrationExpiry90 = (clone $employees)
            ->whereNotNull('professional_registration_expiry')
            ->whereDate('professional_registration_expiry', '>=', $now->toDateString())
            ->whereDate('professional_registration_expiry', '<=', $now->copy()->addDays(90)->toDateString())
            ->count();

        $missingCoreData = (clone $employees)
            ->where(function ($query): void {
                $query->whereNull('position_id')
                    ->orWhereNull('subject_code_id')
                    ->orWhereNull('date_of_birth');
            })
            ->count();

        $transferIn90 = $employeeIds->isEmpty()
            ? 0
            : TransferRecord::query()
                ->where('is_active', true)
                ->whereIn('employee_id', $employeeIds)
                ->where('direction', 'in')
                ->whereBetween('effective_date', [
                    $now->copy()->subDays(90)->toDateString(),
                    $now->toDateString(),
                ])
                ->count();

        $transferOut90 = $employeeIds->isEmpty()
            ? 0
            : TransferRecord::query()
                ->where('is_active', true)
                ->whereIn('employee_id', $employeeIds)
                ->where('direction', 'out')
                ->whereBetween('effective_date', [
                    $now->copy()->subDays(90)->toDateString(),
                    $now->toDateString(),
                ])
                ->count();

        return [
            'activeEmployees' => $activeCount,
            'retirements12m' => $retirements12m,
            'retirements24m' => $retirements24m,
            'increments30' => $increments30,
            'qualityOpen' => $qualityOpen,
            'registrationExpiry90' => $registrationExpiry90,
            'missingCoreData' => $missingCoreData,
            'transferIn90' => $transferIn90,
            'transferOut90' => $transferOut90,
            'netTransfer90' => $transferIn90 - $transferOut90,
            'positionMix' => $positionMix,
            'statusMix' => $statusMix,
            'allocatedPosts' => 0,
            'actualInPost' => 0,
            'vacancyGap' => 0,
            'allocationFillRate' => 0.0,
            'topVacancyRows' => collect(),
        ];
    }

    /** @return array<string, mixed> */
    private function emptyPulse(): array
    {
        return [
            'activeEmployees' => 0,
            'retirements12m' => 0,
            'retirements24m' => 0,
            'increments30' => 0,
            'qualityOpen' => 0,
            'registrationExpiry90' => 0,
            'missingCoreData' => 0,
            'transferIn90' => 0,
            'transferOut90' => 0,
            'netTransfer90' => 0,
            'positionMix' => collect(),
            'statusMix' => collect(),
            'allocatedPosts' => 0,
            'actualInPost' => 0,
            'vacancyGap' => 0,
            'allocationFillRate' => 0.0,
            'topVacancyRows' => collect(),
        ];
    }
}
