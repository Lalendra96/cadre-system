<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extends carder_monthly_entries with workflow status and verification fields.
 *
 * NOTE: submitted_by and submitted_at already exist from migration 000006
 * (they were part of the original table definition). This migration only
 * adds the NEW columns that support the deadline-enforcement and
 * verification-layer features.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carder_monthly_entries', function (Blueprint $table) {
            // Only add columns that are genuinely new.
            // submitted_by + submitted_at are already on the table (000006).

            if (! Schema::hasColumn('carder_monthly_entries', 'status')) {
                $table->string('status', 30)->default('submitted')->after('remarks');
            }

            if (! Schema::hasColumn('carder_monthly_entries', 'is_locked')) {
                $table->boolean('is_locked')->default(false)->after('status');
            }

            if (! Schema::hasColumn('carder_monthly_entries', 'verified_by')) {
                $table->unsignedBigInteger('verified_by')->nullable()->after('submitted_at');
            }

            if (! Schema::hasColumn('carder_monthly_entries', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('verified_by');
            }

            if (! Schema::hasColumn('carder_monthly_entries', 'amendment_requested_by')) {
                $table->unsignedBigInteger('amendment_requested_by')->nullable()->after('verified_at');
            }

            if (! Schema::hasColumn('carder_monthly_entries', 'amendment_requested_at')) {
                $table->timestamp('amendment_requested_at')->nullable()->after('amendment_requested_by');
            }

            if (! Schema::hasColumn('carder_monthly_entries', 'amendment_reason')) {
                $table->text('amendment_reason')->nullable()->after('amendment_requested_at');
            }
        });

        // Add foreign keys only if the columns were just created
        // (hasColumn check above means they may exist from a previous partial run)
        if (Schema::hasColumn('carder_monthly_entries', 'verified_by')) {
            try {
                Schema::table('carder_monthly_entries', function (Blueprint $table) {
                    $table->foreign('verified_by')
                        ->references('id')->on('users')->nullOnDelete();
                });
            } catch (\Exception $e) {
                // Foreign key may already exist — safe to ignore on re-run
            }
        }

        if (Schema::hasColumn('carder_monthly_entries', 'amendment_requested_by')) {
            try {
                Schema::table('carder_monthly_entries', function (Blueprint $table) {
                    $table->foreign('amendment_requested_by')
                        ->references('id')->on('users')->nullOnDelete();
                });
            } catch (\Exception $e) {
                // Safe to ignore on re-run
            }
        }

        // Add status index if not already present
        try {
            Schema::table('carder_monthly_entries', function (Blueprint $table) {
                $table->index('status', 'idx_cme_status');
            });
        } catch (\Exception $e) {
            // Index may already exist
        }
    }

    public function down(): void
    {
        Schema::table('carder_monthly_entries', function (Blueprint $table) {
            $table->dropForeignIfExists(['verified_by', 'amendment_requested_by']);
            $table->dropColumnIfExists([
                'status', 'is_locked',
                'verified_by', 'verified_at',
                'amendment_requested_by', 'amendment_requested_at', 'amendment_reason',
            ]);
        });
    }
};
