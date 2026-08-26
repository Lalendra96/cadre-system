<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Transfer Board / PSC circular integration — adds the specific reference
 * fields real Sri Lanka public service transfers are authorised under:
 * a Transfer Board decision (with its own reference + date, distinct from
 * the transfer's effective_date) and/or a Public Service Commission (PSC)
 * circular reference. Both nullable — not every transfer_type (e.g. an
 * internal ward-to-ward move) goes through the Transfer Board.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transfer_records', function (Blueprint $table) {
            if (! Schema::hasColumn('transfer_records', 'psc_circular_no')) {
                $table->string('psc_circular_no', 100)->nullable()->after('notes');
            }
            if (! Schema::hasColumn('transfer_records', 'transfer_board_ref_no')) {
                $table->string('transfer_board_ref_no', 100)->nullable()->after('psc_circular_no');
            }
            if (! Schema::hasColumn('transfer_records', 'transfer_board_decision_date')) {
                $table->date('transfer_board_decision_date')->nullable()->after('transfer_board_ref_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transfer_records', function (Blueprint $table) {
            foreach (['psc_circular_no', 'transfer_board_ref_no', 'transfer_board_decision_date'] as $col) {
                if (Schema::hasColumn('transfer_records', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
