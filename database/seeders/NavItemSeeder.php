<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\NavItem;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * NavItemSeeder
 *
 * Transcribes the ENTIRE previously-hardcoded navigation from
 * layouts/app.blade.php into nav_items, preserving every visibility
 * rule exactly — including the same route appearing multiple times
 * under different sections for different audiences (e.g. "Employee
 * Profiles" appears 3 times: My Work/subject officer, Administration/
 * super admin, and Admin Group's HR-manager gate). These are
 * intentional, not duplicates to merge.
 *
 * ONE KNOWN GAP: the original "Users" nav item showed a live pending-
 * password-reset COUNT badge next to it — a dynamic feature, not a
 * permission rule. nav_items has no concept of a badge; this is handled
 * as a small, targeted special case directly in the nav-rendering Blade
 * partial (checking specifically for route_name === 'users.index'),
 * rather than over-generalizing the schema for one badge.
 *
 * Safe to re-run — updateOrCreate keyed on (section, label, route_name).
 *
 * Usage:
 *   php artisan db:seed --class=NavItemSeeder
 */
class NavItemSeeder extends Seeder
{
    public function run(): void
    {
        $officerId = User::havingRole(User::ROLE_SUPER_ADMIN)->value('id')
            ?? User::query()->value('id');

        $sortOrder = 0;
        foreach ($this->items() as [$section, $label, $routeName, $newTab, $roles, $checks]) {
            $sortOrder += 10;
            NavItem::updateOrCreate(
                ['section' => $section, 'label' => $label, 'route_name' => $routeName],
                [
                    'open_in_new_tab' => $newTab,
                    'allowed_roles'   => $roles,
                    'custom_checks'   => $checks,
                    'sort_order'      => $sortOrder,
                    'is_active'       => true,
                    'created_by'      => $officerId,
                ]
            );
        }

        $this->command?->info('Nav items seeded: ' . NavItem::count());
    }

    /** @return array<int, array{0:?string,1:string,2:string,3:bool,4:?array,5:?array}> */
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
            ['Reports', 'Unit Post Availability', 'reports.unit-breakdown', false, ['super_admin', 'admin_group'], null],
            ['Reports', '📋 Unit Allocations', 'unit-allocations.index', false, ['super_admin', 'admin_group'], null],
            ['Reports', 'Letter Reviews', 'letter-reviews.index', false, ['super_admin', 'admin_group'], null],
            ['Reports', 'Audit Log', 'audit-logs.index', false, ['super_admin', 'admin_group'], null],
            ['Carder', 'Summary', 'planning.summary', false, ['super_admin', 'planning_officer'], null],
            ['Carder', 'Approved Carder', 'approved-carders.index', false, ['super_admin', 'planning_officer'], null],
            ['Carder', '📝 Cadre Reviews', 'cadre-reviews.index', false, ['super_admin', 'planning_officer'], null],
            ['Carder', '✅ Verify Entries', 'carder-entries.pending-verification', false, ['super_admin', 'planning_officer'], null],
            ['Carder', '✏️ Pending Amendments', 'entry-amendments.index', false, ['super_admin', 'planning_officer'], null],
            ['Carder', 'Positions', 'positions.index', false, ['super_admin', 'planning_officer'], null],
            ['Carder', 'Units', 'units.index', false, ['super_admin', 'planning_officer'], null],
            ['Carder', 'Salary Scales', 'salary-scales.index', false, ['super_admin', 'planning_officer'], null],
            ['Carder', '✉️ Service Letters', 'service-letters.index', false, ['super_admin', 'planning_officer'], null],
            ['Carder', 'Vacancy Availability', 'vacancy-availability-letters.index', false, ['super_admin', 'planning_officer'], null],
            ['My Work', 'Monthly Entries', 'carder-entries.index', false, ['subject_officer'], ['canViewEmployees', 'hasAnyPositionBoundCode']],
            ['My Work', 'Employee Profiles', 'employees.index', false, ['subject_officer'], ['canViewEmployees', 'hasAnyPositionBoundCode']],
            ['My Work', '🔄 Transfer Records', 'transfer-records.index', false, ['subject_officer'], ['canViewEmployees', 'hasAnyPositionBoundCode']],
            ['My Work', '👤 Acting Appointments', 'acting-appointments.index', false, ['subject_officer'], ['canViewEmployees', 'hasAnyPositionBoundCode']],
            ['My Work', '📈 Bulk Increment Builder', 'bulk-increments.create', false, ['subject_officer'], ['canViewEmployees', 'hasAnyPositionBoundCode']],
            ['My Work', '📥 Import Summaries', 'employee-imports.index', false, ['subject_officer'], ['canViewEmployees', 'hasAnyPositionBoundCode']],
            ['My Work', '✉️ Service Letters', 'service-letters.index', false, ['subject_officer'], ['canViewEmployees', 'hasAnyPositionBoundCode']],
            ['My Work', 'Vacancy Availability', 'vacancy-availability-letters.index', false, ['subject_officer'], ['canViewEmployees', 'hasAnyPositionBoundCode']],
            ['My Work', 'Letter Sharing', 'letters.index', false, ['subject_officer'], ['canViewLetters']],
            ['Administration', 'Users', 'users.index', false, ['super_admin'], null],
            ['Administration', 'User Categories', 'categories.index', false, ['super_admin'], null],
            ['Administration', 'Subject Codes', 'subject-codes.index', false, ['super_admin'], null],
            ['Administration', '⚙️ Settings', 'admin.settings', false, ['super_admin'], null],
            ['Administration', '🔒 IP Allowlist', 'ip-allowlist.index', false, ['super_admin'], null],
            ['Administration', '📋 Export Audit', 'export-audit.index', false, ['super_admin'], null],
            ['Administration', '📝 Cadre Reviews', 'cadre-reviews.index', false, ['super_admin'], null],
            ['Administration', '👤 Acting Appointments', 'acting-appointments.index', false, ['super_admin'], null],
            ['Administration', '👤 Acting Subject Officers', 'acting-subject-officers.index', false, ['super_admin'], null],
            ['Administration', '✉️ Service Letter Templates', 'service-letter-templates.index', false, ['super_admin'], null],
            ['Administration', '💰 Acting Allowance Rules', 'position-acting-allowance-rules.index', false, ['super_admin'], null],
            ['Administration', '🏷️ Position Subcategories', 'position-subcategories.index', false, ['super_admin'], null],
            ['Administration', '🔗 Unit-Position Bindings', 'unit-position-bindings.index', false, ['super_admin'], null],
            ['Administration', '🧭 Navigation Configuration', 'nav-items.index', false, ['super_admin'], null],
            ['Administration', '📥 Import Employees', 'employee-imports.create', false, ['super_admin'], null],
            ['Administration', 'Employee Profiles', 'employees.index', false, ['super_admin'], null],
            ['Admin Group', '📊 Unit-wise Breakdown', 'executive.unit-breakdown', false, ['admin_group'], ['isExecutiveViewer']],
            ['Admin Group', '✅ Verify Entries', 'carder-entries.pending-verification', false, ['admin_group'], ['canVerifyEntries']],
            ['Admin Group', '✏️ Pending Amendments', 'entry-amendments.index', false, ['admin_group'], ['canVerifyEntries']],
            ['Admin Group', '👤 Employee Profiles', 'employees.index', false, ['admin_group'], ['isHrRecordsManager']],
            ['Admin Group', '🔄 Transfer Records', 'transfer-records.index', false, ['admin_group'], ['isHrRecordsManager']],
            ['Admin Group', '👤 Acting Appointments', 'acting-appointments.index', false, ['admin_group'], ['isHrRecordsManager']],
            ['Admin Group', '✉️ Service Letters', 'service-letters.index', false, ['admin_group'], null],
            ['Admin Group', 'Vacancy Availability', 'vacancy-availability-letters.index', false, ['admin_group'], null],
            ['Admin Group', '✍️ My E-Signature', 'e-signatures.edit', false, ['admin_group'], null],
        ];
    }
}
