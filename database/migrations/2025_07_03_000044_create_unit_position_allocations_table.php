<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * unit_position_allocations
 * ─────────────────────────
 * Stores the Planning Officer / MO Planning's record of how many posts
 * (allocated) and how many are currently filled (actual_in_post) for
 * each Position × Unit combination per year.
 *
 * This is distinct from:
 *   - approved_carders   → hospital-wide total approved per position
 *   - carder_monthly_entries → aggregate monthly figure per subject code
 *   - employees          → individual employee profiles
 *
 * Use case: "Ward 01 should have 1 Sister, 10 Nursing Officers, 5 SKS.
 * Currently we have 1, 8, and 4." The Planning Officer types those numbers
 * directly here; the unit-breakdown report then uses them for gap analysis.
 *
 * unique constraint: (unit_id, position_id, year) — one record per cell
 * in the matrix; upsert on save so the form is idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_position_allocations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('unit_id');
            $table->unsignedBigInteger('position_id');
            $table->unsignedSmallInteger('year');

            // Planned posts for this unit/position (management decision)
            $table->unsignedSmallInteger('allocated_posts')->default(0);

            // Current actual headcount (officer's knowledge / field count)
            $table->unsignedSmallInteger('actual_in_post')->default(0);

            // Optional free-text note per cell (e.g. "2 on no-pay, 1 acting")
            $table->string('notes', 300)->nullable();

            $table->unsignedBigInteger('last_updated_by')->nullable();
            $table->timestamps();

            $table->foreign('unit_id')
                ->references('id')->on('units')
                ->cascadeOnDelete();

            $table->foreign('position_id')
                ->references('id')->on('positions')
                ->cascadeOnDelete();

            $table->foreign('last_updated_by')
                ->references('id')->on('users')
                ->nullOnDelete();

            $table->unique(['unit_id', 'position_id', 'year'], 'uniq_upa_unit_pos_year');
            $table->index(['year', 'unit_id'],    'idx_upa_year_unit');
            $table->index(['year', 'position_id'],'idx_upa_year_pos');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_position_allocations');
    }
};
