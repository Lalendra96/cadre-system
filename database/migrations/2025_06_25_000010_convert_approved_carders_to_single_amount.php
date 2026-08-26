<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Approved cadre is no longer split by gender — it's a single approved
 * headcount per designation/year (e.g. "Pharmacist: 30"). Gender split is
 * still captured separately at the monthly actuals level
 * (carder_monthly_entries.males / .females), which is unaffected by this
 * change.
 *
 * Existing approved_male + approved_female values are summed into the new
 * approved_amount column before the old columns are dropped, so no data is
 * lost if this had already been used.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approved_carders', function (Blueprint $table) {
            $table->unsignedInteger('approved_amount')->default(0)->after('subject_code_id');
        });

        if (Schema::hasColumn('approved_carders', 'approved_male')) {
            DB::statement('
                UPDATE approved_carders
                SET approved_amount = COALESCE(approved_male, 0) + COALESCE(approved_female, 0)
            ');

            Schema::table('approved_carders', function (Blueprint $table) {
                $table->dropColumn(['approved_male', 'approved_female']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('approved_carders', function (Blueprint $table) {
            $table->unsignedInteger('approved_male')->default(0)->after('subject_code_id');
            $table->unsignedInteger('approved_female')->default(0)->after('approved_male');
        });

        DB::statement('UPDATE approved_carders SET approved_male = approved_amount, approved_female = 0');

        Schema::table('approved_carders', function (Blueprint $table) {
            $table->dropColumn('approved_amount');
        });
    }
};
