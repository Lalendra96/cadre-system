<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * W&OP Number — Widows' & Orphans' Pension scheme number, a Sri Lanka
 * public service pension identifier distinct from pay_no and nic_number.
 * Nullable (not every appointment type contributes), unique when present.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'wop_number')) {
                $table->string('wop_number', 20)->nullable()->unique()->after('nic_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'wop_number')) {
                $table->dropUnique(['wop_number']);
                $table->dropColumn('wop_number');
            }
        });
    }
};
