<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migrates the verifier-category setting from a single integer ID to a
 * JSON array of category IDs so multiple categories (e.g. Chief Clerk AND
 * Medical Officer Planning) can simultaneously hold verifier privileges.
 *
 * Also adds predefined category-name values so the authorizer can resolve
 * them without requiring a separate lookup for the most common hospital roles.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Migrate single ID value → JSON array if already set
        $old = DB::table('system_settings')
            ->where('key', 'entry_verifier_category_id')
            ->first();

        $existingIds = [];
        if ($old && $old->value) {
            $existingIds = [(int) $old->value];
        }

        // Remove the singular key (replaced by plural)
        DB::table('system_settings')->where('key', 'entry_verifier_category_id')->delete();

        // Insert the new plural key
        DB::table('system_settings')->insertOrIgnore([
            'key'         => 'entry_verifier_category_ids',
            'value'       => json_encode($existingIds),
            'type'        => 'json',
            'group'       => 'verification',
            'label'       => 'Verifier categories',
            'description' => 'JSON array of UserCategory IDs whose Admin Group members may verify entries. Used when entry_verifier_role = "category".',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // Update the role description
        DB::table('system_settings')
            ->where('key', 'entry_verifier_role')
            ->update([
                'description' => 'planning_officer | super_admin | category. When "category", any Admin Group user belonging to one of the selected verifier categories may verify.',
                'updated_at'  => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('system_settings')->where('key', 'entry_verifier_category_ids')->delete();
        DB::table('system_settings')->insertOrIgnore([
            'key' => 'entry_verifier_category_id', 'value' => null,
            'type' => 'integer', 'group' => 'verification',
            'label' => 'Verifier category', 'description' => 'Rolled back.',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
};
