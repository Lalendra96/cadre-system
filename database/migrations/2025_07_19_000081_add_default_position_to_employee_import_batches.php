<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets an import batch specify a single Position for the ENTIRE file,
 * as an alternative to mapping a "Position" column per row. Built for
 * exactly the position-specific import files this project generates
 * (one file per position, e.g. "Midwife.xlsx" — every row in it is a
 * Midwife, so there's no need for a Position column at all).
 *
 * When set, EmployeeImportProcessor uses this for every row and no
 * longer requires a Position column mapping. When left null, the
 * existing per-row "Position is required" behaviour is unchanged —
 * this is purely additive, not a replacement for mixed-position files.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_import_batches', function (Blueprint $table) {
            $table->unsignedBigInteger('default_position_id')->nullable()->after('subject_code_id');
            $table->foreign('default_position_id')->references('id')->on('positions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employee_import_batches', function (Blueprint $table) {
            $table->dropForeign(['default_position_id']);
            $table->dropColumn('default_position_id');
        });
    }
};
