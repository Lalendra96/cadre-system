<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * employee_qualifications — education/professional qualification history.
 * An employee can hold several (O/L, A/L, Diploma, Degree, Postgraduate,
 * professional certifications) — never mutated, just accumulated.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_qualifications', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('employee_id');
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();

            $table->string('qualification_name', 200); // e.g. "BSc Nursing", "Diploma in Pharmacy"
            $table->string('institution', 200)->nullable();
            $table->unsignedSmallInteger('year_obtained')->nullable();
            $table->string('reference_no', 60)->nullable(); // certificate number
            $table->string('notes', 500)->nullable();

            $table->unsignedBigInteger('recorded_by');
            $table->foreign('recorded_by')->references('id')->on('users')->restrictOnDelete();

            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('disabled_by')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->text('disable_reason')->nullable();
            $table->foreign('disabled_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['employee_id', 'year_obtained'], 'idx_qualifications_employee_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_qualifications');
    }
};
