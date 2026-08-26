<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * position_groups — Super-Admin-configured, named collections of
 * Positions (e.g. "Nursing Staff" = Staff Nurse + Nursing Officer +
 * Sister I + Sister II), used as the send-target when a Subject Officer
 * distributes a Circular/Memo. "Send to Nurses" resolves to every active
 * Employee currently in any position belonging to that group.
 *
 * Groups themselves are NOT subject-code-scoped — Super Admin defines
 * them hospital-wide. What IS scoped is the actual recipient list at
 * send time: CircularController::sendToGroups() always intersects the
 * group's positions with the SENDING OFFICER's own subject codes, so a
 * Subject Officer can only ever reach their own employees, regardless of
 * which groups exist.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('position_groups', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('name', 150)->unique();
            $table->string('description', 500)->nullable();

            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete();

            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('disabled_by')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->text('disable_reason')->nullable();
            $table->foreign('disabled_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();
        });

        Schema::create('position_group_position', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('position_group_id');
            $table->unsignedBigInteger('position_id');
            $table->foreign('position_group_id')->references('id')->on('position_groups')->cascadeOnDelete();
            $table->foreign('position_id')->references('id')->on('positions')->cascadeOnDelete();
            $table->unique(['position_group_id', 'position_id'], 'uniq_group_position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('position_group_position');
        Schema::dropIfExists('position_groups');
    }
};
