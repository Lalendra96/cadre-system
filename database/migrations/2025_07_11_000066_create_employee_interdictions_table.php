<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * employee_interdictions — interdiction / disciplinary inquiry history.
 * An interdicted officer still substantively holds their post but is not
 * actively performing duty — a real, recurring distinction in Sri Lanka
 * public service HR that this system did not previously model at all.
 *
 * An employee can be interdicted more than once over a career, hence a
 * history table (same pattern as grades/increments), not a single status
 * field on employees.
 *
 * inquiry_status distinguishes "still under inquiry" from "concluded" so
 * a currently-active interdiction can be identified without inferring it
 * from date arithmetic alone — reinstatement_date being null does NOT by
 * itself mean still-interdicted if the inquiry_status is already
 * 'concluded' with an outcome recorded but reinstatement pending
 * paperwork; the two are tracked independently on purpose.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_interdictions', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('employee_id');
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();

            $table->date('interdiction_date');
            $table->string('reason', 500)->nullable();
            $table->string('inquiry_reference_no', 100)->nullable();

            $table->enum('inquiry_status', ['ongoing', 'concluded'])->default('ongoing');

            $table->date('reinstatement_date')->nullable();
            $table->enum('outcome', ['reinstated', 'dismissed', 'resigned', 'other'])->nullable();
            $table->string('notes', 500)->nullable();

            $table->unsignedBigInteger('recorded_by');
            $table->foreign('recorded_by')->references('id')->on('users')->restrictOnDelete();

            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('disabled_by')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->text('disable_reason')->nullable();
            $table->foreign('disabled_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['employee_id', 'inquiry_status'], 'idx_interdictions_employee_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_interdictions');
    }
};
