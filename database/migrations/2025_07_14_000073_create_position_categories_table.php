<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * position_categories — a 2-level Super-Admin-configurable taxonomy for
 * grouping Positions. Example from the original request:
 *   Main Category:  "Medical Consultants" (parent_id = null)
 *   Subcategory:     "Consultant in Health Information" (parent_id = Medical Consultants' id)
 *
 * A Position optionally belongs to one Subcategory (positions.category_id,
 * see the companion migration) — never directly to a Main Category. This
 * keeps the hierarchy meaningful: reporting can roll a Position up to its
 * Subcategory, then further up to its Main Category, without ambiguity
 * about which level a given Position "really" belongs to.
 *
 * Deliberately only 2 levels (not arbitrary-depth) — self-referencing
 * parent_id is still used rather than a fixed 'is_main'/'sub' pair of
 * columns, so Super Admin can restructure later (e.g. promote a
 * subcategory to its own main category) without a schema change, but
 * application code enforces the 2-level limit (a subcategory whose
 * parent itself has a parent is rejected — see PositionCategoryController).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('position_categories', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('name', 150);
            $table->text('description')->nullable();

            $table->unsignedBigInteger('parent_id')->nullable();
            $table->foreign('parent_id')->references('id')->on('position_categories')->cascadeOnDelete();

            $table->unsignedInteger('sort_order')->default(0);

            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete();

            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('disabled_by')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->text('disable_reason')->nullable();
            $table->foreign('disabled_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['parent_id', 'name'], 'uniq_category_name_per_parent');
            $table->index('parent_id', 'idx_position_categories_parent');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('position_categories');
    }
};
