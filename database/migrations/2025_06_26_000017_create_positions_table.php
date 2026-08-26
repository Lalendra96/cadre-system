<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table: positions
 * A Designation is split into one or more Positions (e.g. Designation
 * "Nursing" → Positions "Staff Nurse", "Nursing Sister"). Subject Codes,
 * Approved Carder figures, monthly actuals, and Employee Profiles all now
 * attach to a Position rather than directly to a Designation — Designation
 * becomes a rollup grouping (sum of its Positions).
 * CRUD by: Super Admin + Planning Officer (same as Designations).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('positions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('designation_id');
            $table->string('code', 30)->unique();
            $table->string('title', 150);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('designation_id')->references('id')->on('designations')->cascadeOnDelete();
            $table->index('designation_id', 'idx_positions_designation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
