<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds nic_number (Sri Lanka National Identity Card number) to employees.
 *
 * DATA SECURITY NOTE — NIC is sensitive PII:
 *   - Stored as plain string (not encrypted at rest) to allow uniqueness
 *     enforcement and lookup at the database level — this matches how
 *     pay_no is already handled in this table. If full encryption-at-rest
 *     for NIC becomes a requirement, use Laravel's `encrypted` cast, but
 *     note that breaks native SQL uniqueness/LIKE search and would need
 *     an application-level uniqueness check instead.
 *   - Nullable: existing employee records are not required to backfill
 *     immediately.
 *   - Unique: two employees can never share the same NIC — this is a
 *     genuine data-integrity guarantee (an NIC identifies exactly one
 *     person), not just a UI nicety, so it is a DB-level constraint.
 *   - Application layer masks this value in list views (see
 *     Employee::getMaskedNicAttribute()) — full NIC is shown only on the
 *     single-employee edit form, not in index/table listings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'nic_number')) {
                $table->string('nic_number', 12)->nullable()->unique()->after('pay_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'nic_number')) {
                $table->dropUnique(['nic_number']);
                $table->dropColumn('nic_number');
            }
        });
    }
};
