<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-Subject-Officer feature toggles — some officers should see the
 * Employee Profile ("cadre information") section and/or Letter Sharing,
 * others should not. Defaults to true so existing officers keep working
 * exactly as before until Super Admin deliberately restricts someone.
 * These columns are only consulted for users with role = subject_officer
 * (see EnsureFeatureAccess middleware) — harmless on other roles.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('can_view_employees')->default(true)->after('is_active');
            $table->boolean('can_view_letters')->default(true)->after('can_view_employees');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['can_view_employees', 'can_view_letters']);
        });
    }
};
