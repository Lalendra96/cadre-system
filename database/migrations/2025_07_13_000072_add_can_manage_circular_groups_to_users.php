<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * can_manage_circular_groups — Super-Admin-configured, per-Subject-Officer
 * permission flag: which specific officers may create their own Position
 * Groups and dispatch (send) circulars to them. Same pattern as the
 * existing can_view_employees/can_view_letters flags — see
 * EnsureFeatureAccess middleware for how those are enforced; this one
 * is added to the same match() there as 'circular-groups'.
 *
 * Defaults to FALSE (opt-in), unlike can_view_employees/can_view_letters
 * which default to true — sending bulk email to employees is a more
 * consequential action than viewing a profile, so an explicit grant is
 * the safer default here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'can_manage_circular_groups')) {
                $table->boolean('can_manage_circular_groups')->default(false)->after('can_view_letters');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'can_manage_circular_groups')) {
                $table->dropColumn('can_manage_circular_groups');
            }
        });
    }
};
