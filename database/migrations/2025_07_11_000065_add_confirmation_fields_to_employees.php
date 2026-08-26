<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Confirmation-in-service — modelled as simple fields directly on
 * employees, NOT a history table like grades/increments, because
 * confirmation is a single, one-time formal HR event (probation ->
 * confirmed) in the normal course of service, not something that
 * recurs. `is_confirmed` is stored explicitly rather than merely
 * inferred from date_confirmed being non-null, because "confirmed" is
 * itself a formal HR determination backed by a specific letter/reference
 * — the boolean is the fact being asserted, the date and reference are
 * supporting detail.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'is_confirmed')) {
                $table->boolean('is_confirmed')->default(false)->after('retirement_age');
            }
            if (! Schema::hasColumn('employees', 'date_confirmed')) {
                $table->date('date_confirmed')->nullable()->after('is_confirmed');
            }
            if (! Schema::hasColumn('employees', 'confirmation_reference_no')) {
                $table->string('confirmation_reference_no', 60)->nullable()->after('date_confirmed');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            foreach (['is_confirmed', 'date_confirmed', 'confirmation_reference_no'] as $col) {
                if (Schema::hasColumn('employees', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
