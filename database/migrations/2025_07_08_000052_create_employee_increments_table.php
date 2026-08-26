<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * employee_increments — HISTORY of salary increment dates per employee.
 * An employee can have several increment records over time (annual
 * increments, arrears-adjusted increments, etc). The NEXT upcoming
 * increment is whichever row has the soonest increment_date >= today
 * and notified_at IS NULL — see App\Console\Commands\NotifyUpcomingIncrements.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_increments', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('employee_id');
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();

            $table->date('increment_date');
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('reference_no', 60)->nullable();
            $table->string('notes', 500)->nullable();

            // Set once the 30-day-before reminder notification has been sent,
            // so the scheduled command never notifies the same increment twice.
            $table->timestamp('notified_at')->nullable();

            $table->unsignedBigInteger('recorded_by');
            $table->foreign('recorded_by')->references('id')->on('users')->restrictOnDelete();

            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('disabled_by')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->text('disable_reason')->nullable();
            $table->foreign('disabled_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['increment_date', 'notified_at'], 'idx_increments_date_notified');
            $table->index(['employee_id', 'increment_date'], 'idx_increments_employee_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_increments');
    }
};
