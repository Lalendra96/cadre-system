<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\NavItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class NavItemSeeder extends Seeder
{
    public function run(): void
    {
        $officerId = User::havingRole(User::ROLE_SUPER_ADMIN)->value('id') ?? User::query()->value('id');

        $sortOrder = 0;
        foreach ($this->items() as [$section, $label, $routeName, $newTab, $roles, $checks]) {
            $sortOrder += 10;
            $label = trim((string) preg_replace('/\s*↗\s*$/u', '', $label));
            $item = NavItem::firstOrNew(['section' => $section, 'route_name' => $routeName]);
            $item->fill([
                'label' => $label,
                'open_in_new_tab' => $newTab,
                'allowed_roles' => $roles,
                'custom_checks' => $checks,
                'sort_order' => $sortOrder,
                'is_active' => true,
                'created_by' => $item->created_by ?: $officerId,
            ]);
            $item->save();
        }

        NavItem::where('section', 'My Work')->whereIn('route_name', ['increments.dashboard','bulk-increments.create','employee-imports.index'])->update(['is_active'=>false]);
        NavItem::where('section', 'Carder')->whereIn('route_name', ['planning.summary','approved-carders.index'])->update(['is_active'=>false]);

        $this->command?->info('Nav items seeded: ' . NavItem::count());
    }

    private function items(): array
    {
        return [
            [null, '📢 Memos & Circulars ↗', 'circulars.index', true, null, null],
            [null, '📤 Manage Circulars', 'circulars.manage', false, ['subject_officer', 'super_admin', 'admin_group'], null],
            [null, '👥 Position Groups', 'position-groups.index', false, null, ['canManageCircularGroups']],
            ['Reports', 'Summary & Charts', 'reports.summary', false, ['super_admin', 'admin_group'], null],
            ['Reports', 'Submission Rate', 'reports.officer-submissions', false, ['super_admin', 'admin_group'], null],
            ['Reports', 'Trend Analysis', 'reports.trend', false, ['super_admin', 'admin_group'], null],
            ['Reports', 'Year-on-Year', 'reports.yoy', false, ['super_admin', 'admin_group'], null],
            ['Reports', 'Historical Snapshot', 'reports.snapshot', false, ['super_admin', 'admin_group'], null],
            ['Reports', 'Retirement Projections', 'reports.retirement-projections', false, ['super_admin', 'admin_group'], null],
            ['Workforce', 'HR Intelligence & Reassignments', 'hr-intelligence.index', false, ['super_admin', 'planning_officer', 'admin_group'], ['canAccessHrIntelligence']],
            ['Workforce', 'HR Responsibilities', 'hr-responsibilities.index', false, ['super_admin', 'planning_officer', 'admin_group'], null],
            ['Workforce', '⚡ Action Centre', 'workforce.action-center', false, ['super_admin', 'planning_officer', 'admin_group'], null],
            ['Workforce', '🛡️ Data Quality', 'data-quality.index', false, ['super_admin', 'planning_officer', 'admin_group'], null],
            ['Workforce', '📅 Increment Management', 'increments.dashboard', false, ['super_admin', 'planning_officer', 'admin_group'], null],
            ['Workforce', '🌿 Retirement Project', 'retirement-projects.index', false, ['super_admin', 'planning_officer', 'admin_group'], null],
            ['Workforce', '🔎 Reconciliation', 'workforce.reconciliation', false, ['super_admin', 'planning_officer', 'admin_group'], null],
            ['Workforce', '📈 Workforce Forecast', 'workforce.forecast', false, ['super_admin', 'planning_officer', 'admin_group'], null],
            ['Workforce', '🧠 Administrative Intelligence', 'administrative-intelligence.index', false, ['super_admin', 'admin_group'], ['isExecutiveViewer']],
            ['Workforce', '🧠 Planning Intelligence', 'planning-intelligence.index', false, ['super_admin', 'planning_officer'], null],
            ['Workforce', '🧪 Scenario Comparison', 'workforce.scenario-comparison', false, ['super_admin', 'planning_officer', 'admin_group'], null],
            ['Administration', '🩺 Application Health', 'admin.workforce-health', false, ['super_admin'], null],
            ['Administration', '🔐 Offline OTP MFA', 'mfa.setup', false, User::ROLES, null],
            ['Workforce', '🎂 Age & Retirement Exposure', 'workforce.age-analysis', false, ['super_admin', 'planning_officer', 'admin_group'], null],
            ['Workforce', '🔄 Employee Movements', 'workforce.movements', false, ['super_admin', 'planning_officer', 'admin_group'], null],
            ['Workforce', '🕰️ Historical Register', 'workforce.as-at', false, ['super_admin', 'planning_officer', 'admin_group'], null],
            ['Workforce', '🧩 Duplicate Review', 'workforce.duplicates', false, ['super_admin', 'planning_officer'], null],
            ['Workforce', '🛠️ Bulk Corrections', 'employees.bulk-correction', false, ['super_admin', 'planning_officer'], null],
            ['Reports', 'Unit Post Availability', 'reports.unit-breakdown', false, ['super_admin', 'admin_group'], null],
            ['Reports', '📋 Unit Allocations', 'unit-allocations.index', false, ['super_admin', 'admin_group'], null],
            ['Reports', 'Letter Reviews', 'letter-reviews.index', false, ['super_admin', 'admin_group'], null],
            ['Reports', 'Audit Log', 'audit-logs.index', false, ['super_admin', 'admin_group'], null],
            ['Planning', '📊 Planning Dashboard', 'planning.summary', false, ['super_admin', 'planning_officer'], null],
            ['Planning', '📋 Approved Cadre', 'approved-carders.index', false, ['super_admin', 'planning_officer'], null],
            ['Carder', '📝 Cadre Reviews', 'cadre-reviews.index', false, ['super_admin', 'planning_officer'], null],
            ['Carder', '🩺 Intern Allocation', 'intern-batches.index', false, ['super_admin', 'planning_officer', 'subject_officer'], ['canManageInternAssignments']],
            ['Carder', '👥 Current Intern Assignments', 'current-intern-assignments.index', false, ['admin_group'], ['isExecutiveViewer']],
            ['Carder', '✅ Verify Entries', 'carder-entries.pending-verification', false, ['super_admin', 'planning_officer'], null],
            ['Carder', '✏️ Pending Amendments', 'entry-amendments.index', false, ['super_admin', 'planning_officer'], null],
            ['Carder', 'Positions', 'positions.index', false, ['super_admin', 'planning_officer'], null],
            ['Carder', 'Units', 'units.index', false, ['super_admin', 'planning_officer'], null],
            ['Carder', 'Salary Scales', 'salary-scales.index', false, ['super_admin', 'planning_officer'], null],
            ['Carder', '✉️ Service Letters', 'service-letters.index', false, ['super_admin', 'planning_officer'], null],
            ['Carder', 'Vacancy Availability', 'vacancy-availability-letters.index', false, ['super_admin', 'planning_officer'], null],
            ['My Work', '🏠 Today & My Work', 'subject-officer.workspace', false, ['subject_officer'], ['canViewEmployees', 'hasAssignedHrPositions']],
            ['My Work', '📅 My Actions', 'workforce.action-center', false, ['subject_officer'], ['canViewEmployees', 'hasAssignedHrPositions']],
            ['My Work', '🔄 My Movements', 'workforce.movements', false, ['subject_officer'], ['canViewEmployees', 'hasAssignedHrPositions']],
            ['My Work', 'Monthly Entries', 'carder-entries.index', false, ['subject_officer'], ['canViewEmployees', 'hasAnyPositionBoundCode']],
            ['My Work', '👥 My Employees', 'employees.index', false, ['subject_officer'], ['canViewEmployees', 'hasAssignedHrPositions']],
            ['My Work', '📂 Service History', 'employee-service-periods.overview', false, ['subject_officer'], ['canViewEmployees', 'hasAssignedHrPositions']],
            ['My Work', '🏛 Register Incoming Officer', 'incoming-officers.create', false, ['subject_officer'], ['canViewEmployees', 'hasAssignedHrPositions']],
            ['My Work', '🔄 Transfer Records', 'transfer-records.index', false, ['subject_officer'], ['canViewEmployees', 'hasAssignedHrPositions']],
            ['My Work', '👤 Acting Appointments', 'acting-appointments.index', false, ['subject_officer'], ['canViewEmployees', 'hasAssignedHrPositions']],
            ['My Work', '🛡️ Data Quality', 'data-quality.index', false, ['subject_officer'], ['canViewEmployees', 'hasAssignedHrPositions']],
            ['My Work', '🌿 My Retirements', 'retirement-projects.index', false, ['subject_officer'], ['canViewEmployees', 'hasAssignedHrPositions']],
            ['My Work', '✉️ Service Letters', 'service-letters.index', false, ['subject_officer'], ['canViewEmployees', 'hasAssignedHrPositions']],
            ['My Work', '✅ My Decision Requests', 'administrative-decisions.index', false, ['subject_officer'], ['canViewEmployees']],
            ['My Work', '⚠ Incident & Correction Register', 'incidents.index', false, ['subject_officer', 'planning_officer', 'admin_group', 'super_admin'], null],
            ['My Work', 'Vacancy Availability', 'vacancy-availability-letters.index', false, ['subject_officer'], ['canViewEmployees', 'hasAssignedHrPositions']],
            ['My Work', 'Letter Sharing', 'letters.index', false, ['subject_officer'], ['canViewLetters']],
            ['Administration', 'Users', 'users.index', false, ['super_admin'], null],
            ['Administration', 'User Categories', 'categories.index', false, ['super_admin'], null],
            ['Administration', 'Subject Codes', 'subject-codes.index', false, ['super_admin'], null],
            ['Administration', '⚙️ Settings', 'admin.settings', false, ['super_admin'], null],
            ['Administration', '🎛️ Feature Management', 'admin.features', false, ['super_admin'], null],
            ['Administration', '⚖ Governance & Official Authority', 'governance.index', false, ['super_admin'], null],
            ['Administration', '✅ Administrative Decision Queue', 'administrative-decisions.index', false, ['super_admin', 'admin_group'], null],
            ['Administration', '🛡️ Data Quality Rules', 'data-quality.rules', false, ['super_admin'], null],
            ['Administration', '🔒 IP Allowlist', 'ip-allowlist.index', false, ['super_admin'], null],
            ['Administration', '📋 Export Audit', 'export-audit.index', false, ['super_admin'], null],
            ['Administration', '📝 Cadre Reviews', 'cadre-reviews.index', false, ['super_admin'], null],
            ['Administration', '👤 Acting Appointments', 'acting-appointments.index', false, ['super_admin'], null],
            ['Administration', '👤 Acting Subject Officers', 'acting-subject-officers.index', false, ['super_admin'], null],
            ['Administration', '✉️ Service Letter Templates', 'service-letter-templates.index', false, ['super_admin'], null],
            ['Administration', '🏛 Service Letter Letterheads', 'service-letter-letterheads.index', false, ['super_admin'], null],
            ['Administration', '💰 Acting Allowance Rules', 'position-acting-allowance-rules.index', false, ['super_admin'], null],
            ['Administration', '🏷️ Position Subcategories', 'position-subcategories.index', false, ['super_admin'], null],
            ['Administration', '🔗 Unit-Position Bindings', 'unit-position-bindings.index', false, ['super_admin'], null],
            ['Administration', '🧭 Navigation Configuration', 'nav-items.index', false, ['super_admin'], null],
            ['Administration', '📥 Import Employees', 'employee-imports.create', false, ['super_admin'], null],
            ['Administration', 'Employee Counts', 'employee-counts.index', false, ['super_admin'], null],
            ['Admin Group', '📊 Unit-wise Breakdown', 'executive.unit-breakdown', false, ['admin_group'], ['isExecutiveViewer']],
            ['Admin Group', '✅ Verify Entries', 'carder-entries.pending-verification', false, ['admin_group'], ['canVerifyEntries']],
            ['Admin Group', '✏️ Pending Amendments', 'entry-amendments.index', false, ['admin_group'], ['canVerifyEntries']],
            ['Admin Group', '📊 Employee Counts', 'employee-counts.index', false, ['admin_group'], ['isHrRecordsManager']],
            ['Admin Group', '🔄 Transfer Records', 'transfer-records.index', false, ['admin_group'], ['isHrRecordsManager']],
            ['Admin Group', '👤 Acting Appointments', 'acting-appointments.index', false, ['admin_group'], ['isHrRecordsManager']],
            ['Admin Group', '✉️ Service Letters', 'service-letters.index', false, ['admin_group'], null],
            ['Admin Group', 'Vacancy Availability', 'vacancy-availability-letters.index', false, ['admin_group'], null],
            ['Admin Group', '✍️ My E-Signature', 'e-signatures.edit', false, ['admin_group'], null],
        ];
    }
}
