<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * employee_import_batches — tracks a Super-Admin-run bulk Employee import
 * from an uploaded CSV/XLSX file, scoped to ONE subject code per batch.
 *
 * WHY ONE SUBJECT CODE PER BATCH (not a mixed multi-code file):
 *   - Every other write path in this system scopes employee creation to a
 *     single subject code's officer/context — keeping imports scoped the
 *     same way means the existing authorization model (Subject Officer
 *     sees only their own subject code) extends to imports for free,
 *     rather than needing a whole new per-row authorization scheme.
 *   - Column mapping is defined once per batch; a file wouldn't need a
 *     "subject_code" column at all, simplifying the mapping UI.
 *
 * WORKFLOW: uploaded -> mapped -> completed | failed
 *
 * DATA SECURITY:
 *   - stored_path is on the PRIVATE disk only, and is deleted immediately
 *     after a successful (or permanently failed) import — raw PII in an
 *     uploaded spreadsheet should not sit in storage longer than needed.
 *   - column_mapping and errors are JSON — errors capped to the first 200
 *     rows' worth of detail to keep the column bounded on a huge file.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_import_batches', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('subject_code_id');
            $table->foreign('subject_code_id')->references('id')->on('subject_codes')->restrictOnDelete();

            $table->unsignedBigInteger('uploaded_by');
            $table->foreign('uploaded_by')->references('id')->on('users')->restrictOnDelete();

            $table->string('original_filename', 255);
            $table->string('stored_path', 255)->nullable(); // null once cleaned up post-import
            $table->string('file_type', 10); // csv | xlsx

            $table->json('column_headers')->nullable();  // raw header row read from the file
            $table->json('column_mapping')->nullable();   // {csv_column_index: employee_field}

            $table->enum('status', ['uploaded', 'mapped', 'completed', 'failed'])->default('uploaded');

            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('imported_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->json('errors')->nullable(); // [{row: 5, message: "..."}]

            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index(['subject_code_id', 'status'], 'idx_import_batches_code_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_import_batches');
    }
};
