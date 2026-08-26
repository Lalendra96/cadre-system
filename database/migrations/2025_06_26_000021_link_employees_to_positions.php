<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Employee Profiles now carry position_id (derived from the subject code's
 * position) instead of designation_id, matching the new Designation →
 * Position → Subject Code hierarchy. Designation is still reachable for
 * display via $employee->position->designation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedBigInteger('position_id')->nullable()->after('designation_id');
        });

        DB::statement('
            UPDATE employees
            SET position_id = subject_codes.position_id
            FROM subject_codes
            WHERE employees.subject_code_id = subject_codes.id
        ');

        if (Schema::hasColumn('employees', 'designation_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropForeign(['designation_id']);
                $table->dropColumn('designation_id');
            });
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->foreign('position_id')->references('id')->on('positions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['position_id']);
            $table->dropColumn('position_id');
            $table->unsignedBigInteger('designation_id')->nullable();
            $table->foreign('designation_id')->references('id')->on('designations')->nullOnDelete();
        });
    }
};
