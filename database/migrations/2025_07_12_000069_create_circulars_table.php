<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * circulars — Memos, Circulars, and Internal Circulars uploaded by staff,
 * shared via an unguessable public link ("anyone with the link can view").
 *
 * DATA SECURITY — this is the first feature in this system with a
 * genuinely PUBLIC (unauthenticated) route, so the design choices here
 * matter more than usual:
 *
 *   - `share_token` is a 40-character cryptographically random string
 *     (Str::random(), which uses PHP's CSPRNG), NEVER the sequential `id`.
 *     The public route resolves by this token only — an attacker
 *     iterating ?id=1,2,3... can never enumerate circulars this way,
 *     since the token space is far too large to brute-force.
 *   - The underlying file still lives on the PRIVATE disk, same as every
 *     other upload in this system (letters, e-signatures, imports).
 *     "Public link" means the CONTROLLER ACTION is reachable without
 *     login and resolves via the token — it does NOT mean the file sits
 *     on a publicly-browsable disk/URL. Even if the storage path were
 *     somehow guessed, it isn't directly web-accessible.
 *   - Disabling a circular (HasDisableWorkflow) must make its public link
 *     404, not reveal "this exists but was disabled" — see
 *     CircularController::show() for how that's enforced.
 *   - view_count is a simple counter, not a per-view audit log — public
 *     viewers are by definition unauthenticated, so there is no user
 *     identity to attribute a view-level audit entry to.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('circulars', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->enum('category', ['memo', 'circular', 'internal_circular'])->default('circular');

            $table->string('file_path', 255);      // private disk path
            $table->string('original_filename', 255);
            $table->string('mime_type', 100);
            $table->unsignedInteger('file_size');  // bytes

            $table->string('share_token', 64)->unique();
            $table->unsignedInteger('view_count')->default(0);

            $table->unsignedBigInteger('uploaded_by');
            $table->foreign('uploaded_by')->references('id')->on('users')->restrictOnDelete();

            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('disabled_by')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->text('disable_reason')->nullable();
            $table->foreign('disabled_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();

            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('circulars');
    }
};
