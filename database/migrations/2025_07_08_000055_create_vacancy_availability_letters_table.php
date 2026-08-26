<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * vacancy_availability_letters — a formal, e-signed letter declaring a
 * position (within a subject code) has an available vacancy that can be
 * recruited against.
 *
 * COOLDOWN RULE (business requirement: "should not be able to give for
 * another 90 days"): once issued, the SAME position+subject_code
 * combination cannot have another vacancy availability letter issued
 * until `cooldown_until` has passed. Enforced in
 * VacancyAvailabilityLetterController::store() via a query for any
 * existing row where cooldown_until >= today for the same pair — NOT
 * relied upon as a DB constraint alone, since "90 days from the last
 * issue date" is a moving window, not a fixed uniqueness rule.
 *
 * Subject Officers can only ever READ this table (to see whether a
 * position under their subject code currently has an available vacancy)
 * — only Admin Group / AO can issue one, via e-signature.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacancy_availability_letters', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('position_id');
            $table->foreign('position_id')->references('id')->on('positions')->restrictOnDelete();

            $table->unsignedBigInteger('subject_code_id');
            $table->foreign('subject_code_id')->references('id')->on('subject_codes')->restrictOnDelete();

            $table->unsignedSmallInteger('vacancy_count')->default(1);
            $table->string('reference_no', 60)->nullable();
            $table->text('remarks')->nullable();

            $table->unsignedBigInteger('issued_by');
            $table->foreign('issued_by')->references('id')->on('users')->restrictOnDelete();
            $table->timestamp('issued_at');

            $table->unsignedBigInteger('e_signature_id');
            $table->foreign('e_signature_id')->references('id')->on('e_signatures')->restrictOnDelete();

            // The 90-day cooldown boundary for this position+subject_code pair.
            $table->date('cooldown_until');

            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('disabled_by')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->text('disable_reason')->nullable();
            $table->foreign('disabled_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['position_id', 'subject_code_id', 'cooldown_until'], 'idx_vacancy_letter_cooldown');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacancy_availability_letters');
    }
};
