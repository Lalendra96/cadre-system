<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds force_password_change flag to users.
 * When a Super Admin sets a temporary password for a user this column is
 * set to true. The EnsurePasswordNotExpired middleware then intercepts every
 * authenticated request and redirects the user to /change-password until
 * they pick a permanent password of their own.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('force_password_change')->default(false)->after('can_view_letters');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('force_password_change');
        });
    }
};
