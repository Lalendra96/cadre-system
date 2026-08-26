<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CRITICAL FIX — monthly entry uniqueness is per (subject_code, position, year, month)
 * NOT per (subject_code, year, month).
 *
 * WHY THIS MATTERS
 * ────────────────
 * Subject codes can span multiple positions. Example:
 *   Subject Code EA  →  Staff Nurse  (approved: 280)
 *   Subject Code EA  →  Nursing Officer (approved: 45)
 *
 * The original unique index 'uniq_carder_entry_period' only covered
 * (subject_code_id, year, month), which prevented the subject officer
 * from submitting separate monthly figures for Staff Nurse vs Nursing Officer
 * under the same code in the same period — a hard DB error on the second insert.
 *
 * The application-level check in StoreCarderEntryRequest::withValidator()
 * already correctly uses (subject_code_id, position_id, year, month).
 * This migration aligns the DB constraint with the application intent.
 *
 * DATA SAFETY
 * ──────────
 * Before dropping the old constraint we check for any existing rows that
 * would violate the new constraint (same sc+pos+year+month). If any are
 * found they are logged but not deleted — a DBA should review them.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Safety check: find any rows that would violate the new constraint ─
        $conflicts = DB::select("
            SELECT subject_code_id, position_id, year, month, COUNT(*) as cnt
            FROM carder_monthly_entries
            WHERE deleted_at IS NULL
            GROUP BY subject_code_id, position_id, year, month
            HAVING COUNT(*) > 1
        ");

        if (! empty($conflicts)) {
            \Illuminate\Support\Facades\Log::warning(
                '[Migration 000047] Duplicate (subject_code, position, year, month) rows found. '
                . 'Review before deploying.',
                ['conflicts' => $conflicts]
            );
        }

        Schema::table('carder_monthly_entries', function (Blueprint $table) {
            // Drop old constraint: UNIQUE(subject_code_id, year, month)
            $table->dropUnique('uniq_carder_entry_period');

            // Add correct constraint: UNIQUE(subject_code_id, position_id, year, month)
            $table->unique(
                ['subject_code_id', 'position_id', 'year', 'month'],
                'uniq_carder_entry_sc_pos_period'
            );
        });
    }

    public function down(): void
    {
        Schema::table('carder_monthly_entries', function (Blueprint $table) {
            $table->dropUnique('uniq_carder_entry_sc_pos_period');

            // Restore original (weaker) constraint
            $table->unique(
                ['subject_code_id', 'year', 'month'],
                'uniq_carder_entry_period'
            );
        });
    }
};
