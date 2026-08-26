<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Table: carder_monthly_entries
 * Monthly actuals submitted by a Subject Officer against their subject_code:
 * males, females, transferred_in, transferred_out, no_pay_leave.
 * One row per subject_code, per year, per month (enforced by unique index).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carder_monthly_entries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('subject_code_id');
            $table->unsignedBigInteger('designation_id')->nullable();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month'); // 1-12
            $table->unsignedInteger('males')->default(0);
            $table->unsignedInteger('females')->default(0);
            $table->unsignedInteger('transferred_in')->default(0);
            $table->unsignedInteger('transferred_out')->default(0);
            $table->unsignedInteger('no_pay_leave')->default(0);
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('subject_code_id')->references('id')->on('subject_codes')->cascadeOnDelete();
            $table->foreign('designation_id')->references('id')->on('designations')->nullOnDelete();
            $table->foreign('submitted_by')->references('id')->on('users')->nullOnDelete();

            $table->unique(['subject_code_id', 'year', 'month'], 'uniq_carder_entry_period');
            $table->index(['year', 'month'], 'idx_carder_entries_period');
        });

        // PostgreSQL CHECK constraint for valid month range
        DB::statement('ALTER TABLE carder_monthly_entries ADD CONSTRAINT chk_month_range CHECK (month >= 1 AND month <= 12)');
    }

    public function down(): void
    {
        Schema::dropIfExists('carder_monthly_entries');
    }
};
