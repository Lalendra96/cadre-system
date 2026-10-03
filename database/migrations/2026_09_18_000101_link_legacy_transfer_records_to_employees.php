<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Older transfer rows were created from a free-text employee name and
     * therefore have employee_id = NULL. Subject-officer scope is now based on
     * the employee's HR position, so those legacy rows would otherwise be
     * invisible. Link only unambiguous exact-name matches; never guess when
     * duplicate employee names exist.
     */
    public function up(): void
    {
        if (! Schema::hasTable('transfer_records') || ! Schema::hasTable('employees')) {
            return;
        }

        $records = DB::table('transfer_records')
            ->whereNull('employee_id')
            ->whereNotNull('employee_name')
            ->select(['id', 'employee_name'])
            ->orderBy('id')
            ->get();

        foreach ($records as $record) {
            $name = trim((string) $record->employee_name);
            if ($name === '') {
                continue;
            }

            $matches = DB::table('employees')
                ->whereRaw('LOWER(TRIM(name)) = LOWER(?)', [$name])
                ->whereNull('deleted_at')
                ->limit(2)
                ->pluck('id');

            if ($matches->count() !== 1) {
                continue;
            }

            DB::table('transfer_records')
                ->where('id', $record->id)
                ->whereNull('employee_id')
                ->update([
                    'employee_id' => (int) $matches->first(),
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Data-linking migration is intentionally non-destructive on rollback.
     * Removing a valid employee link would recreate the historical visibility
     * and authorisation defect this migration fixes.
     */
    public function down(): void
    {
        // Intentionally no-op.
    }
};
