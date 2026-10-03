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

        DB::table('nav_items')->updateOrInsert(
            ['route_name' => 'approval-requests.index'],
            [
                'section' => 'Workflow & Approvals',
                'label' => 'Requests for Approval',
                'route_params' => null,
                'open_in_new_tab' => false,
                'allowed_roles' => json_encode([
                    'super_admin',
                    'admin_group',
                    'planning_officer',
                ]),
                'custom_checks' => null,
                'sort_order' => 5,
                'is_active' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        if (Schema::hasTable('nav_items')) {
            DB::table('nav_items')->where('route_name', 'approval-requests.index')->delete();
        }
    }
};
