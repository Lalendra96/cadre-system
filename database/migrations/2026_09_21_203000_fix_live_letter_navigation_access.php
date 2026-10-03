<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // The route was already present, but translated navigation labels could
        // still render it as the legacy "Service Letters" name. Normalize the
        // persisted item and add an explicit drafting shortcut for drafter roles.
        DB::table('nav_items')
            ->where('section', 'Letters & Documents')
            ->where('route_name', 'service-letters.index')
            ->update([
                'label' => '✉️ Service & Official Letter Builder',
                'allowed_roles' => json_encode(
                    ['subject_officer', 'planning_officer', 'admin_group', 'super_admin'],
                    JSON_THROW_ON_ERROR
                ),
                'custom_checks' => null,
                'is_active' => true,
                'updated_at' => $now,
            ]);

        $existingCreateId = DB::table('nav_items')
            ->where('section', 'Letters & Documents')
            ->where('route_name', 'service-letters.create')
            ->value('id');

        $values = [
            'label' => '✍️ Create Service / Official Letter',
            'open_in_new_tab' => false,
            'allowed_roles' => json_encode(
                ['subject_officer', 'planning_officer', 'super_admin'],
                JSON_THROW_ON_ERROR
            ),
            'custom_checks' => null,
            'sort_order' => 15,
            'is_active' => true,
            'updated_at' => $now,
        ];

        if ($existingCreateId) {
            DB::table('nav_items')->where('id', $existingCreateId)->update($values);
        } else {
            DB::table('nav_items')->insert($values + [
                'section' => 'Letters & Documents',
                'route_name' => 'service-letters.create',
                'route_params' => null,
                'created_by' => null,
                'created_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('nav_items')
            ->where('section', 'Letters & Documents')
            ->where('route_name', 'service-letters.create')
            ->delete();
    }
};
