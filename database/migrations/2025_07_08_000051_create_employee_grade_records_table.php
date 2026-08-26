<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * employee_grade_records — HISTORY of grade changes per employee.
 * An employee can hold several grade records over their career; the most
 * recent by effective_date (with no end_date, or the latest end_date) is
 * their CURRENT grade. Nothing is ever overwritten — a promotion inserts
 * a new row rather than mutating the old one, preserving full history for
 * HR/audit purposes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_grade_records', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('employee_id');
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();

            $table->unsignedBigInteger('position_grade_id');
            $table->foreign('position_grade_id')->references('id')->on('position_grades')->restrictOnDelete();

            $table->date('effective_date');
            $table->date('end_date')->nullable(); // null = current/ongoing
            $table->string('reference_no', 60)->nullable(); // gazette/circular reference
            $table->string('notes', 500)->nullable();

            $table->unsignedBigInteger('recorded_by');
            $table->foreign('recorded_by')->references('id')->on('users')->restrictOnDelete();

            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('disabled_by')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->text('disable_reason')->nullable();
            $table->foreign('disabled_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['employee_id', 'effective_date'], 'idx_grade_records_employee_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_grade_records');
    }
};
