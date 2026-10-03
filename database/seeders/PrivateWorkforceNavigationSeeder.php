<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PrivateWorkforceNavigationSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $managementRoles = ['super_admin', 'admin_group', 'planning_officer', 'unit_manager'];

        $items = [
            // Workforce landing + operational modules
            ['Workforce', '🏠 Workforce Dashboard', 'workforce.dashboard', $managementRoles, [], 5],
            ['Workforce', '🗓️ Roster Overview', 'roster.dashboard', $managementRoles, [], 10],
            ['Workforce', '📋 Roster Templates', 'roster.templates.index', $managementRoles, [], 11],
            ['Workforce', '📅 Roster Plans', 'roster.plans.index', $managementRoles, [], 12],
            // Approval access is workflow/category/user driven, not roster-creation-role driven.
            ['Workforce', '✅ My Roster Approvals', 'roster.approvals.index', null, ['canAccessRosterApprovals'], 13],
            ['Workforce', '🕘 Attendance', 'workforce.attendance.index', $managementRoles, [], 20],
            ['Workforce', '🌴 Leave', 'workforce.leave.index', $managementRoles, [], 30],
            ['Workforce', '⏱️ Overtime', 'workforce.overtime.index', $managementRoles, [], 40],
            ['Workforce', '📄 Contracts', 'workforce.contracts.index', $managementRoles, [], 50],
            ['Workforce', '💰 Payroll', 'workforce.payroll.index', ['super_admin', 'admin_group', 'planning_officer'], [], 60],
            ['Workforce', '🩺 Locum / Sessions', 'workforce.locum.index', $managementRoles, [], 70],
            ['Workforce', '📈 Cost Centre Analytics', 'workforce.cost-centre.index', ['super_admin', 'admin_group', 'planning_officer'], [], 80],
            ['Workforce', '👤 Employee Self-Service', 'workforce.self-service.index', null, [], 90],

            // Super Admin configuration
            ['System', '⚙️ Workforce Configuration', 'workforce.settings.index', ['super_admin'], [], 910],
            ['System', '⚙️ Roster Workflow Configuration', 'roster.settings.index', ['super_admin'], [], 920],
        ];

        foreach ($items as [$section, $label, $route, $roles, $checks, $sort]) {
            DB::table('nav_items')->updateOrInsert(
                ['route_name' => $route],
                [
                    'section' => $section,
                    'label' => $label,
                    'allowed_roles' => $roles ? json_encode($roles) : null,
                    'custom_checks' => $checks ? json_encode($checks) : json_encode([]),
                    'sort_order' => $sort,
                    'is_active' => true,
                    'open_in_new_tab' => false,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }
}
