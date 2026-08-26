<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * position_subcategories — Super-Admin-configurable subgrouping under a
 * "main" Position. Example: Position "Medical Consultant" can have
 * subcategories "Health Information Consultant", "Emergency Physician",
 * "PGIM Trainee" etc. — each tracked distinctly at the unit level (see
 * unit_position_allocations.position_subcategory_id) while still rolling
 * up into the parent Position's total headcount.
 *
 * counts_toward_parent_total — the field that makes the PGIM use case
 * possible: a subcategory can be recorded (with its own in-post/required
 * figures, visible as a remark on the breakdown) WITHOUT being summed
 * into the parent Position's main total. Default true (a normal
 * subcategory contributes to its parent's total); set false for
 * subcategories that are tracked-but-excluded, like PGIM trainees.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('position_subcategories', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('parent_position_id');
            $table->foreign('parent_position_id')->references('id')->on('positions')->cascadeOnDelete();

            $table->string('name', 150);
            $table->string('code', 30)->nullable();
            $table->string('description', 300)->nullable();

            $table->boolean('counts_toward_parent_total')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete();

            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('disabled_by')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->text('disable_reason')->nullable();
            $table->foreign('disabled_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['parent_position_id', 'name'], 'uniq_subcat_parent_name');
            $table->index('parent_position_id', 'idx_subcat_parent');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('position_subcategories');
    }
};
