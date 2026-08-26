<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds login_show_username to system_settings — Super Admin can disable
 * the Email/username field on the login page. When disabled, the login
 * form shows a User Group/Category dropdown instead, followed by a
 * dropdown of the actual accounts in that group — see LoginController
 * and auth/login.blade.php.
 *
 * Default TRUE (the existing email-based login, unchanged) — this is an
 * opt-in alternate login flow, not a replacement forced on every install.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('system_settings')->insertOrIgnore([
            'key'         => 'login_show_username',
            'value'       => 'true',
            'type'        => 'boolean',
            'group'       => 'security',
            'label'       => 'Show username/email field on login',
            'description' => 'When disabled, the login page shows a User Group/Category dropdown instead of a typed username, followed by a dropdown to pick the specific account.',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('system_settings')->where('key', 'login_show_username')->delete();
    }
};
