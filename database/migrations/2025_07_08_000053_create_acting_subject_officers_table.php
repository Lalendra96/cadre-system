<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * acting_subject_officers — Super Admin appoints a user to temporarily
 * act as the Subject Officer for a subject code they are not permanently
 * assigned to (e.g. covering leave).
 *
 * NOT the same as `acting_appointments` (that table is for an EMPLOYEE
 * acting in a higher POSITION — a workforce/HR record). This table is a
 * SYSTEM ACCESS grant — it controls what a USER can submit/view in the
 * application, independent of the employee establishment.
 *
 * DATA SECURITY: only Super Admin may create these (enforced in the
 * controller, not just route middleware — see ActingSubjectOfficerController).
 * The effective-access check (User::effectiveSubjectCodeIds()) treats an
 * acting assignment as equivalent to a permanent one ONLY while
 * is_active = true AND today is within [start_date, end_date] (or
 * end_date is null and start_date <= today).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acting_subject_officers', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->unsignedBigInteger('subject_code_id');
            $table->foreign('subject_code_id')->references('id')->on('subject_codes')->cascadeOnDelete();

            $table->date('start_date');
            $table->date('end_date')->nullable(); // null = open-ended until revoked

            $table->unsignedBigInteger('appointed_by');
            $table->foreign('appointed_by')->references('id')->on('users')->restrictOnDelete();

            $table->string('reason', 300)->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('disabled_by')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->text('disable_reason')->nullable();
            $table->foreign('disabled_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['subject_code_id', 'is_active'], 'idx_acting_so_code_active');
            $table->index(['user_id', 'is_active'], 'idx_acting_so_user_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acting_subject_officers');
    }
};
