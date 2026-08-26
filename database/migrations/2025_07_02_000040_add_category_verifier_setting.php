<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds the entry_verifier_category_id key to system_settings so that a
 * specific Admin Group category (e.g. "Medical Officer Planning") can be
 * designated as the entry verifier instead of a blanket role.
 *
 * Works in conjunction with entry_verifier_role = 'category'.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('system_settings')->insertOrIgnore([
            'key'         => 'entry_verifier_category_id',
            'value'       => null,
            'type'        => 'integer',
            'group'       => 'verification',
            'label'       => 'Verifier category',
            'description' => 'When "Specific User Category" is selected as the verifier role, users assigned this category can verify monthly entries.',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // Also widen the allowed values for entry_verifier_role
        DB::table('system_settings')
            ->where('key', 'entry_verifier_role')
            ->update([
                'description' => 'Who can verify monthly entries: Planning Officer role, Super Admin only, or a specific Admin Group category.',
                'updated_at'  => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('system_settings')->where('key', 'entry_verifier_category_id')->delete();
    }
};
