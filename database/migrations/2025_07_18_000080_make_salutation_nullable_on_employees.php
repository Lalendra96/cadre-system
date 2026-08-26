<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Salutation is no longer a required field on Employee. Real-world source
 * data (see the Nursing Officer / Nursing Sister import work) frequently
 * has it missing — 62 of 223 imported Nursing staff had no salutation on
 * file at all. The display accessor (Employee::getDisplayNameAttribute())
 * already handles a blank salutation gracefully (falls back to plain
 * name), so requiring it at the database level was blocking otherwise-
 * complete, importable records over a single cosmetic field.
 *
 * The field itself is kept, not dropped — existing salutation values are
 * untouched, and it remains available wherever it's already recorded.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Raw SQL rather than ->change() — the latter requires doctrine/dbal,
        // which isn't used anywhere else in this project's migrations and
        // isn't verifiable as installed (no composer.json in this delivery).
        DB::statement('ALTER TABLE employees ALTER COLUMN salutation DROP NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE employees ALTER COLUMN salutation SET NOT NULL');
    }
};
