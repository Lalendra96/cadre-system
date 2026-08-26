<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Letters can now carry MULTIPLE files (the "file stack"/bucket upload UI),
 * not just one. Each attachment tracks its own 30-day purge independently.
 * Any existing single-file letters have their file migrated into one
 * attachment row here, then the old single-file columns are dropped from
 * `letters`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('letter_attachments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('letter_id');
            $table->string('file_path', 500)->nullable();   // null once auto-purged
            $table->string('original_filename', 255)->nullable();
            $table->unsignedBigInteger('file_size')->nullable(); // bytes, kept after purge for the record
            $table->timestamp('file_removed_at')->nullable();
            $table->timestamps();

            $table->foreign('letter_id')->references('id')->on('letters')->cascadeOnDelete();
            $table->index('created_at', 'idx_letter_attachments_created_at');
        });

        if (Schema::hasColumn('letters', 'file_path')) {
            $letters = DB::table('letters')->whereNotNull('original_filename')->get();

            foreach ($letters as $letter) {
                DB::table('letter_attachments')->insert([
                    'letter_id'          => $letter->id,
                    'file_path'          => $letter->file_path,
                    'original_filename'  => $letter->original_filename,
                    'file_size'          => $letter->file_size,
                    'file_removed_at'    => $letter->file_removed_at,
                    'created_at'         => $letter->created_at,
                    'updated_at'         => $letter->created_at,
                ]);
            }

            Schema::table('letters', function (Blueprint $table) {
                $table->dropColumn(['file_path', 'original_filename', 'file_size', 'file_removed_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('letters', function (Blueprint $table) {
            $table->string('file_path', 500)->nullable();
            $table->string('original_filename', 255)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamp('file_removed_at')->nullable();
        });

        Schema::dropIfExists('letter_attachments');
    }
};
