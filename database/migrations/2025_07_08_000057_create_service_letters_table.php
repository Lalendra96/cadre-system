<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * service_letters — formal HR service letters (e.g. service confirmation,
 * salary certification) drafted by a Subject Officer for one of THEIR OWN
 * assigned employees, then routed to an Administrative Officer (AO) for
 * e-signed approval before being considered issued.
 *
 * WORKFLOW / STATUS MACHINE
 *   draft -> pending_approval -> approved   (AO e-signs; final, issued)
 *                              -> rejected  (AO declines with a reason;
 *                                            officer may edit and resubmit)
 *
 * DATA SECURITY — ownership double-enforced (same pattern as Employee):
 *   1. ServiceLetterController checks the drafting officer's subject code
 *      scope (via User::effectiveSubjectCodeIds(), which includes acting
 *      assignments) against the target employee's subject_code_id.
 *   2. StoreServiceLetterRequest::authorize() re-checks the same scope
 *      independently, so a route-level bypass alone can never succeed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_letters', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('employee_id');
            $table->foreign('employee_id')->references('id')->on('employees')->restrictOnDelete();

            $table->unsignedBigInteger('template_id')->nullable();
            $table->foreign('template_id')->references('id')->on('service_letter_templates')->nullOnDelete();

            $table->string('subject', 200);
            $table->text('rendered_body'); // template with placeholders already substituted

            $table->enum('status', ['draft', 'pending_approval', 'approved', 'rejected'])
                ->default('draft');

            $table->unsignedBigInteger('drafted_by');
            $table->foreign('drafted_by')->references('id')->on('users')->restrictOnDelete();

            $table->unsignedBigInteger('approved_by')->nullable();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->unsignedBigInteger('e_signature_id')->nullable();
            $table->foreign('e_signature_id')->references('id')->on('e_signatures')->nullOnDelete();

            $table->text('rejection_reason')->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('disabled_by')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->text('disable_reason')->nullable();
            $table->foreign('disabled_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['employee_id', 'status'], 'idx_service_letters_employee_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_letters');
    }
};
