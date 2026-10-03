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
            ['Governance & Compliance', 'Governance Control Dashboard', 'governance-control.dashboard', ['super_admin', 'planning_officer', 'admin_group'], 405],
            ['Governance & Compliance', 'Administrative Decisions', 'governance-control.decisions', ['super_admin', 'planning_officer', 'admin_group'], 406],
            ['Governance & Compliance', 'Rule Versions', 'governance-control.rules', ['super_admin', 'planning_officer', 'admin_group'], 407],
            ['Governance & Compliance', 'Regulatory Change', 'governance-control.regulatory', ['super_admin', 'planning_officer', 'admin_group'], 408],
            ['Governance & Compliance', 'Records Retention', 'governance-control.retention', ['super_admin', 'planning_officer', 'admin_group'], 409],
            ['My Work', 'Formal Handovers', 'governance-control.handovers', ['super_admin', 'planning_officer', 'admin_group', 'subject_officer'], 565],
        ];
        foreach ($items as [$section,$label,$route,$roles,$sort]) {
            DB::table('nav_items')->updateOrInsert(
                ['route_name' => $route],
                ['section' => $section, 'label' => $label, 'route_params' => null, 'open_in_new_tab' => false, 'allowed_roles' => json_encode($roles), 'custom_checks' => null, 'sort_order' => $sort, 'is_active' => true, 'created_by' => null, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('nav_items')) {
            DB::table('nav_items')->whereIn('route_name', ['governance-control.dashboard', 'governance-control.decisions', 'governance-control.rules', 'governance-control.regulatory', 'governance-control.retention', 'governance-control.handovers'])->delete();
        }
    }
};
