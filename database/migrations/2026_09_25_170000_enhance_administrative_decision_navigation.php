<?php

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
            [
                'section' => 'Workforce',
                'label' => '🧠 Administrative Intelligence',
                'route_name' => 'administrative-intelligence.index',
                'allowed_roles' => ['super_admin', 'admin_group'],
                'custom_checks' => null,
                'sort_order' => 165,
            ],
            [
                'section' => 'Workforce',
                'label' => '📈 Enterprise Workforce Intelligence',
                'route_name' => 'enterprise.intelligence',
                'allowed_roles' => ['super_admin', 'planning_officer', 'admin_group', 'subject_officer'],
                'custom_checks' => null,
                'sort_order' => 166,
            ],
            [
                'section' => 'Governance Intelligence',
                'label' => '⚖ Governance Control Centre',
                'route_name' => 'governance-control.dashboard',
                'allowed_roles' => ['super_admin', 'planning_officer', 'admin_group'],
                'custom_checks' => null,
                'sort_order' => 431,
            ],
            [
                'section' => 'Reports',
                'label' => '📑 Official Reports & Signed Snapshots',
                'route_name' => 'official-reports.index',
                'allowed_roles' => ['super_admin', 'planning_officer', 'admin_group'],
                'custom_checks' => null,
                'sort_order' => 285,
            ],
            [
                'section' => 'Administration',
                'label' => '🩺 Workforce & Application Health',
                'route_name' => 'admin.workforce-health',
                'allowed_roles' => ['super_admin', 'planning_officer', 'admin_group'],
                'custom_checks' => null,
                'sort_order' => 447,
            ],
            [
                'section' => 'Administration',
                'label' => '💡 Utility Bill Administration',
                'route_name' => 'utility-bills.admin.index',
                'allowed_roles' => ['super_admin'],
                'custom_checks' => null,
                'sort_order' => 448,
            ],
        ];

        foreach ($items as $item) {
            DB::table('nav_items')->updateOrInsert(
                ['route_name' => $item['route_name']],
                [
                    'section' => $item['section'],
                    'label' => $item['label'],
                    'open_in_new_tab' => false,
                    'allowed_roles' => json_encode($item['allowed_roles']),
                    'custom_checks' => $item['custom_checks'] ? json_encode($item['custom_checks']) : null,
                    'sort_order' => $item['sort_order'],
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

        DB::table('nav_items')
            ->whereIn('route_name', [
                'enterprise.intelligence',
                'governance-control.dashboard',
                'official-reports.index',
            ])
            ->delete();

        // Restore the narrower pre-enhancement visibility for existing items.
        DB::table('nav_items')
            ->where('route_name', 'administrative-intelligence.index')
            ->update([
                'allowed_roles' => json_encode(['super_admin', 'admin_group']),
                'custom_checks' => json_encode(['isExecutiveViewer']),
                'updated_at' => now(),
            ]);

        DB::table('nav_items')
            ->where('route_name', 'admin.workforce-health')
            ->update([
                'allowed_roles' => json_encode(['super_admin']),
                'updated_at' => now(),
            ]);
    }
};
