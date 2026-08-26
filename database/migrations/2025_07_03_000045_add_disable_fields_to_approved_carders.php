<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces hard-delete on approved_carders with a soft-disable workflow.
 *
 * Rationale: approved carder records are the authoritative MoH reference
 * figures. Deleting them would silently remove the baseline from all reports
 * and historical comparisons. Disabling keeps the audit trail intact while
 * removing the record from active reports.
 *
 * A disabled record:
 *   - Does NOT appear in reports or carry-forward calculations
 *   - IS visible in the Admin Group / Director management view (greyed out)
 *   - CAN be re-enabled without data loss
 *   - Carries who disabled it, when, and why
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approved_carders', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('remarks');
            $table->unsignedBigInteger('disabled_by')->nullable()->after('is_active');
            $table->timestamp('disabled_at')->nullable()->after('disabled_by');
            $table->string('disable_reason', 500)->nullable()->after('disabled_at');

            $table->foreign('disabled_by')
                ->references('id')->on('users')
                ->nullOnDelete();

            $table->index(['year', 'is_active', 'position_id'], 'idx_ac_year_active_pos');
        });
    }

    public function down(): void
    {
        Schema::table('approved_carders', function (Blueprint $table) {
            $table->dropForeign(['disabled_by']);
            $table->dropIndex('idx_ac_year_active_pos');
            $table->dropColumn(['is_active', 'disabled_by', 'disabled_at', 'disable_reason']);
        });
    }
};
