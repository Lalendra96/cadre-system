<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Applies the disable workflow (is_active, disabled_by, disabled_at,
 * disable_reason) to every table that manages persistent records.
 *
 * Each table gets:
 *   is_active       BOOLEAN DEFAULT TRUE — false = record is disabled
 *   disabled_by     FK → users(id), nullable, null on user delete
 *   disabled_at     TIMESTAMP nullable
 *   disable_reason  TEXT nullable — mandatory UI-side, nullable DB-side
 *
 * Tables that already have is_active only get the three audit columns.
 * Tables that have neither get all four.
 *
 * AuditLog, ExportAuditLog, SystemSetting, CadreReviewItem,
 * LetterAttachment, LetterRecipient, UserRole — excluded deliberately:
 * these are either append-only audit records or sub-records managed by
 * their parent's lifecycle.
 *
 * All Schema::hasColumn() guards make this migration safe to retry.
 */
return new class extends Migration
{
    /**
     * Tables that already have is_active and just need the audit columns.
     * Format: [table, after_column]
     */
    private function existingActiveTables(): array
    {
        return [
            ['user_categories',    'is_active'],
            ['positions',          'is_active'],
            ['subject_codes',      'is_active'],
            ['units',              'is_active'],
            ['unit_types',         'is_active'],
            ['acting_appointments','is_active'],
            ['ip_allowlist',       'is_active'],
            ['users',              'is_active'],
            ['employees',          'is_active'],
        ];
    }

    /**
     * Tables that need is_active added plus the audit columns.
     * Format: [table, after_column_for_is_active]
     */
    private function newActiveTables(): array
    {
        return [
            ['letters',          'subject_code_id'],
            ['transfer_records', 'notes'],
        ];
    }

    public function up(): void
    {
        // ── Tables that already have is_active ─────────────────────────────
        foreach ($this->existingActiveTables() as [$table, $after]) {
            Schema::table($table, function (Blueprint $t) use ($table, $after) {

                if (! Schema::hasColumn($table, 'disabled_by')) {
                    $t->unsignedBigInteger('disabled_by')
                        ->nullable()
                        ->after($after);

                    $t->foreign('disabled_by')
                        ->references('id')->on('users')
                        ->nullOnDelete();
                }

                if (! Schema::hasColumn($table, 'disabled_at')) {
                    $t->timestamp('disabled_at')->nullable()->after('disabled_by');
                }

                if (! Schema::hasColumn($table, 'disable_reason')) {
                    $t->text('disable_reason')->nullable()->after('disabled_at');
                }
            });
        }

        // ── Tables that need is_active added ───────────────────────────────
        foreach ($this->newActiveTables() as [$table, $after]) {
            Schema::table($table, function (Blueprint $t) use ($table, $after) {

                if (! Schema::hasColumn($table, 'is_active')) {
                    $t->boolean('is_active')->default(true)->after($after);
                }

                if (! Schema::hasColumn($table, 'disabled_by')) {
                    $t->unsignedBigInteger('disabled_by')
                        ->nullable()
                        ->after('is_active');

                    $t->foreign('disabled_by')
                        ->references('id')->on('users')
                        ->nullOnDelete();
                }

                if (! Schema::hasColumn($table, 'disabled_at')) {
                    $t->timestamp('disabled_at')->nullable()->after('disabled_by');
                }

                if (! Schema::hasColumn($table, 'disable_reason')) {
                    $t->text('disable_reason')->nullable()->after('disabled_at');
                }
            });
        }

        // ── carder_monthly_entries — cancelled status instead of disable ───
        // Entries have a rich status workflow (submitted/verified/amendment).
        // Instead of is_active, a 'cancelled' status is added. Cancelled
        // entries are excluded from reports identically to disabled records.
        if (! \Illuminate\Support\Facades\DB::getSchemaBuilder()->hasColumn('carder_monthly_entries', 'cancelled_by')) {
            Schema::table('carder_monthly_entries', function (Blueprint $t) {
                $t->unsignedBigInteger('cancelled_by')->nullable()->after('amendment_reason');
                $t->timestamp('cancelled_at')->nullable()->after('cancelled_by');
                $t->text('cancel_reason')->nullable()->after('cancelled_at');
                $t->foreign('cancelled_by')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        $allTables = array_merge(
            array_column($this->existingActiveTables(), 0),
            array_column($this->newActiveTables(), 0)
        );

        foreach ($allTables as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                try { $t->dropForeign([$table === 'acting_appointments' ? 'acting_appointments_disabled_by_foreign' : "{$table}_disabled_by_foreign"]); } catch (\Exception $e) {}
                foreach (['disabled_by', 'disabled_at', 'disable_reason'] as $col) {
                    if (Schema::hasColumn($table, $col)) {
                        $t->dropColumn($col);
                    }
                }
            });
        }

        // Remove is_active from new-active tables
        foreach ($this->newActiveTables() as [$table]) {
            if (Schema::hasColumn($table, 'is_active')) {
                Schema::table($table, fn ($t) => $t->dropColumn('is_active'));
            }
        }

        // carder_monthly_entries cancel columns
        Schema::table('carder_monthly_entries', function (Blueprint $t) {
            try { $t->dropForeign(['cancelled_by']); } catch (\Exception $e) {}
            foreach (['cancelled_by', 'cancelled_at', 'cancel_reason'] as $col) {
                if (Schema::hasColumn('carder_monthly_entries', $col)) {
                    $t->dropColumn($col);
                }
            }
        });
    }
};
