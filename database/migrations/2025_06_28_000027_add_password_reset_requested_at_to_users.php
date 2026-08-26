<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks when a user has submitted a "Forgot Password" request.
 * Because this is an internal LAN system with no outbound email dependency,
 * the request is stored in the database and surfaced to the Super Admin on
 * the Users screen — the admin then uses the existing temp-password flow to
 * reset the account. Cleared automatically when the admin sets a new temp
 * password for the user.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('password_reset_requested_at')->nullable()->after('force_password_change');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('password_reset_requested_at');
        });
    }
};
