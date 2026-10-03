<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('nav_items')) {
            return;
        }

        $items = [
            ['Cadre & Establishment', 'enterprise.cadre', ['super_admin', 'planning_officer', 'admin_group', 'subject_officer'], 10],
            ['Employee Service Record', 'enterprise.service-record', ['super_admin', 'planning_officer', 'admin_group', 'subject_officer'], 20],
            ['Workforce Intelligence', 'enterprise.intelligence', ['super_admin', 'planning_officer', 'admin_group', 'subject_officer'], 30],
            ['Workflow & Case Management', 'enterprise.workflows', ['super_admin', 'planning_officer', 'admin_group', 'subject_officer'], 40],
            ['Governance & Compliance', 'enterprise.governance', ['super_admin', 'planning_officer', 'admin_group'], 50],
            ['System Administration & Assurance', 'enterprise.assurance', ['super_admin'], 60],
        ];

        foreach ($items as [$label, $route, $roles, $sort]) {
            DB::table('nav_items')->updateOrInsert(
                ['route_name' => $route],
                [
                    'section' => 'Enterprise Control',
                    'label' => $label,
                    'route_params' => null,
                    'open_in_new_tab' => false,
                    'allowed_roles' => json_encode($roles),
                    'custom_checks' => null,
                    'sort_order' => $sort,
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('nav_items')) {
            return;
        }
        DB::table('nav_items')->whereIn('route_name', [
            'enterprise.cadre', 'enterprise.service-record', 'enterprise.intelligence',
            'enterprise.workflows', 'enterprise.governance', 'enterprise.assurance',
        ])->delete();
    }
};
