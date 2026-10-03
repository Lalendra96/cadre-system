<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ScenarioComparisonService;
use Illuminate\Http\Request;

class ScenarioComparisonController extends Controller
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
        $data = $request->validate([
            'year' => 'nullable|integer|min:2000|max:2200',
            'months' => 'nullable|integer|min:1|max:60',
            'base_recruitment' => 'nullable|integer|min:0|max:100000',
            'planned_recruitment' => 'nullable|integer|min:0|max:100000',
            'high_retirement' => 'nullable|integer|min:0|max:100000',
        ]);
        $scenarios = ScenarioComparisonService::compare(
            (int) ($data['year'] ?? now()->year),
            (int) ($data['months'] ?? 12),
            [
                ['name' => 'Base case', 'recruitment' => $data['base_recruitment'] ?? 0],
                ['name' => 'Planned recruitment', 'recruitment' => $data['planned_recruitment'] ?? 0],
                [
                    'name' => 'High retirement',
                    'recruitment' => $data['base_recruitment'] ?? 0,
                    'extra_retirements' => $data['high_retirement'] ?? 0,
                ],
            ],
        );

        return view('workforce.scenario-comparison', compact('scenarios'));
    }
}
