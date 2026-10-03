<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ApprovedCarder;
use App\Models\Employee;
use App\Models\EmployeeIncrement;
use App\Models\AdministrativeDecision;
use App\Models\DataQualityIssue;
use App\Models\HrEscalation;
use App\Models\IncidentReport;
use App\Models\OfficialReport;
use App\Models\ReconciliationIssue;
use App\Models\RecruitmentVacancy;
use App\Models\UtilityBill;
use App\Models\Position;
use App\Models\RetirementProject;
use Illuminate\Support\Facades\Schema;

final class AdministrativeIntelligenceService
{
    public static function summary(int $year, int $months, int $recruitment, float $salaryImpact): array
    {
        $until = now()->copy()->addMonths($months);
        $employees = Employee::query()
            ->where('is_active', true)
            ->whereNotNull('position_id')
            ->get(['id', 'position_id', 'retirement_age', 'date_of_birth', 'salary_scale_id']);
        $approved = ApprovedCarder::forYear($year)->get()->groupBy('position_id')->map->sum('approved_amount');
        $retiring = $employees
            ->filter(fn (Employee $employee): bool => $employee->retire_date?->between(now(), $until) === true)
            ->count();
        $active = $employees->count();
        $future = max(0, $active - $retiring + $recruitment);
        $approvedTotal = (int) $approved->sum();

        return [
            'active_employees' => $active,
            'approved_establishment' => $approvedTotal,
            'current_establishment_gap' => max(0, $approvedTotal - $active),
            'current_fill_rate_pct' => $approvedTotal > 0 ? round(($active / $approvedTotal) * 100, 1) : 0.0,
            'projected_retirements' => $retiring,
            'recruitment_scenario' => $recruitment,
            'projected_headcount' => $future,
            'projected_gap' => max(0, $approvedTotal - $future),
            'estimated_annual_salary_impact' => round($recruitment * $salaryImpact, 2),
            'open_increments' => EmployeeIncrement::active()
                ->whereNotIn('workflow_status', ['granted', 'withheld'])
                ->count(),
            'open_retirement_projects' => RetirementProject::query()->where('status', '!=', 'completed')->count(),
            'recruitment_vacancies_open' => Schema::hasTable('recruitment_vacancies')
                ? RecruitmentVacancy::query()->whereNotIn('status', ['filled', 'cancelled', 'closed'])->sum('vacancy_count')
                : 0,
            'open_data_quality_issues' => Schema::hasTable('data_quality_issues')
                ? DataQualityIssue::query()->whereNotIn('status', ['resolved', 'verified'])->count()
                : 0,
            'open_reconciliation_issues' => Schema::hasTable('reconciliation_issues')
                ? ReconciliationIssue::query()->whereNotIn('status', ['resolved', 'verified'])->count()
                : 0,
            'open_hr_escalations' => Schema::hasTable('hr_escalations')
                ? HrEscalation::query()->where('status', 'open')->count()
                : 0,
            'pending_administrative_decisions' => Schema::hasTable('administrative_decisions')
                ? AdministrativeDecision::query()->pending()->count()
                : 0,
            'open_high_critical_incidents' => Schema::hasTable('incident_reports')
                ? IncidentReport::query()->active()->whereNotIn('status', ['closed'])->whereIn('severity', ['high', 'critical'])->count()
                : 0,
            'official_reports_in_progress' => Schema::hasTable('official_reports')
                ? OfficialReport::query()->whereNotIn('status', ['approved', 'signed', 'archived'])->count()
                : 0,
            'utility_overdue_bills' => Schema::hasTable('utility_bills')
                ? UtilityBill::query()->whereIn('status', [UtilityBill::STATUS_UNPAID, UtilityBill::STATUS_PARTIAL])->whereDate('due_date', '<', today())->count()
                : 0,
            'utility_outstanding_lkr' => Schema::hasTable('utility_bills')
                ? (float) UtilityBill::query()->whereIn('status', [UtilityBill::STATUS_UNPAID, UtilityBill::STATUS_PARTIAL])->selectRaw('COALESCE(SUM(bill_amount - amount_paid),0) total')->value('total')
                : 0.0,
            'positions' => Position::active()->count(),
        ];
    }
}
