<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AdministrativeIntelligenceService;
use Illuminate\Http\Request;

class AdministrativeIntelligenceController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'year' => 'nullable|integer|min:2000|max:2200',
            'months' => 'nullable|integer|min:1|max:60',
            'recruitment' => 'nullable|integer|min:0|max:100000',
            'salary_impact' => 'nullable|numeric|min:0|max:100000000',
        ]);
        $year = (int) ($data['year'] ?? now()->year);
        $months = (int) ($data['months'] ?? 12);
        $recruitment = (int) ($data['recruitment'] ?? 0);
        $salaryImpact = (float) ($data['salary_impact'] ?? 0);

        $summary = AdministrativeIntelligenceService::summary(
            $year,
            $months,
            $recruitment,
            $salaryImpact,
        );

        $workforceChart = [
            ['label' => 'Approved', 'value' => (int) $summary['approved_establishment']],
            ['label' => 'Active', 'value' => (int) $summary['active_employees']],
            ['label' => 'Projected', 'value' => (int) $summary['projected_headcount']],
            ['label' => 'Projected gap', 'value' => (int) $summary['projected_gap']],
        ];

        $riskChart = [
            ['label' => 'HR escalations', 'value' => (int) $summary['open_hr_escalations']],
            ['label' => 'Data quality', 'value' => (int) $summary['open_data_quality_issues']],
            ['label' => 'Reconciliation', 'value' => (int) $summary['open_reconciliation_issues']],
            ['label' => 'Serious incidents', 'value' => (int) $summary['open_high_critical_incidents']],
            ['label' => 'Pending decisions', 'value' => (int) $summary['pending_administrative_decisions']],
        ];

        $priorities = collect([
            ['label' => 'HR escalations requiring follow-up', 'value' => $summary['open_hr_escalations'], 'route' => 'hr-intelligence.index', 'severity' => 'danger'],
            ['label' => 'High / critical incidents', 'value' => $summary['open_high_critical_incidents'], 'route' => 'incidents.index', 'severity' => 'danger'],
            ['label' => 'Overdue utility bills', 'value' => $summary['utility_overdue_bills'], 'route' => 'utility-bills.index', 'severity' => 'warning'],
            ['label' => 'Reconciliation issues', 'value' => $summary['open_reconciliation_issues'], 'route' => 'workforce.reconciliation', 'severity' => 'warning'],
            ['label' => 'Pending administrative decisions', 'value' => $summary['pending_administrative_decisions'], 'route' => 'administrative-decisions.index', 'severity' => 'warning'],
            ['label' => 'Official reports in progress', 'value' => $summary['official_reports_in_progress'], 'route' => 'official-reports.index', 'severity' => 'info'],
        ])->sortByDesc('value')->values();

        return view('workforce.administrative-intelligence', compact(
            'summary',
            'year',
            'months',
            'recruitment',
            'salaryImpact',
            'workforceChart',
            'riskChart',
            'priorities',
        ));
    }
}
