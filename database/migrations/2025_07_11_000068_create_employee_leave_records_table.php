<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * employee_leave_records — per-employee Leave Without Pay (LWOP) history
 * with an expected return date, distinct from the aggregate monthly
 * `no_pay_leave` COUNT already tracked on carder_monthly_entries. That
 * count answers "how many people were on no-pay leave this month" for
 * hospital-wide reporting; this table answers "which specific employee,
 * since when, and when are they due back" — the detail needed to decide
 * whether a post should be temporarily backfilled.
 *
 * actual_return_date is nullable and distinct from expected_return_date:
 * an employee can return early, late, or request an extension (a new
 * record, not an edit to this one — the original expectation stays on
 * the historical record).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_leave_records', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('employee_id');
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();

            $table->enum('leave_type', ['no_pay_leave', 'other'])->default('no_pay_leave');

            $table->date('start_date');
            $table->date('expected_return_date')->nullable();
            $table->date('actual_return_date')->nullable();

            $table->string('reference_no', 100)->nullable();
            $table->string('notes', 500)->nullable();

            $table->unsignedBigInteger('recorded_by');
            $table->foreign('recorded_by')->references('id')->on('users')->restrictOnDelete();

            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('disabled_by')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->text('disable_reason')->nullable();
            $table->foreign('disabled_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['employee_id', 'actual_return_date'], 'idx_leave_records_employee_return');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_leave_records');
    }
};
