<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Unit becomes a REQUIRED field on Employee Profiles. Since employees may
 * already exist, this:
 *   1. Adds unit_id as nullable
 *   2. Creates/reuses an "Unassigned" unit and backfills any existing rows
 *      with it (so nothing is left in a broken state)
 *   3. Tightens the column to NOT NULL
 * Rename/reassign the "Unassigned" placeholder via Units screen afterwards.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_id')->nullable()->after('designation_id');
            $table->foreign('unit_id')->references('id')->on('units')->restrictOnDelete();
        });

        $fallbackUnitId = DB::table('units')->where('code', 'UNASSIGNED')->value('id');

        if (! $fallbackUnitId) {
            $fallbackUnitId = DB::table('units')->insertGetId([
                'code' => 'UNASSIGNED',
                'name' => 'Unassigned',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('employees')->whereNull('unit_id')->update(['unit_id' => $fallbackUnitId]);

        DB::statement('ALTER TABLE employees ALTER COLUMN unit_id SET NOT NULL');
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn('unit_id');
        });
    }
};
