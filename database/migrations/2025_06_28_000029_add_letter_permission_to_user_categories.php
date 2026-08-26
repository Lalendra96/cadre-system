<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Adds two columns to user_categories:
 *
 *   can_receive_letters  — when true, Admin Group users assigned this category
 *                          appear in the Letter Sharing recipient picker. This
 *                          replaces the old hardcoded LETTER_RECIPIENT_CATEGORIES
 *                          array in the User model so Super Admin can configure
 *                          the eligible pool without a code deploy.
 *
 *   sort_order           — controls display order in pickers and the category
 *                          index (lower = first). Defaults to 0 (natural order).
 *
 * Also bootstraps the new official category taxonomy:
 *   Deputy Director General · Director · Deputy Director ·
 *   Administrative Officer / Hospital Secretary · Chief Clerk ·
 *   Medical Officer Planning · Chief Accountant
 *
 * Existing categories are matched by name and updated in-place; new ones are
 * inserted. The old hardcoded list entries (Director, Deputy Director,
 * Administrative Officer, Chief Accountant) are migrated with
 * can_receive_letters = true automatically.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_categories', function (Blueprint $table) {
            $table->boolean('can_receive_letters')->default(false)->after('is_active');
            $table->unsignedSmallInteger('sort_order')->default(0)->after('can_receive_letters');
        });

        // Migrate existing categories: grant letter-recipient flag to the
        // names that were previously hardcoded in User::LETTER_RECIPIENT_CATEGORIES.
        $legacyRecipients = ['Director', 'Deputy Director', 'Administrative Officer', 'Chief Accountant'];

        DB::table('user_categories')
            ->whereIn('name', $legacyRecipients)
            ->update(['can_receive_letters' => true]);

        // Rename "Administrative Officer" to the new official title if present.
        DB::table('user_categories')
            ->where('name', 'Administrative Officer')
            ->update(['name' => 'Administrative Officer / Hospital Secretary']);

        // Upsert the full taxonomy with canonical sort order.
        $taxonomy = [
            ['name' => 'Deputy Director General',                'can_receive_letters' => true,  'sort_order' => 10],
            ['name' => 'Director',                               'can_receive_letters' => true,  'sort_order' => 20],
            ['name' => 'Deputy Director',                        'can_receive_letters' => true,  'sort_order' => 30],
            ['name' => 'Administrative Officer / Hospital Secretary', 'can_receive_letters' => true,  'sort_order' => 40],
            ['name' => 'Chief Clerk',                            'can_receive_letters' => false, 'sort_order' => 50],
            ['name' => 'Medical Officer Planning',               'can_receive_letters' => true,  'sort_order' => 60],
            ['name' => 'Chief Accountant',                       'can_receive_letters' => true,  'sort_order' => 70],
        ];

        foreach ($taxonomy as $row) {
            $exists = DB::table('user_categories')->where('name', $row['name'])->first();

            if ($exists) {
                DB::table('user_categories')->where('id', $exists->id)->update([
                    'can_receive_letters' => $row['can_receive_letters'],
                    'sort_order'          => $row['sort_order'],
                    'is_active'           => true,
                ]);
            } else {
                DB::table('user_categories')->insert($row + [
                    'is_active'   => true,
                    'description' => null,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('user_categories', function (Blueprint $table) {
            $table->dropColumn(['can_receive_letters', 'sort_order']);
        });
    }
};
