<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * employee_exam_records — Sri Lanka public service exam history:
 *   - 'competitive'     : Limited/Open Competitive Examination (for promotion)
 *   - 'efficiency_bar'  : Efficiency Bar (E-Bar) exam — mandatory periodic
 *                         exam that must be passed for increments to
 *                         continue past a certain point in service.
 * An employee can sit for either type multiple times over a career
 * (e.g. failing and re-sitting an E-Bar exam), so this is a history
 * table, never a single current-value field.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_exam_records', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('employee_id');
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();

            $table->enum('exam_type', ['competitive', 'efficiency_bar']);
            $table->string('exam_name', 200)->nullable(); // e.g. "Efficiency Bar Exam - Group A"
            $table->date('exam_date')->nullable();
            $table->enum('result', ['pass', 'fail', 'pending'])->default('pending');
            $table->string('reference_no', 60)->nullable();
            $table->string('notes', 500)->nullable();

            $table->unsignedBigInteger('recorded_by');
            $table->foreign('recorded_by')->references('id')->on('users')->restrictOnDelete();

            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('disabled_by')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->text('disable_reason')->nullable();
            $table->foreign('disabled_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['employee_id', 'exam_type'], 'idx_exam_records_employee_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_exam_records');
    }
};
