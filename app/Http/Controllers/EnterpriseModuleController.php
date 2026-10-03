<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnterpriseModuleController extends Controller
{
    public function cadre(Request $request)
    {
        $this->allowOperational($request);
        $year = (int) now()->year;
        $approved = $this->sum('approved_carders', 'approved_amount', ['year' => $year]);
        $activeEmployees = $this->count('employees', [['is_active', '=', true]]);
        $vacancies = max(0, $approved - $activeEmployees);
        $excess = max(0, $activeEmployees - $approved);
        $positions = $this->count('positions', [['is_active', '=', true]]);
        $units = $this->count('units', [['is_active', '=', true]]);
        $reviews = $this->count('cadre_review_proposals');

        return view('enterprise.cadre', compact('year', 'approved', 'activeEmployees', 'vacancies', 'excess', 'positions', 'units', 'reviews'));
    }

    public function serviceRecord(Request $request)
    {
        $this->allowOperational($request);
        $metrics = [
            'employees' => $this->count('employees', [['is_active', '=', true]]),
            'increments' => $this->count('employee_increments', [['is_active', '=', true]]),
            'training' => $this->count('employee_training_records'),
            'documents' => $this->count('employee_documents'),
            'corrections' => $this->count('employee_change_requests', [['status', '=', 'pending']]),
            'retirements' => $this->count('retirement_projects', [['status', '!=', 'completed']]),
        ];

        return view('enterprise.service-record', compact('metrics'));
    }

    public function intelligence(Request $request)
    {
        $this->allowOperational($request);
        $metrics = [
            'retirements_12m' => $this->dateCount('retirement_projects', 'retirement_date', now()->toDateString(), now()->addYear()->toDateString()),
            'vacancies' => $this->count('recruitment_vacancies', [['status', '!=', 'filled']]),
            'reassignments' => $this->count('hr_reassignment_cases', [['status', '!=', 'completed']]),
            'coverage_alerts' => $this->count('hr_coverage_alerts', [['is_open', '=', true]]),
            'escalations' => $this->count('hr_escalations', [['status', '=', 'open']]),
            'quality_issues' => $this->count('data_quality_issues', [['status', '!=', 'verified']]),
        ];

        return view('enterprise.intelligence', compact('metrics'));
    }

    public function workflows(Request $request)
    {
        $this->allowOperational($request);
        $queues = [
            ['label' => 'Promotions', 'count' => $this->count('employee_promotions', [['status', '!=', 'approved']]), 'route' => 'hr-intelligence.index'],
            ['label' => 'Employee corrections', 'count' => $this->count('employee_change_requests', [['status', '=', 'pending']]), 'route' => 'data-quality.index'],
            ['label' => 'Acting appointments', 'count' => $this->count('acting_appointments', [['is_active', '=', true]]), 'route' => 'acting-appointments.index'],
            ['label' => 'Reconciliation cases', 'count' => $this->count('reconciliation_issues', [['status', '=', 'open']]), 'route' => 'workforce.reconciliation'],
            ['label' => 'Reassignment cases', 'count' => $this->count('hr_reassignment_cases', [['status', '!=', 'completed']]), 'route' => 'hr-intelligence.index'],
            ['label' => 'Retirement cases', 'count' => $this->count('retirement_projects', [['status', '!=', 'completed']]), 'route' => 'retirement-projects.index'],
        ];

        $promotions = Schema::hasTable('employee_promotions')
            ? DB::table('employee_promotions as p')
                ->join('employees as e', 'e.id', '=', 'p.employee_id')
                ->leftJoin('position_grades as fg', 'fg.id', '=', 'p.from_grade_id')
                ->join('position_grades as tg', 'tg.id', '=', 'p.to_grade_id')
                ->select('p.*', 'fg.name as from_grade_name', 'tg.name as to_grade_name')
                ->where('p.status', '!=', 'approved')->orderByDesc('p.created_at')->limit(50)->get()
            : collect();
        $promotions = PersonnelDisplayService::hydrateEmployeeRows($promotions);
        $employees = Schema::hasTable('employees') ? \App\Models\Employee::query()->where('is_active', true)->orderBy('id')->limit(2000)->get(['id', 'name', 'pay_no'])->sortBy(fn ($e) => mb_strtolower((string) $e->name, 'UTF-8'))->values() : collect();
        $grades = Schema::hasTable('position_grades') ? DB::table('position_grades')->where('is_active', true)->orderBy('name')->get(['id', 'name', 'position_id']) : collect();

        return view('enterprise.workflows', compact('queues', 'promotions', 'employees', 'grades'));
    }

    public function governance(Request $request)
    {
        $this->allowGovernance($request);
        $authorities = $this->rows('approval_authorities', 'updated_at', 20);
        $delegations = $this->rows('authority_delegations', 'ends_on', 20);
        $exceptions = $this->rows('governance_exceptions', 'updated_at', 20);
        $users = Schema::hasTable('users')
            ? DB::table('users')->where('is_active', true)->orderBy('name')->get(['id', 'name', 'email'])
            : collect();

        return view('enterprise.governance', compact('authorities', 'delegations', 'exceptions', 'users'));
    }

    public function assurance(Request $request)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $database = 'connected';
        try {
            DB::connection()->getPdo();
        } catch (\Throwable) {
            $database = 'error';
        }
        $scheduler = Schema::hasTable('hr_intelligence_status') ? DB::table('hr_intelligence_status')->where('id', 1)->value('last_completed_at') : null;
        $failedJobs = $this->count('failed_jobs');
        $mfaEnabled = $this->count('users', [['mfa_enabled', '=', true], ['is_active', '=', true]]);
        $activeUsers = $this->count('users', [['is_active', '=', true]]);
        $accessReviews = $this->rows('access_reviews', 'created_at', 12);
        $backups = $this->rows('backup_assurance_records', 'backup_at', 12);
        $integrations = $this->rows('integration_health_checks', 'updated_at', 20);

        return view('enterprise.assurance', compact('database', 'scheduler', 'failedJobs', 'mfaEnabled', 'activeUsers', 'accessReviews', 'backups', 'integrations'));
    }

    public function storeAuthority(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $data = $request->validate([
            'process_key' => ['required', 'string', 'max:80'], 'process_label' => ['required', 'string', 'max:160'],
            'authority_source' => ['nullable', 'string', 'max:220'], 'reference_no' => ['nullable', 'string', 'max:100'],
            'preparer_role' => ['nullable', 'string', 'max:80'], 'checker_role' => ['nullable', 'string', 'max:80'],
            'approver_role' => ['required', 'string', 'max:80'], 'effective_from' => ['nullable', 'date'], 'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ]);
        $this->ensure('approval_authorities');
        DB::table('approval_authorities')->insert($data + ['is_active' => true, 'created_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('success', 'Approval authority registered.');
    }

    public function storeDelegation(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $data = $request->validate([
            'from_user_id' => ['required', 'integer', 'exists:users,id'], 'to_user_id' => ['required', 'integer', 'different:from_user_id', 'exists:users,id'],
            'scope' => ['required', 'string', 'max:180'], 'reference_no' => ['nullable', 'string', 'max:100'], 'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'], 'conditions' => ['nullable', 'string', 'max:2000'],
        ]);
        $this->ensure('authority_delegations');
        DB::table('authority_delegations')->insert($data + ['is_active' => true, 'created_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('success', 'Delegation recorded with effective dates.');
    }

    public function storeException(Request $request): RedirectResponse
    {
        $this->allowGovernance($request);
        $data = $request->validate([
            'area' => ['required', 'string', 'max:100'], 'title' => ['required', 'string', 'max:180'], 'reason' => ['required', 'string', 'max:4000'],
            'mitigation' => ['nullable', 'string', 'max:4000'], 'review_due_on' => ['nullable', 'date'],
        ]);
        $this->ensure('governance_exceptions');
        DB::table('governance_exceptions')->insert($data + ['status' => 'open', 'owner_user_id' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('success', 'Governance exception opened for review.');
    }

    public function storeAccessReview(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $data = $request->validate([
            'review_period' => ['required', 'string', 'max:40'], 'scope' => ['required', 'string', 'max:160'], 'status' => ['required', 'in:planned,in_progress,completed'],
            'due_on' => ['nullable', 'date'], 'accounts_reviewed' => ['nullable', 'integer', 'min:0'], 'exceptions_found' => ['nullable', 'integer', 'min:0'],
            'evidence_reference' => ['nullable', 'string', 'max:2000'],
        ]);
        $this->ensure('access_reviews');
        $data['reviewed_by'] = $request->user()->id;
        $data['completed_at'] = $data['status'] === 'completed' ? now() : null;
        DB::table('access_reviews')->insert($data + ['created_at' => now(), 'updated_at' => now()]);

        return back()->with('success', 'Access review evidence recorded.');
    }

    public function storeBackup(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $data = $request->validate([
            'backup_type' => ['required', 'string', 'max:40'], 'backup_at' => ['required', 'date'], 'status' => ['required', 'in:successful,failed,partial'],
            'storage_reference' => ['nullable', 'string', 'max:255'], 'restore_tested_at' => ['nullable', 'date'], 'restore_result' => ['nullable', 'in:successful,failed,partial,not_tested'],
            'restore_evidence' => ['nullable', 'string', 'max:4000'],
        ]);
        $this->ensure('backup_assurance_records');
        DB::table('backup_assurance_records')->insert($data + ['recorded_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('success', 'Backup/restore assurance evidence recorded.');
    }

    public function storeIntegration(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'], 'integration_type' => ['nullable', 'string', 'max:80'], 'endpoint_label' => ['nullable', 'string', 'max:180'],
            'status' => ['required', 'in:healthy,degraded,down,unknown'], 'last_checked_at' => ['nullable', 'date'], 'last_success_at' => ['nullable', 'date'],
            'certificate_expires_on' => ['nullable', 'date'], 'notes' => ['nullable', 'string', 'max:4000'],
        ]);
        $this->ensure('integration_health_checks');
        DB::table('integration_health_checks')->insert($data + ['updated_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('success', 'Integration health record added.');
    }

    private function allowOperational(Request $request): void
    {
        abort_unless($request->user()->isSuperAdmin() || $request->user()->hasAnyRole(['planning_officer', 'admin_group', 'subject_officer']), 403);
    }

    private function allowGovernance(Request $request): void
    {
        abort_unless($request->user()->isSuperAdmin() || $request->user()->hasAnyRole(['planning_officer', 'admin_group']), 403);
    }

    private function ensure(string $table): void
    {
        abort_unless(Schema::hasTable($table), 503, 'Run php artisan migrate to enable this module.');
    }

    private function count(string $table, array $where = []): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }
        $q = DB::table($table);
        foreach ($where as [$c,$op,$v]) {
            $q->where($c, $op, $v);
        }

        return (int) $q->count();
    }

    private function sum(string $table, string $column, array $where = []): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }
        $q = DB::table($table);
        foreach ($where as $c => $v) {
            $q->where($c, $v);
        }

        return (int) $q->sum($column);
    }

    private function dateCount(string $table, string $column, string $from, string $to): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        return (int) DB::table($table)->whereBetween($column, [$from, $to])->count();
    }

    private function rows(string $table, string $order, int $limit)
    {
        if (! Schema::hasTable($table)) {
            return collect();
        }

        return DB::table($table)->orderByDesc($order)->limit($limit)->get();
    }
}
