<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Approved Carder figures now attach to a Position, not a Designation —
 * since "Designation has several positions" and the approved/monthly
 * tracking actually happens per Position (Designation total = sum of its
 * Positions' approved amounts). The old optional subject_code_id split is
 * removed since Position now IS the split unit that used to serve.
 *
 * Existing rows are pointed at the same default Position created in
 * migration 000018 for their designation, so nothing already entered is
 * lost — split them onto the real positions afterwards if needed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approved_carders', function (Blueprint $table) {
            $table->unsignedBigInteger('position_id')->nullable()->after('designation_id');
        });

        if (Schema::hasColumn('approved_carders', 'designation_id')) {
            $rows = DB::table('approved_carders')->get();

            foreach ($rows as $row) {
                $designation = DB::table('designations')->find($row->designation_id);
                if (! $designation) {
                    continue;
                }

                $positionId = DB::table('positions')->where('code', $designation->code . '-DEFAULT')->value('id');

                if (! $positionId) {
                    $positionId = DB::table('positions')->insertGetId([
                        'designation_id' => $designation->id,
                        'code' => $designation->code . '-DEFAULT',
                        'title' => $designation->title,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('approved_carders')->where('id', $row->id)->update(['position_id' => $positionId]);
            }

            Schema::table('approved_carders', function (Blueprint $table) {
                $table->dropForeign(['designation_id']);
                $table->dropColumn('designation_id');
            });
        }

        if (Schema::hasColumn('approved_carders', 'subject_code_id')) {
            // NOTE: the original 3-column unique constraint
            // (designation_id, subject_code_id, year) was already
            // cascade-dropped by Postgres when designation_id was dropped
            // above, so there's nothing left to explicitly dropUnique()
            // here. Same logic applies to the old subject_code_id foreign
            // key — dropping the column removes its own FK automatically.
            Schema::table('approved_carders', function (Blueprint $table) {
                $table->dropColumn('subject_code_id');
            });
        }

        Schema::table('approved_carders', function (Blueprint $table) {
            $table->foreign('position_id')->references('id')->on('positions')->cascadeOnDelete();
            $table->unique(['position_id', 'year'], 'uniq_approved_carder_position_year');
        });

        DB::statement('ALTER TABLE approved_carders ALTER COLUMN position_id SET NOT NULL');
    }

    public function down(): void
    {
        Schema::table('approved_carders', function (Blueprint $table) {
            $table->dropUnique('uniq_approved_carder_position_year');
            $table->dropForeign(['position_id']);
            $table->dropColumn('position_id');
            $table->unsignedBigInteger('designation_id')->nullable();
            $table->unsignedBigInteger('subject_code_id')->nullable();
            $table->foreign('designation_id')->references('id')->on('designations')->cascadeOnDelete();
            $table->foreign('subject_code_id')->references('id')->on('subject_codes')->nullOnDelete();
        });
    }
};
