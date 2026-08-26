<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * unit_position — which Positions are actually relevant/expected for a
 * given Unit. Super-Admin-managed. Used to scope the Unit Post Allocation
 * entry grid (UnitAllocationController::edit()) to just the positions
 * that make sense for that unit, instead of the full 58-position catalog
 * on every single unit ("Dental Surgeon" has no business appearing on
 * "Hospital Kitchen"'s entry form).
 *
 * DELIBERATELY NOT a hard filter on existing data: a unit with no
 * bindings configured yet falls back to showing every position (see
 * UnitAllocationController), so nothing that was already entered becomes
 * invisible just because Super Admin hasn't gotten to configuring that
 * unit yet. Bindings are a scoping AID for data entry, not a gate that
 * can hide real headcount data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_position', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('unit_id');
            $table->unsignedBigInteger('position_id');
            $table->foreign('unit_id')->references('id')->on('units')->cascadeOnDelete();
            $table->foreign('position_id')->references('id')->on('positions')->cascadeOnDelete();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['unit_id', 'position_id'], 'uniq_unit_position');
            $table->index('unit_id', 'idx_unit_position_unit');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_position');
    }
};
