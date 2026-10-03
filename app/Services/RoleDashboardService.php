<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AdministrativeDecision;
use App\Models\ApprovedCarder;
use App\Models\AuditLog;
use App\Models\CarderMonthlyEntry;
use App\Models\CarPassRequest;
use App\Models\DataQualityIssue;
use App\Models\Employee;
use App\Models\EmployeeIncrement;
use App\Models\EmployeePromotion;
use App\Models\IncidentReport;
use App\Models\InternBatch;
use App\Models\Letter;
use App\Models\NavItem;
use App\Models\Position;
use App\Models\RetirementProject;
use App\Models\ServiceLetter;
use App\Models\User;
use App\Models\UserDashboardLayout;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class RoleDashboardService
{
    public function roleKey(User $user): string
    {
        if ($user->isSuperAdmin()) {
            return 'super_admin';
        }

        if ($user->isPlanningOfficer()) {
            return 'planning_officer';
        }

        if ($user->isAdminGroup()) {
            return 'admin_group';
        }

        if ($user->isSubjectOfficer()) {
            return 'subject_officer';
        }

        if ($user->isUnitManager()) {
            return 'unit_manager';
        }

        return 'general';
    }

    public function dashboardTitle(string $roleKey): string
    {
        return match ($roleKey) {
            'super_admin' => 'System & Workforce Analytics',
            'planning_officer' => 'Workforce Planning Analytics',
            'admin_group' => 'Administrative Oversight Dashboard',
            'subject_officer' => 'My Operational Dashboard',
            'unit_manager' => 'Unit Workforce Dashboard',
            default => 'My Dashboard',
        };
    }

    public function dashboardSubtitle(string $roleKey): string
    {
        return match ($roleKey) {
            'super_admin' => 'Institution-wide governance, workload and system oversight.',
            'planning_officer' => 'Aggregate workforce trends, vacancies, retirements and planning signals.',
            'admin_group' => 'Governed administrative workload, approvals and service delivery indicators.',
            'subject_officer' => 'Only responsibilities and records within your formally assigned scope.',
            'unit_manager' => 'Aggregate information for units within your authorised decision-support scope.',
            default => 'Information available to your account and role.',
        };
    }

    public function widgetCatalog(User $user): array
    {
        $roleKey = $this->roleKey($user);

        $common = [
            'quick_links' => $this->definition(
                'quick_links',
                'Quick Links',
                'Your saved shortcuts to authorised areas of the system.',
                'wide'
            ),
            'governance_notice' => $this->definition(
                'governance_notice',
                'Governance & Data Use',
                'Read-only scope and data-handling notice.',
                'wide'
            ),
        ];

        $catalog = match ($roleKey) {
            'super_admin' => [
                'workforce_kpis' => $this->definition('workforce_kpis', 'Workforce Overview', 'Active employees, establishment and vacancy gap.', 'wide'),
                'workforce_distribution' => $this->definition('workforce_distribution', 'Workforce by Position', 'Largest active workforce groups.', 'half'),
                'retirement_outlook' => $this->definition('retirement_outlook', 'Retirement Outlook', 'Recorded retirement projects by year.', 'half'),
                'workflow_backlog' => $this->definition('workflow_backlog', 'Workflow Backlog', 'Pending approvals and governed work queues.', 'half'),
                'intern_allocation' => $this->definition('intern_allocation', 'Intern Allocation', 'Active batches, interns and RHO follow-up.', 'half'),
                'car_pass_analytics' => $this->definition('car_pass_analytics', 'Car Pass Workflow', 'Pending, issued and expiring vehicle passes.', 'half'),
                'data_quality' => $this->definition('data_quality', 'Data Quality', 'Open data-quality findings by severity.', 'half'),
                'profile_completion' => $this->definition('profile_completion', 'Profile Completion', 'Handling count versus current active Employee Profile count.', 'half'),
                'system_activity' => $this->definition('system_activity', 'Recent Governed Activity', 'Recent audit-log events without exposing secret values.', 'wide'),
            ],
            'planning_officer' => [
                'workforce_kpis' => $this->definition('workforce_kpis', 'Workforce Planning Overview', 'Establishment, filled positions and vacancy gap.', 'wide'),
                'vacancy_analysis' => $this->definition('vacancy_analysis', 'Vacancy Analysis', 'Largest establishment gaps by position.', 'half'),
                'retirement_outlook' => $this->definition('retirement_outlook', 'Retirement Forecast', 'Recorded retirement projects by year.', 'half'),
                'promotion_increment' => $this->definition('promotion_increment', 'Career Events', 'Promotions and increments requiring planning attention.', 'half'),
                'intern_allocation' => $this->definition('intern_allocation', 'Intern Allocation', 'Aggregate active-batch and RHO status.', 'half'),
                'data_quality' => $this->definition('data_quality', 'Planning Data Quality', 'Open issues that may affect planning outputs.', 'half'),
                'profile_completion' => $this->definition('profile_completion', 'Profile Completion', 'Institution-wide handling count versus current profile count.', 'half'),
            ],
            'admin_group' => [
                'administrative_kpis' => $this->definition('administrative_kpis', 'Administrative Overview', 'Pending decisions, letters, incidents and passes.', 'wide'),
                'workflow_backlog' => $this->definition('workflow_backlog', 'Approval & Request Backlog', 'Pending governed workflow counts.', 'half'),
                'turnaround_snapshot' => $this->definition('turnaround_snapshot', 'Request Status Mix', 'Status distribution for key administrative workflows.', 'half'),
                'career_events' => $this->definition('career_events', 'Upcoming Career Events', 'Aggregate promotions, increments and retirements.', 'half'),
                'data_quality' => $this->definition('data_quality', 'Data Quality', 'Open quality issues by severity.', 'half'),
                'profile_completion' => $this->definition('profile_completion', 'Profile Completion', 'Aggregate handling count versus current profile count.', 'half'),
            ],
            'subject_officer' => [
                'my_scope' => $this->definition('my_scope', 'My Assigned Scope', 'Positions and employees within your current HR responsibility.', 'wide'),
                'profile_completion' => $this->definition('profile_completion', 'My Profile Completion', 'Handling count versus current profiles within your assigned post/subject scope.', 'half'),
                'my_intern_batches' => $this->definition('my_intern_batches', 'My Intern Batches', 'Batches formally assigned to you and RHO follow-up.', 'half'),
                'my_car_passes' => $this->definition('my_car_passes', 'My Car Pass Work', 'Requests prepared by you and pending actions.', 'half'),
                'my_letters' => $this->definition('my_letters', 'My Letters & Reviews', 'Letters you created or received for review.', 'half'),
                'my_due_actions' => $this->definition('my_due_actions', 'My Action Centre', 'Employee events and the administrative follow-up already started for them.', 'wide'),
            ],
            'unit_manager' => [
                'unit_scope' => $this->definition('unit_scope', 'Unit Scope', 'Units available to your decision-support role.', 'wide'),
                'unit_workforce' => $this->definition('unit_workforce', 'Unit Workforce', 'Aggregate active employee distribution by authorised unit.', 'half'),
                'unit_events' => $this->definition('unit_events', 'Upcoming Unit Events', 'Aggregate retirement and increment events.', 'half'),
                'unit_quality' => $this->definition('unit_quality', 'Unit Data Quality', 'Data-quality findings affecting employees in your authorised units.', 'half'),
            ],
            default => [],
        };

        return array_merge($catalog, $common);
    }

    public function defaultLayout(User $user): array
    {
        return $this->normaliseLayout(
            array_keys($this->widgetCatalog($user)),
            array_keys($this->widgetCatalog($user))
        );
    }

    public function savedLayout(User $user): array
    {
        $roleKey = $this->roleKey($user);
        $catalog = $this->widgetCatalog($user);
        $allowed = array_keys($catalog);

        $savedRecord = UserDashboardLayout::query()
            ->where('user_id', $user->id)
            ->where('role_key', $roleKey)
            ->first();

        $saved = $savedRecord?->layout;

        if (! is_array($saved)) {
            return $this->defaultLayout($user);
        }

        $sanitised = array_values(array_unique(array_filter(
            $saved,
            fn ($key): bool => is_string($key) && in_array($key, $allowed, true)
        )));

        return $sanitised !== []
            ? $this->normaliseLayout($sanitised, $allowed)
            : $this->defaultLayout($user);
    }

    public function saveLayout(User $user, array $layout): array
    {
        $roleKey = $this->roleKey($user);
        $allowed = array_keys($this->widgetCatalog($user));
        $sanitised = array_values(array_unique(array_filter(
            $layout,
            fn ($key): bool => is_string($key) && in_array($key, $allowed, true)
        )));

        $sanitised = $this->normaliseLayout($sanitised, $allowed);

        UserDashboardLayout::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'role_key' => $roleKey,
            ],
            [
                'layout' => $sanitised,
            ]
        );

        return $sanitised;
    }

    private function normaliseLayout(array $layout, array $allowed): array
    {
        $layout = array_values(array_unique(array_filter(
            $layout,
            fn ($key): bool => is_string($key) && in_array($key, $allowed, true)
        )));

        if (! in_array('quick_links', $layout, true)) {
            return $layout;
        }

        return array_merge(
            ['quick_links'],
            array_values(array_filter(
                $layout,
                fn (string $key): bool => $key !== 'quick_links'
            ))
        );
    }

    public function quickLinkCatalog(User $user): array
    {
        $catalog = [];

        NavItem::query()
            ->active()
            ->orderByRaw('section NULLS FIRST')
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get()
            ->filter(fn (NavItem $item): bool => $item->isVisibleTo($user))
            ->each(function (NavItem $item) use (&$catalog): void {
                $routeName = (string) $item->route_name;

                if ($routeName === '' || $routeName === 'dashboard' || ! Route::has($routeName)) {
                    return;
                }

                try {
                    $url = route($routeName, $item->route_params ?? []);
                } catch (\Throwable) {
                    return;
                }

                $key = 'nav:'.$item->id;
                $catalog[$key] = [
                    'key' => $key,
                    'label' => trim((string) preg_replace('/^[^\pL\pN]+/u', '', (string) $item->label)),
                    'section' => $item->section ?: 'General',
                    'url' => $url,
                    'open_in_new_tab' => (bool) $item->open_in_new_tab,
                ];
            });

        return $catalog;
    }

    public function defaultQuickLinks(User $user): array
    {
        return array_slice(array_keys($this->quickLinkCatalog($user)), 0, 8);
    }

    public function savedQuickLinks(User $user): array
    {
        $roleKey = $this->roleKey($user);
        $catalog = $this->quickLinkCatalog($user);
        $allowed = array_keys($catalog);

        $savedRecord = UserDashboardLayout::query()
            ->where('user_id', $user->id)
            ->where('role_key', $roleKey)
            ->first();

        $saved = $savedRecord?->quick_links;

        if (! is_array($saved)) {
            return $this->defaultQuickLinks($user);
        }

        return array_values(array_slice(array_unique(array_filter(
            $saved,
            fn ($key): bool => is_string($key) && in_array($key, $allowed, true)
        )), 0, 12));
    }

    public function saveQuickLinks(User $user, array $quickLinks): array
    {
        $roleKey = $this->roleKey($user);
        $allowed = array_keys($this->quickLinkCatalog($user));
        $sanitised = array_values(array_slice(array_unique(array_filter(
            $quickLinks,
            fn ($key): bool => is_string($key) && in_array($key, $allowed, true)
        )), 0, 12));

        $record = UserDashboardLayout::query()->firstOrNew([
            'user_id' => $user->id,
            'role_key' => $roleKey,
        ]);

        if (! $record->exists || ! is_array($record->layout)) {
            $record->layout = $this->defaultLayout($user);
        }

        $record->quick_links = $sanitised;
        $record->save();

        return $sanitised;
    }

    public function data(User $user): array
    {
        $roleKey = $this->roleKey($user);
        $base = [
            'roleKey' => $roleKey,
            'title' => $this->dashboardTitle($roleKey),
            'subtitle' => $this->dashboardSubtitle($roleKey),
            'catalog' => $this->widgetCatalog($user),
            'layout' => $this->savedLayout($user),
            'quickLinkCatalog' => $this->quickLinkCatalog($user),
            'quickLinks' => $this->savedQuickLinks($user),
            'generatedAt' => now(),
        ];

        return array_merge($base, match ($roleKey) {
            'super_admin' => $this->superAdminData(),
            'planning_officer' => $this->planningData(),
            'admin_group' => $this->adminData(),
            'subject_officer' => $this->subjectOfficerData($user),
            'unit_manager' => $this->unitManagerData($user),
            default => [],
        });
    }

    private function definition(string $key, string $title, string $description, string $size): array
    {
        return compact('key', 'title', 'description', 'size');
    }

    private function establishmentMetrics(): array
    {
        $year = (int) now()->year;
        $month = (int) now()->month;

        $approved = (int) ApprovedCarder::forYear($year)->sum('approved_amount');
        $available = (int) CarderMonthlyEntry::forPeriodWithCarryForward($year, $month)
            ->sum(fn (CarderMonthlyEntry $entry): int => (int) $entry->males + (int) $entry->females + (int) $entry->no_pay_leave
            );

        return [
            'active_employees' => Employee::query()->where('is_active', true)->count(),
            'approved' => $approved,
            'available' => $available,
            'vacancies' => max($approved - $available, 0),
        ];
    }

    private function superAdminData(): array
    {
        return [
            'workforce' => $this->establishmentMetrics(),
            'positionDistribution' => $this->positionDistribution(),
            'retirementYears' => $this->retirementYears(),
            'workflowBacklog' => $this->workflowBacklog(),
            'internStats' => $this->internStats(),
            'carPassStats' => $this->carPassStats(),
            'qualityStats' => $this->qualityStats(),
            'profileCompletion' => app(EmployeeProfileCompletionService::class)->summaryFor(auth()->user()),
            'recentAudit' => AuditLog::query()
                ->with('user:id,name')
                ->latest('created_at')
                ->limit(8)
                ->get(['id', 'user_id', 'action', 'auditable_type', 'description', 'created_at']),
        ];
    }

    private function planningData(): array
    {
        return [
            'workforce' => $this->establishmentMetrics(),
            'vacancyRows' => $this->vacancyRows(),
            'retirementYears' => $this->retirementYears(),
            'careerStats' => $this->careerStats(),
            'internStats' => $this->internStats(),
            'qualityStats' => $this->qualityStats(),
            'profileCompletion' => app(EmployeeProfileCompletionService::class)->summaryFor(auth()->user()),
        ];
    }

    private function adminData(): array
    {
        return [
            'administrative' => [
                'pending_decisions' => AdministrativeDecision::query()->whereIn('status', ['pending', 'checked', 'recommended'])->count(),
                'pending_service_letters' => ServiceLetter::query()->where('status', 'pending_approval')->where('is_active', true)->count(),
                'pending_car_passes' => CarPassRequest::query()->where('status', CarPassRequest::STATUS_PENDING)->count(),
                'open_incidents' => IncidentReport::query()->where('is_active', true)->where('status', '!=', 'closed')->count(),
            ],
            'workflowBacklog' => $this->workflowBacklog(),
            'workflowStatusMix' => $this->workflowStatusMix(),
            'careerStats' => $this->careerStats(),
            'qualityStats' => $this->qualityStats(),
            'profileCompletion' => app(EmployeeProfileCompletionService::class)->summaryFor(auth()->user()),
        ];
    }

    private function subjectOfficerData(User $user): array
    {
        $positionIds = $user->effectiveHrPositionIds()->filter()->unique()->values();
        $employeeIds = WorkforceScopeService::allocatedEmployeeIds($user);

        return [
            'myScope' => [
                'positions' => $positionIds->count(),
                'employees' => $employeeIds->count(),
                'open_quality' => DataQualityIssue::query()
                    ->whereIn('employee_id', $employeeIds)
                    ->whereNotIn('status', ['resolved', 'verified'])
                    ->count(),
            ],
            'myInternStats' => $this->internStats($user->id),
            'myCarPassStats' => [
                'draft' => CarPassRequest::query()->where('prepared_by', $user->id)->where('status', CarPassRequest::STATUS_DRAFT)->count(),
                'pending' => CarPassRequest::query()->where('prepared_by', $user->id)->where('status', CarPassRequest::STATUS_PENDING)->count(),
                'issued' => CarPassRequest::query()->where('prepared_by', $user->id)->where('status', CarPassRequest::STATUS_ISSUED)->count(),
            ],
            'myLetters' => [
                'created' => Letter::query()->where('created_by', $user->id)->where('is_active', true)->count(),
                'pending_service_letters' => ServiceLetter::query()->where('drafted_by', $user->id)->where('status', 'pending_approval')->count(),
            ],
            'profileCompletion' => app(EmployeeProfileCompletionService::class)->summaryFor($user),
            'myDueActions' => $this->subjectOfficerDueActions($employeeIds),
        ];
    }

    private function subjectOfficerDueActions(Collection $employeeIds): array
    {
        $now = now();
        $employees = Employee::query()
            ->whereIn('id', $employeeIds)
            ->where('is_active', true)
            ->get();

        $increments = EmployeeIncrement::query()
            ->whereIn('employee_id', $employeeIds)
            ->where('is_active', true)
            ->get();

        $incrementDue30 = $increments
            ->filter(fn (EmployeeIncrement $increment): bool => $increment->increment_date
                && $increment->increment_date->between(
                    $now->copy()->startOfDay(),
                    $now->copy()->addDays(30)->endOfDay(),
                ))
            ->count();

        $incrementOverdue = $increments
            ->filter(fn (EmployeeIncrement $increment): bool => $increment->increment_date
                && $increment->increment_date->isPast()
                && ! in_array($increment->workflow_status, ['granted', 'deferred', 'withheld'], true))
            ->count();

        $retiringEmployees = $employees
            ->filter(fn (Employee $employee): bool => $employee->retire_date
                && $employee->retire_date->between(
                    $now->copy()->startOfDay(),
                    $now->copy()->addMonths(12)->endOfDay(),
                ));

        $retiringEmployeeIds = $retiringEmployees->pluck('id');
        $retirementProjects = RetirementProject::query()
            ->whereIn('employee_id', $retiringEmployeeIds)
            ->get(['employee_id', 'status']);

        $employeesWithProject = $retirementProjects->pluck('employee_id')->unique();

        return [
            'increments_due_30d' => $incrementDue30,
            'increments_overdue' => $incrementOverdue,
            'employees_retiring_12m' => $retiringEmployees->count(),
            'retirement_cases_in_progress' => $retirementProjects
                ->where('status', '!=', 'completed')
                ->count(),
            'retirement_cases_not_opened' => $retiringEmployeeIds
                ->diff($employeesWithProject)
                ->count(),
        ];
    }

    private function unitManagerData(User $user): array
    {
        $unitIds = $user->decisionUnits()->pluck('units.id');
        $employeeQuery = Employee::query()
            ->where('is_active', true)
            ->whereIn('unit_id', $unitIds);
        $employeeIds = (clone $employeeQuery)->pluck('id');

        $unitCounts = (clone $employeeQuery)
            ->selectRaw('unit_id, COUNT(*) as total')
            ->groupBy('unit_id')
            ->with('unit:id,name')
            ->get()
            ->map(fn (Employee $row): array => [
                'label' => $row->unit?->name ?? 'Unknown Unit',
                'value' => (int) $row->total,
            ]);

        return [
            'unitScope' => [
                'units' => $unitIds->count(),
                'employees' => $employeeIds->count(),
            ],
            'unitDistribution' => $unitCounts,
            'unitEvents' => [
                'retirements_12m' => RetirementProject::query()->whereIn('employee_id', $employeeIds)->whereBetween('retirement_date', [now()->toDateString(), now()->addMonths(12)->toDateString()])->count(),
                'increments_60d' => EmployeeIncrement::query()->whereIn('employee_id', $employeeIds)->where('is_active', true)->whereBetween('increment_date', [now()->toDateString(), now()->addDays(60)->toDateString()])->count(),
            ],
            'unitQuality' => $this->qualityStats($employeeIds),
        ];
    }

    private function positionDistribution(): Collection
    {
        return Employee::query()
            ->where('employees.is_active', true)
            ->join('positions', 'positions.id', '=', 'employees.position_id')
            ->selectRaw('positions.title as label, COUNT(*) as value')
            ->groupBy('positions.title')
            ->orderByDesc('value')
            ->limit(8)
            ->get();
    }

    private function vacancyRows(): Collection
    {
        $year = (int) now()->year;
        $month = (int) now()->month;

        $approved = ApprovedCarder::forYear($year)
            ->get()
            ->groupBy('position_id')
            ->map(fn (Collection $rows): int => (int) $rows->sum('approved_amount'));

        $actual = CarderMonthlyEntry::forPeriodWithCarryForward($year, $month)
            ->groupBy('position_id')
            ->map(fn (Collection $rows): int => (int) ($rows->sum('males') + $rows->sum('females') + $rows->sum('no_pay_leave')));

        return Position::query()
            ->where('is_active', true)
            ->get(['id', 'title'])
            ->map(function (Position $position) use ($approved, $actual): array {
                $approvedCount = (int) $approved->get($position->id, 0);
                $actualCount = (int) $actual->get($position->id, 0);

                return [
                    'label' => $position->title,
                    'approved' => $approvedCount,
                    'actual' => $actualCount,
                    'gap' => max($approvedCount - $actualCount, 0),
                ];
            })
            ->sortByDesc('gap')
            ->take(8)
            ->values();
    }

    private function retirementYears(): Collection
    {
        $startYear = (int) now()->year;

        return collect(range($startYear, $startYear + 4))->map(function (int $year): array {
            return [
                'label' => (string) $year,
                'value' => RetirementProject::query()->whereYear('retirement_date', $year)->count(),
            ];
        });
    }

    private function workflowBacklog(): array
    {
        return [
            ['label' => 'Administrative decisions', 'value' => AdministrativeDecision::query()->whereIn('status', ['pending', 'checked', 'recommended'])->count()],
            ['label' => 'Service letters', 'value' => ServiceLetter::query()->where('status', 'pending_approval')->where('is_active', true)->count()],
            ['label' => 'Car passes', 'value' => CarPassRequest::query()->where('status', CarPassRequest::STATUS_PENDING)->count()],
            ['label' => 'Open incidents', 'value' => IncidentReport::query()->where('is_active', true)->where('status', '!=', 'closed')->count()],
            ['label' => 'Data-quality issues', 'value' => DataQualityIssue::query()->whereNotIn('status', ['resolved', 'verified'])->count()],
        ];
    }

    private function internStats(?int $subjectOfficerId = null): array
    {
        $query = InternBatch::query();

        if ($subjectOfficerId !== null) {
            $query->where('assigned_subject_officer_id', $subjectOfficerId);
        }

        $activeIds = (clone $query)->where('is_active', true)->pluck('id');
        $allIds = (clone $query)->pluck('id');

        return [
            'active_batches' => $activeIds->count(),
            'active_interns' => DB::table('interns')->whereIn('intern_batch_id', $activeIds)->where('is_active', true)->count(),
            'rho_pending' => DB::table('intern_rho_placements')->whereIn('intern_batch_id', $allIds)->where('status', 'pending')->count(),
            'rho_placed' => DB::table('intern_rho_placements')->whereIn('intern_batch_id', $allIds)->where('status', 'placed')->count(),
        ];
    }

    private function carPassStats(): array
    {
        return [
            'pending' => CarPassRequest::query()->where('status', CarPassRequest::STATUS_PENDING)->count(),
            'approved' => CarPassRequest::query()->where('status', CarPassRequest::STATUS_APPROVED)->count(),
            'issued' => CarPassRequest::query()->where('status', CarPassRequest::STATUS_ISSUED)->count(),
            'expiring_30d' => CarPassRequest::query()
                ->where('status', CarPassRequest::STATUS_ISSUED)
                ->whereBetween('valid_to', [now()->toDateString(), now()->addDays(30)->toDateString()])
                ->count(),
        ];
    }

    private function qualityStats($employeeIds = null): array
    {
        $query = DataQualityIssue::query()->whereNotIn('status', ['resolved', 'verified']);

        if ($employeeIds !== null) {
            $query->whereIn('employee_id', $employeeIds);
        }

        return [
            'open' => (clone $query)->count(),
            'high' => (clone $query)->whereIn('severity', ['high', 'critical'])->count(),
            'medium' => (clone $query)->where('severity', 'medium')->count(),
        ];
    }

    private function careerStats(): array
    {
        return [
            'promotions_pending' => EmployeePromotion::query()->whereIn('status', ['draft', 'pending', 'checked'])->count(),
            'increments_60d' => EmployeeIncrement::query()->where('is_active', true)->whereBetween('increment_date', [now()->toDateString(), now()->addDays(60)->toDateString()])->count(),
            'retirements_12m' => RetirementProject::query()->whereBetween('retirement_date', [now()->toDateString(), now()->addMonths(12)->toDateString()])->count(),
        ];
    }

    private function workflowStatusMix(): array
    {
        return [
            'car_pass' => CarPassRequest::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status')->toArray(),
            'service_letter' => ServiceLetter::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status')->toArray(),
        ];
    }
}
