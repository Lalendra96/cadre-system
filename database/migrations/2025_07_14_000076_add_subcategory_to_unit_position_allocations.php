<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds position_subcategory_id to unit_position_allocations so a single
 * Unit × Position × Year "cell" can be broken down further into
 * subcategory rows (e.g. Medical Consultant → PGIM Trainee) alongside —
 * not instead of — the position's own main-line figure.
 *
 * WHY TWO PARTIAL UNIQUE INDEXES INSTEAD OF ONE PLAIN UNIQUE CONSTRAINT:
 * The original unique constraint was (unit_id, position_id, year). Simply
 * adding a nullable position_subcategory_id column to that same
 * constraint would NOT behave as needed — SQL treats every NULL as
 * distinct from every other NULL, so a plain unique constraint on
 * (unit_id, position_id, position_subcategory_id, year) would silently
 * allow multiple "main line" rows (position_subcategory_id IS NULL) for
 * the same unit+position+year, which breaks the one-row-per-cell
 * invariant the rest of this system relies on.
 *
 * Instead: drop the old constraint and add two PostgreSQL partial unique
 * indexes —
 *   1. One row per (unit, position, year) WHERE position_subcategory_id IS NULL
 *      — the position's own main-line figure, same as before.
 *   2. One row per (unit, position, subcategory, year) WHERE
 *      position_subcategory_id IS NOT NULL — one row per specific
 *      subcategory breakdown.
 * This is PostgreSQL-specific syntax (this system requires PostgreSQL —
 * see INSTALLATION.md), not portable to MySQL without a different approach.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unit_position_allocations', function (Blueprint $table) {
            if (! Schema::hasColumn('unit_position_allocations', 'position_subcategory_id')) {
                $table->unsignedBigInteger('position_subcategory_id')->nullable()->after('position_id');
                $table->foreign('position_subcategory_id')
                    ->references('id')->on('position_subcategories')
                    ->nullOnDelete();
            }
        });

        Schema::table('unit_position_allocations', function (Blueprint $table) {
            $table->dropUnique('uniq_upa_unit_pos_year');
        });

        DB::statement('
            CREATE UNIQUE INDEX uniq_upa_main_line
            ON unit_position_allocations (unit_id, position_id, year)
            WHERE position_subcategory_id IS NULL
        ');

        DB::statement('
            CREATE UNIQUE INDEX uniq_upa_subcategory_line
            ON unit_position_allocations (unit_id, position_id, position_subcategory_id, year)
            WHERE position_subcategory_id IS NOT NULL
        ');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS uniq_upa_main_line');
        DB::statement('DROP INDEX IF EXISTS uniq_upa_subcategory_line');

        Schema::table('unit_position_allocations', function (Blueprint $table) {
            $table->unique(['unit_id', 'position_id', 'year'], 'uniq_upa_unit_pos_year');
        });

        Schema::table('unit_position_allocations', function (Blueprint $table) {
            if (Schema::hasColumn('unit_position_allocations', 'position_subcategory_id')) {
                $table->dropForeign(['position_subcategory_id']);
                $table->dropColumn('position_subcategory_id');
            }
        });
    }
};
