<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Promotes the Subject Code → Position relationship from a single FK
 * (subject_codes.position_id) to a proper many-to-many pivot so one
 * subject code can belong to multiple positions simultaneously.
 *
 * Existing single-position assignments are migrated into the pivot before
 * the old column is dropped — no data is lost.
 *
 * Downstream columns that record which SPECIFIC position an entry targets
 * (carder_monthly_entries.position_id, employees.position_id) are NOT
 * affected — those remain single-value FKs on the entry rows themselves,
 * now supplied explicitly by the officer when they submit data rather than
 * being auto-derived from a single subject-code FK.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('position_subject_code', function (Blueprint $table) {
            $table->unsignedBigInteger('position_id');
            $table->unsignedBigInteger('subject_code_id');
            $table->timestamps();

            $table->primary(['position_id', 'subject_code_id'], 'pk_position_subject_code');

            $table->foreign('position_id')
                ->references('id')->on('positions')
                ->cascadeOnDelete();

            $table->foreign('subject_code_id')
                ->references('id')->on('subject_codes')
                ->cascadeOnDelete();
        });

        // Migrate existing single-position assignments into the pivot.
        if (Schema::hasColumn('subject_codes', 'position_id')) {
            $rows = DB::table('subject_codes')
                ->whereNotNull('position_id')
                ->get(['id', 'position_id']);

            foreach ($rows as $row) {
                DB::table('position_subject_code')->insertOrIgnore([
                    'position_id'    => $row->position_id,
                    'subject_code_id' => $row->id,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }

            Schema::table('subject_codes', function (Blueprint $table) {
                $table->dropForeign(['position_id']);
                $table->dropColumn('position_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('subject_codes', function (Blueprint $table) {
            $table->unsignedBigInteger('position_id')->nullable()->after('name');
            $table->foreign('position_id')
                ->references('id')->on('positions')
                ->nullOnDelete();
        });

        // Restore the first linked position for each code.
        $rows = DB::table('position_subject_code')->get();
        $seen = [];
        foreach ($rows as $row) {
            if (! isset($seen[$row->subject_code_id])) {
                DB::table('subject_codes')
                    ->where('id', $row->subject_code_id)
                    ->update(['position_id' => $row->position_id]);
                $seen[$row->subject_code_id] = true;
            }
        }

        Schema::dropIfExists('position_subject_code');
    }
};
