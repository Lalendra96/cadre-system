<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Keep an existing installation's database-backed navigation aligned with
     * the role-aware report access introduced for hospital planning.
     */
    public function up(): void
    {
        if (! Schema::hasTable('nav_items')) {
            return;
        }

        $planningAndManagementReports = [
            'reports.summary',
            'reports.officer-submissions',
            'reports.trend',
            'reports.yoy',
            'reports.snapshot',
            'reports.retirement-projections',
            'reports.unit-breakdown',
            'unit-allocations.index',
        ];

        DB::table('nav_items')
            ->whereIn('route_name', $planningAndManagementReports)
            ->update([
                'allowed_roles' => json_encode(
                    ['super_admin', 'admin_group', 'planning_officer'],
                    JSON_THROW_ON_ERROR
                ),
                'updated_at' => now(),
            ]);

        foreach (['planning-intelligence.index', 'planning.assessments.index'] as $routeName) {
            DB::table('nav_items')
                ->where('route_name', $routeName)
                ->update([
                    'allowed_roles' => json_encode(
                        ['super_admin', 'planning_officer', 'admin_group'],
                        JSON_THROW_ON_ERROR
                    ),
                    'custom_checks' => json_encode(
                        ['canAccessPlanningReports'],
                        JSON_THROW_ON_ERROR
                    ),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('nav_items')) {
            return;
        }

        foreach ([
            'reports.summary',
            'reports.officer-submissions',
            'reports.retirement-projections',
            'reports.unit-breakdown',
            'unit-allocations.index',
        ] as $routeName) {
            DB::table('nav_items')
                ->where('route_name', $routeName)
                ->update([
                    'allowed_roles' => json_encode(
                        ['super_admin', 'admin_group'],
                        JSON_THROW_ON_ERROR
                    ),
                    'updated_at' => now(),
                ]);
        }

        foreach (['reports.trend', 'reports.yoy', 'reports.snapshot'] as $routeName) {
            DB::table('nav_items')
                ->where('route_name', $routeName)
                ->update([
                    'allowed_roles' => json_encode(
                        ['super_admin', 'admin_group'],
                        JSON_THROW_ON_ERROR
                    ),
                    'updated_at' => now(),
                ]);
        }

        foreach (['planning-intelligence.index', 'planning.assessments.index'] as $routeName) {
            DB::table('nav_items')
                ->where('route_name', $routeName)
                ->update([
                    'allowed_roles' => json_encode(
                        ['super_admin', 'planning_officer'],
                        JSON_THROW_ON_ERROR
                    ),
                    'custom_checks' => null,
                    'updated_at' => now(),
                ]);
        }
    }
};
