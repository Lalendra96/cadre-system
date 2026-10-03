<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeAgeAnalysisController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(
            $request->user()->is_active &&
                ($request->user()->isSuperAdmin() ||
                    $request->user()->isPlanningOfficer() ||
                    $request->user()->isAdminGroup()),
            403,
        );
        $employees = Employee::query()
            ->where('is_active', true)
            ->whereNotNull('date_of_birth')
            ->with('position:id,title')
            ->get(['id', 'date_of_birth', 'position_id']);
        $bands = ['under_30' => 0, '30_39' => 0, '40_49' => 0, '50_54' => 0, '55_59' => 0, '60_plus' => 0];
        $byPosition = [];
        foreach ($employees as $employee) {
            $age = $employee->date_of_birth->age;
            $band =
                $age < 30
                    ? 'under_30'
                    : ($age < 40
                        ? '30_39'
                        : ($age < 50
                            ? '40_49'
                            : ($age < 55
                                ? '50_54'
                                : ($age < 60
                                    ? '55_59'
                                    : '60_plus'))));
            $bands[$band]++;
            $position = $employee->position?->title ?? 'Unassigned position';
            $byPosition[$position] = ($byPosition[$position] ?? 0) + ($age >= 55 ? 1 : 0);
        }
        arsort($byPosition);

        return view('workforce.age-analysis', compact('bands', 'byPosition'));
    }
}
