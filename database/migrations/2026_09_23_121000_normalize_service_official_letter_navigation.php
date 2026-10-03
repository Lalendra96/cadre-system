<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('nav_items')
            ->where('route_name', 'service-letters.index')
            ->update([
                'section' => 'Letters & Documents',
                'label' => '✉️ Service & Official Letter Builder',
                'allowed_roles' => json_encode(
                    ['subject_officer', 'planning_officer', 'admin_group', 'super_admin'],
                    JSON_THROW_ON_ERROR
                ),
                'is_active' => true,
                'updated_at' => $now,
            ]);

        DB::table('nav_items')
            ->where('route_name', 'service-letters.create')
            ->update([
                'section' => 'Letters & Documents',
                'label' => '✍️ Create Service / Official Letter',
                'allowed_roles' => json_encode(
                    ['subject_officer', 'planning_officer', 'super_admin'],
                    JSON_THROW_ON_ERROR
                ),
                'is_active' => true,
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        // Wording normalization is intentionally not reverted. Restoring the
        // legacy labels would reintroduce inconsistent user-facing navigation.
    }
};
