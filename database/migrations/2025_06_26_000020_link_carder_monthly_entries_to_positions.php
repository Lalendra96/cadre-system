<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Monthly entries now carry position_id (derived from the subject code's
 * position) instead of designation_id — multiple subject codes can share a
 * position, and their monthly entries sum to that position's total, which
 * in turn rolls up to the Designation total.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carder_monthly_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('position_id')->nullable()->after('designation_id');
        });

        // Backfill from the subject code's (already-migrated) position_id.
        DB::statement('
            UPDATE carder_monthly_entries
            SET position_id = subject_codes.position_id
            FROM subject_codes
            WHERE carder_monthly_entries.subject_code_id = subject_codes.id
        ');

        if (Schema::hasColumn('carder_monthly_entries', 'designation_id')) {
            Schema::table('carder_monthly_entries', function (Blueprint $table) {
                $table->dropForeign(['designation_id']);
                $table->dropColumn('designation_id');
            });
        }

        Schema::table('carder_monthly_entries', function (Blueprint $table) {
            $table->foreign('position_id')->references('id')->on('positions')->nullOnDelete();
            $table->index('position_id', 'idx_carder_entries_position');
        });
    }

    public function down(): void
    {
        Schema::table('carder_monthly_entries', function (Blueprint $table) {
            $table->dropForeign(['position_id']);
            $table->dropIndex('idx_carder_entries_position');
            $table->dropColumn('position_id');
            $table->unsignedBigInteger('designation_id')->nullable();
            $table->foreign('designation_id')->references('id')->on('designations')->nullOnDelete();
        });
    }
};
