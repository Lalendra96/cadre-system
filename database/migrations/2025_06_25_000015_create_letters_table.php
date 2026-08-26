<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table: letters
 * A document a Subject Officer shares for review. The actual file is
 * removed automatically 30 days after upload (see PurgeExpiredLetterFiles
 * command) — metadata (title, original filename, who/when) is kept so the
 * record and its review history remain visible even after the file itself
 * is gone.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('letters', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('created_by'); // the Subject Officer who shared it
            $table->unsignedBigInteger('subject_code_id')->nullable();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('file_path', 500)->nullable();   // null once auto-purged
            $table->string('original_filename', 255)->nullable();
            $table->unsignedBigInteger('file_size')->nullable(); // bytes, kept after purge for the record
            $table->timestamp('file_removed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('subject_code_id')->references('id')->on('subject_codes')->nullOnDelete();

            $table->index('created_at', 'idx_letters_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('letters');
    }
};
