<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ApprovedCarder;
use App\Models\Employee;

final class ScenarioComparisonService
{
    public static function compare(int $year, int $months, array $scenarios): array
    {
        $approved = (int) ApprovedCarder::forYear($year)->sum('approved_amount');
        $active = Employee::query()->where('is_active', true)->whereNotNull('position_id')->get();
        $retiring = $active
            ->filter(
                fn (Employee $employee): bool => $employee->retire_date?->between(
                    now(),
                    now()->copy()->addMonths($months),
                ) === true,
            )
            ->count();

        return collect($scenarios)
            ->map(function (array $scenario) use ($approved, $active, $retiring): array {
                $recruitment = max(0, (int) ($scenario['recruitment'] ?? 0));
                $extraRetirements = max(0, (int) ($scenario['extra_retirements'] ?? 0));
                $future = max(0, $active->count() - $retiring - $extraRetirements + $recruitment);

                return [
                    'name' => (string) ($scenario['name'] ?? 'Scenario'),
                    'recruitment' => $recruitment,
                    'retirements' => $retiring + $extraRetirements,
                    'projected_headcount' => $future,
                    'projected_gap' => max(0, $approved - $future),
                    'fill_rate' => $approved > 0 ? round(($future / $approved) * 100, 1) : null,
                ];
            })
            ->all();
    }
}
