<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('nav_items')->where('route_name', 'employee-profile-completion.index')->exists();

        if (! $exists) {
            DB::table('nav_items')->insert([
                'section' => 'Workforce',
                'label' => '📊 Profile Completion Tracking',
                'route_name' => 'employee-profile-completion.index',
                'route_params' => null,
                'open_in_new_tab' => false,
                'allowed_roles' => json_encode(['super_admin', 'planning_officer', 'admin_group', 'subject_officer']),
                'custom_checks' => null,
                'sort_order' => 245,
                'is_active' => true,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('nav_items')->where('route_name', 'employee-profile-completion.index')->delete();
    }
};
