<?php

namespace Database\Seeders;

use App\Models\UserCategory;
use Illuminate\Database\Seeder;

/**
 * UserCategorySeeder
 * Seeds the official Admin Group category taxonomy for the hospital.
 *
 * Each category specifies:
 *   can_receive_letters — whether users in this category appear in the
 *                         Letter Sharing recipient picker.
 *   sort_order          — display order in pickers and the categories index.
 *
 * Deputy Director (I to N): one category entry; multiple people can be
 * assigned the same category (Deputy Director I, II, … N are distinguished
 * by their user name, not by separate categories).
 *
 * Safe to re-run — uses updateOrCreate keyed on 'name'.
 *
 * Usage:
 *   php artisan db:seed --class=UserCategorySeeder
 */
class UserCategorySeeder extends Seeder
{
    private function taxonomy(): array
    {
        return [
            [
                'name'                => 'Deputy Director General',
                'description'         => 'Deputy Director General of the institution',
                'can_receive_letters' => true,
                'sort_order'          => 10,
            ],
            [
                'name'                => 'Director',
                'description'         => 'Director of the institution',
                'can_receive_letters' => true,
                'sort_order'          => 20,
            ],
            [
                'name'                => 'Deputy Director',
                'description'         => 'Covers Deputy Director I through N (any number). Each officer assigned this category appears individually in the Letter Sharing recipient picker — the sender selects specific individuals, not the whole group.',
                'can_receive_letters' => true,
                'sort_order'          => 30,
            ],
            [
                'name'                => 'Administrative Officer / Hospital Secretary',
                'description'         => 'Administrative Officer or Hospital Secretary',
                'can_receive_letters' => true,
                'sort_order'          => 40,
            ],
            [
                'name'                => 'Chief Clerk',
                'description'         => 'Chief Clerk — administrative support; not a letter recipient by default',
                'can_receive_letters' => false,
                'sort_order'          => 50,
            ],
            [
                'name'                => 'Medical Officer Planning',
                'description'         => 'Medical Officer responsible for institutional planning',
                'can_receive_letters' => true,
                'sort_order'          => 60,
            ],
            [
                'name'                => 'Chief Accountant',
                'description'         => 'Chief Accountant of the institution',
                'can_receive_letters' => true,
                'sort_order'          => 70,
            ],
        ];
    }

    public function run(): void
    {
        foreach ($this->taxonomy() as $row) {
            UserCategory::updateOrCreate(
                ['name' => $row['name']],
                $row + ['is_active' => true]
            );
        }

        $this->command?->info('User categories seeded: ' . count($this->taxonomy()) . ' categories.');
        $this->command?->line('');
        $this->command?->line('  Categories that can receive letters:');
        foreach (array_filter($this->taxonomy(), fn ($r) => $r['can_receive_letters']) as $r) {
            $this->command?->line("    ✓ {$r['name']}");
        }
        $this->command?->line('');
        $this->command?->line('  Categories that cannot receive letters:');
        foreach (array_filter($this->taxonomy(), fn ($r) => ! $r['can_receive_letters']) as $r) {
            $this->command?->line("    – {$r['name']}");
        }
        $this->command?->line('');
        $this->command?->line('  To change letter permissions: Admin → User Categories → Edit.');
    }
}
