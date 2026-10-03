<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // Existing installations already have nav_items populated, so changing
        // NavItemSeeder alone would not surface the new menu. Retire the old
        // scattered entries first and create one predictable document section.
        DB::table('nav_items')
            ->whereIn('route_name', [
                'service-letters.index',
                'service-letters.create',
                'service-letter-templates.index',
                'service-letter-letterheads.index',
                'e-signatures.edit',
            ])
            ->where(function ($query): void {
                $query->whereNull('section')
                    ->orWhere('section', '<>', 'Letters & Documents');
            })
            ->update([
                'is_active' => false,
                'updated_at' => $now,
            ]);

        $this->upsertNavItem(
            'Letters & Documents',
            '✉️ Service & Official Letter Builder',
            'service-letters.index',
            ['subject_officer', 'planning_officer', 'admin_group', 'super_admin'],
            10,
            $now
        );

        $this->upsertNavItem(
            'Letters & Documents',
            '✍️ Create Service / Official Letter',
            'service-letters.create',
            ['subject_officer', 'planning_officer', 'super_admin'],
            15,
            $now
        );

        $this->upsertNavItem(
            'Letters & Documents',
            '🧩 Letter Templates',
            'service-letter-templates.index',
            ['super_admin'],
            20,
            $now
        );

        $this->upsertNavItem(
            'Letters & Documents',
            '🏛 Letterheads',
            'service-letter-letterheads.index',
            ['super_admin'],
            30,
            $now
        );

        $this->upsertNavItem(
            'Letters & Documents',
            '✍️ My E-Signature',
            'e-signatures.edit',
            ['admin_group'],
            40,
            $now
        );
    }

    public function down(): void
    {
        DB::table('nav_items')
            ->where('section', 'Letters & Documents')
            ->whereIn('route_name', [
                'service-letters.index',
                'service-letters.create',
                'service-letter-templates.index',
                'service-letter-letterheads.index',
                'e-signatures.edit',
            ])
            ->delete();

        DB::table('nav_items')
            ->whereIn('route_name', [
                'service-letters.index',
                'service-letters.create',
                'service-letter-templates.index',
                'service-letter-letterheads.index',
                'e-signatures.edit',
            ])
            ->update([
                'is_active' => true,
                'updated_at' => now(),
            ]);
    }

    private function upsertNavItem(
        string $section,
        string $label,
        string $routeName,
        array $roles,
        int $sortOrder,
        $now
    ): void {
        $existingId = DB::table('nav_items')
            ->where('section', $section)
            ->where('route_name', $routeName)
            ->value('id');

        $values = [
            'label' => $label,
            'open_in_new_tab' => false,
            'allowed_roles' => json_encode($roles, JSON_THROW_ON_ERROR),
            'custom_checks' => null,
            'sort_order' => $sortOrder,
            'is_active' => true,
            'updated_at' => $now,
        ];

        if ($existingId) {
            DB::table('nav_items')->where('id', $existingId)->update($values);

            return;
        }

        DB::table('nav_items')->insert($values + [
            'section' => $section,
            'route_name' => $routeName,
            'route_params' => null,
            'created_by' => null,
            'created_at' => $now,
        ]);
    }
};
