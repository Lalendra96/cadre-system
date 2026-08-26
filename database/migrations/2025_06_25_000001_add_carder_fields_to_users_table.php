<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds role-based access fields to the existing users table.
 *
 * role values (application-level enum, validated in App\Models\User::ROLES):
 *   - super_admin       : full system administration (users, designations, subject codes, categories)
 *   - admin_group       : Director / Deputy Director / Administrative Officer / Chief Clerk (view + charts only)
 *   - planning_officer  : manages approved carder figures, prints summaries, CRUDs designations
 *   - subject_officer   : enters monthly male/female/transfer/no-pay-leave figures against their subject_code
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 30)->default('subject_officer')->after('email');
            $table->unsignedBigInteger('category_id')->nullable()->after('role');
            $table->unsignedBigInteger('subject_code_id')->nullable()->after('category_id');
            $table->boolean('is_active')->default(true)->after('subject_code_id');

            $table->index('role', 'idx_users_role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_role');
            $table->dropColumn(['role', 'category_id', 'subject_code_id', 'is_active']);
        });
    }
};
