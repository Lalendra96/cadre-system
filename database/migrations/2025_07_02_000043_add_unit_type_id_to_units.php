<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds unit_type_id and location to the units table.
 *
 * NOTE: `code` and `is_active` were already present from migration 000012
 * (create_units_table). This migration ONLY adds the two genuinely new
 * columns and avoids touching columns that already exist.
 *
 * All additions are guarded with Schema::hasColumn() so the migration is
 * safe to retry after a partial failure.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {

            // Foreign key to unit_types — new column
            if (! Schema::hasColumn('units', 'unit_type_id')) {
                $table->unsignedBigInteger('unit_type_id')
                    ->nullable()
                    ->after('name');

                $table->foreign('unit_type_id')
                    ->references('id')->on('unit_types')
                    ->nullOnDelete();
            }

            // Physical location descriptor — new column
            if (! Schema::hasColumn('units', 'location')) {
                $table->string('location', 150)
                    ->nullable()
                    ->after('unit_type_id');
            }

            // is_active and code already exist from 000012 — do NOT re-add.
        });

        // Add index on unit_type_id (separate call so the FK above is committed first)
        try {
            Schema::table('units', function (Blueprint $table) {
                $table->index('unit_type_id', 'idx_units_type');
            });
        } catch (\Exception $e) {
            // Index already exists — safe to ignore on re-run
        }
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            try {
                $table->dropForeign(['unit_type_id']);
                $table->dropIndex('idx_units_type');
            } catch (\Exception $e) {
                // Already dropped — ignore
            }

            if (Schema::hasColumn('units', 'location')) {
                $table->dropColumn('location');
            }

            if (Schema::hasColumn('units', 'unit_type_id')) {
                $table->dropColumn('unit_type_id');
            }
        });
    }
};
