<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Subject Codes now attach to a Position instead of directly to a
 * Designation (Designation → Position → Subject Code). For every existing
 * Designation, a default Position is created (same title/code) and all of
 * that designation's existing subject codes are pointed at it — so nothing
 * already entered breaks. Split that default Position into the real ones
 * afterwards via the Positions screen (e.g. split "Nursing" into "Staff
 * Nurse" / "Nursing Sister").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subject_codes', function (Blueprint $table) {
            $table->unsignedBigInteger('position_id')->nullable()->after('designation_id');
        });

        if (Schema::hasColumn('subject_codes', 'designation_id')) {
            $designationIds = DB::table('subject_codes')->whereNotNull('designation_id')->distinct()->pluck('designation_id');

            foreach ($designationIds as $designationId) {
                $designation = DB::table('designations')->find($designationId);
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

                DB::table('subject_codes')->where('designation_id', $designationId)->update(['position_id' => $positionId]);
            }

            Schema::table('subject_codes', function (Blueprint $table) {
                $table->dropForeign(['designation_id']);
                $table->dropColumn('designation_id');
            });
        }

        Schema::table('subject_codes', function (Blueprint $table) {
            $table->foreign('position_id')->references('id')->on('positions')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE subject_codes ALTER COLUMN position_id SET NOT NULL');
    }

    public function down(): void
    {
        Schema::table('subject_codes', function (Blueprint $table) {
            $table->dropForeign(['position_id']);
            $table->dropColumn('position_id');
            $table->unsignedBigInteger('designation_id')->nullable();
            $table->foreign('designation_id')->references('id')->on('designations')->nullOnDelete();
        });
    }
};
