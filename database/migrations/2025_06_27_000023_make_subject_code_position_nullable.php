<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Reverts the NOT NULL constraint on subject_codes.position_id that was added
 * in migration 000018. Some subject codes do not correspond to any specific
 * Position (e.g. general administrative codes, cross-cutting codes) — they
 * still need to exist and be assignable to officers, they just don't need to
 * roll up into a Position's approved-carder or monthly-entry totals.
 *
 * No data is changed; only the column nullability constraint is relaxed.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE subject_codes ALTER COLUMN position_id DROP NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE subject_codes ALTER COLUMN position_id SET NOT NULL');
    }
};
