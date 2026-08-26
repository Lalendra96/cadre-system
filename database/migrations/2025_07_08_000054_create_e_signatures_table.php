<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * e_signatures — one registered electronic signature image per Admin
 * Group user, used when approving Service Letters and Vacancy Availability
 * Letters.
 *
 * DATA SECURITY:
 *   - `file_path` points into the PRIVATE disk (storage/app/private), never
 *     the public disk — a signature image must never be reachable by a
 *     guessable public URL. Served only via a controller action that
 *     checks the requester is either the signature's owner or has a
 *     legitimate reason to view it (e.g. rendering an already-signed
 *     letter, not fetching the raw signature file directly).
 *   - Only one active signature per user (unique on user_id) — replacing
 *     a signature disables the old row rather than overwriting the file,
 *     so previously-signed documents keep referencing the correct image.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('e_signatures', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->string('file_path', 255); // private disk path
            $table->string('mime_type', 60);
            $table->unsignedInteger('file_size'); // bytes, for basic sanity checks

            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('disabled_by')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->text('disable_reason')->nullable();
            $table->foreign('disabled_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();

            // Partial-unique intent: enforced in the model layer via
            // disableRecord() on the prior row before creating a new one,
            // since portable partial unique indexes vary by DB driver.
            $table->index(['user_id', 'is_active'], 'idx_esig_user_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('e_signatures');
    }
};
