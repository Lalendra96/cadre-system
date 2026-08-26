<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Replaces the single users.role column with a user_roles pivot table so a
 * user account can hold multiple roles simultaneously — e.g. both
 * admin_group and planning_officer, or super_admin and subject_officer.
 *
 * The existing single-role value on each user is migrated into the new table
 * as the user's first (primary) role before the old column is dropped.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('role', 30);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['user_id', 'role'], 'uniq_user_role');
            $table->index('role', 'idx_user_roles_role');
        });

        // Migrate every existing user's single role into the pivot table.
        if (Schema::hasColumn('users', 'role')) {
            $users = DB::table('users')->whereNotNull('role')->get(['id', 'role']);

            foreach ($users as $user) {
                DB::table('user_roles')->insertOrIgnore([
                    'user_id'    => $user->id,
                    'role'       => $user->role,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 30)->default('subject_officer')->after('email');
        });

        // Restore single primary role (first one alphabetically).
        $roles = DB::table('user_roles')
            ->orderBy('user_id')->orderBy('role')
            ->get(['user_id', 'role']);

        foreach ($roles as $r) {
            DB::table('users')->where('id', $r->user_id)
                ->where('role', '')->orWhere('role', null)
                ->update(['role' => $r->role]);
        }

        Schema::dropIfExists('user_roles');
    }
};
