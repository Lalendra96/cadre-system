<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('nav_items')
            ->where('route_name', 'workforce.career-events-timeline')
            ->exists();

        if ($exists) {
            DB::table('nav_items')
                ->where('route_name', 'workforce.career-events-timeline')
                ->update([
                    'section' => 'Workforce',
                    'label' => '🗓️ Career Events Timeline',
                    'allowed_roles' => json_encode([
                        'super_admin',
                        'planning_officer',
                        'admin_group',
                        'subject_officer',
                    ]),
                    'custom_checks' => null,
                    'is_active' => true,
                    'updated_at' => now(),
                ]);

            return;
        }

        $nextSortOrder = ((int) DB::table('nav_items')
            ->where('section', 'Workforce')
            ->max('sort_order')) + 10;

        DB::table('nav_items')->insert([
            'section' => 'Workforce',
            'label' => '🗓️ Career Events Timeline',
            'route_name' => 'workforce.career-events-timeline',
            'route_params' => null,
            'open_in_new_tab' => false,
            'allowed_roles' => json_encode([
                'super_admin',
                'planning_officer',
                'admin_group',
                'subject_officer',
            ]),
            'custom_checks' => null,
            'sort_order' => $nextSortOrder,
            'is_active' => true,
            'created_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('nav_items')
            ->where('route_name', 'workforce.career-events-timeline')
            ->delete();
    }
};
