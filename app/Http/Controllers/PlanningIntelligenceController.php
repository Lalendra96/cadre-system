<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AdministrativeIntelligenceService;
use Illuminate\Http\Request;

class PlanningIntelligenceController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(
            $request->user()?->canAccessPlanningReports(),
            403,
            'Hospital Planning Intelligence is limited to Planning Officers, Medical Officer Planning, and Super Admin.'
        );

        $data = $request->validate([
            'year' => 'nullable|integer|min:2000|max:2200',
            'months' => 'nullable|integer|min:1|max:60',
            'recruitment' => 'nullable|integer|min:0|max:100000',
            'salary_impact' => 'nullable|numeric|min:0|max:100000000',
        ]);
        $summary = AdministrativeIntelligenceService::summary(
            (int) ($data['year'] ?? now()->year),
            (int) ($data['months'] ?? 12),
            (int) ($data['recruitment'] ?? 0),
            (float) ($data['salary_impact'] ?? 0),
        );

        return view('workforce.planning-intelligence', compact('summary'));
    }
}
