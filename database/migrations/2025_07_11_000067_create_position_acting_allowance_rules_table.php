<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * position_acting_allowance_rules — Super-Admin-configurable acting
 * allowance calculation rule, one per Position (the position being ACTED
 * IN, i.e. acting_appointments.acting_position_id — not the substantive
 * position). "Customizable per individual Post" per the original request:
 * each position can have its own rule type and rate, since real acting
 * allowance entitlement varies by grade/scheme in the Sri Lanka public
 * service and cannot be a single hospital-wide constant.
 *
 * THREE SUPPORTED RULE TYPES (rule_type):
 *   'percentage_of_salary_difference' — allowance = percentage% x (acting
 *        position's salary scale midpoint minus substantive position's
 *        salary scale midpoint). Needs both positions to have a linked
 *        SalaryScale to compute; falls back to flagging "cannot compute"
 *        if either is missing rather than guessing.
 *   'percentage_of_base'   — allowance = percentage% x acting position's
 *        own salary scale midpoint, ignoring the substantive position.
 *   'flat_amount'          — allowance = flat_amount, a fixed Rs. figure
 *        regardless of salary scale.
 *
 * One rule per position (unique constraint) — if a position has no rule
 * configured, ActingAllowanceCalculator reports "not configured" rather
 * than defaulting to zero, so a missing configuration is visibly
 * distinguishable from a genuinely zero-value entitlement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('position_acting_allowance_rules', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('position_id')->unique();
            $table->foreign('position_id')->references('id')->on('positions')->cascadeOnDelete();

            $table->enum('rule_type', ['percentage_of_salary_difference', 'percentage_of_base', 'flat_amount']);
            $table->decimal('percentage', 5, 2)->nullable();     // e.g. 25.00 for 25%
            $table->decimal('flat_amount', 12, 2)->nullable();   // Rs. figure for the flat_amount rule type
            $table->string('notes', 500)->nullable();

            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete();

            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('disabled_by')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->text('disable_reason')->nullable();
            $table->foreign('disabled_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('position_acting_allowance_rules');
    }
};
