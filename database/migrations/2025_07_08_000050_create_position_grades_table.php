<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * position_grades — the FLEXIBLE grading ladder for a Position
 * (e.g. Medical Officer: Grade III -> Grade II -> Grade I -> Senior Grade).
 *
 * "Flexible" is implemented as:
 *   - `criteria` is a JSON free-form field (min_years_service, required
 *     qualification codes, exam requirements, etc.) so Super Admin/Planning
 *     Officer can define whatever criteria a given position's grade needs
 *     WITHOUT a schema change. The UI renders a simple key/value editor;
 *     the application does not hard-code which criteria keys must exist.
 *   - `sort_order` lets grades be reordered per position without renaming.
 *
 * This is the CONFIGURATION side. The per-employee HISTORY of which grade
 * an employee held and when is in employee_grade_records (separate table,
 * many rows per employee over time).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('position_grades', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('position_id');
            $table->foreign('position_id')->references('id')->on('positions')->cascadeOnDelete();

            $table->string('name', 100);           // e.g. "Grade II", "Senior Grade"
            $table->unsignedSmallInteger('sort_order')->default(10);

            // Flexible criteria — no fixed schema. Example shape:
            // {"min_years_service": 5, "min_qualification": "Diploma", "requires_exam": true}
            $table->json('criteria')->nullable();

            $table->string('description', 500)->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('disabled_by')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->text('disable_reason')->nullable();
            $table->foreign('disabled_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['position_id', 'name'], 'uniq_position_grade_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('position_grades');
    }
};
