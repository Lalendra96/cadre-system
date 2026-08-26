<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds two HR fields directly on employees (single current value, not
 * history — history lives in employee_grade_records / employee_increments):
 *   - salary_scale_id: the employee's current official salary scale
 *   - date_reported_for_duty: date the employee actually reported for duty
 *     at THIS hospital, distinct from date_of_appointment (the Ministry
 *     appointment date can differ from the day they physically reported —
 *     a standard distinction in the Sri Lanka Establishment Code).
 *
 * Both nullable — existing employee rows are not required to backfill
 * immediately, and not every employee profile will have a salary scale
 * recorded on day one of using this feature.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'salary_scale_id')) {
                $table->unsignedBigInteger('salary_scale_id')->nullable()->after('position_id');
                $table->foreign('salary_scale_id')->references('id')->on('salary_scales')->nullOnDelete();
            }
            if (! Schema::hasColumn('employees', 'date_reported_for_duty')) {
                $table->date('date_reported_for_duty')->nullable()->after('date_of_appointment');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'salary_scale_id')) {
                $table->dropForeign(['salary_scale_id']);
                $table->dropColumn('salary_scale_id');
            }
            if (Schema::hasColumn('employees', 'date_reported_for_duty')) {
                $table->dropColumn('date_reported_for_duty');
            }
        });
    }
};
