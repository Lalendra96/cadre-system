<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            foreach ([
                'name', 'pay_no', 'service_file_no', 'nic_number', 'wop_number',
                'professional_registration_no', 'email', 'whatsapp_mobile',
            ] as $field) {
                $table->char($field.'_hmac', 64)->nullable();
            }
        });

        Schema::table('interns', function (Blueprint $table) {
            foreach (['name', 'nic_number', 'mobile_number'] as $field) {
                $table->char($field.'_hmac', 64)->nullable();
            }
        });

        // Authenticated ciphertext is much longer than the original values.
        // PostgreSQL TEXT avoids truncation and does not impose a meaningful
        // performance penalty for these short encrypted values.
        if (DB::getDriverName() === 'pgsql') {
            foreach ([
                'name', 'pay_no', 'service_file_no', 'nic_number', 'wop_number',
                'professional_registration_no', 'email', 'whatsapp_mobile',
                'permanent_address', 'current_address', 'emergency_contact_name',
                'emergency_contact_relationship', 'emergency_contact_mobile',
                'confirmation_reference_no', 'notes',
            ] as $field) {
                DB::statement("ALTER TABLE employees ALTER COLUMN {$field} TYPE TEXT");
            }

            foreach (['name', 'nic_number', 'mobile_number'] as $field) {
                DB::statement("ALTER TABLE interns ALTER COLUMN {$field} TYPE TEXT");
            }

            foreach ([
                'transfer_records' => ['employee_name'],
                'car_pass_requests' => ['employee_name_snapshot', 'pay_no_snapshot'],
                'employee_change_requests' => ['old_value', 'requested_value'],
            ] as $table => $fields) {
                if (! Schema::hasTable($table)) {
                    continue;
                }
                foreach ($fields as $field) {
                    if (Schema::hasColumn($table, $field)) {
                        DB::statement("ALTER TABLE {$table} ALTER COLUMN {$field} TYPE TEXT");
                    }
                }
            }
        }

        foreach ([
            'name', 'pay_no', 'service_file_no', 'nic_number', 'wop_number',
            'professional_registration_no', 'email', 'whatsapp_mobile',
        ] as $field) {
            DB::statement("CREATE INDEX IF NOT EXISTS employees_{$field}_hmac_idx ON employees ({$field}_hmac)");
        }

        foreach (['name', 'nic_number', 'mobile_number'] as $field) {
            DB::statement("CREATE INDEX IF NOT EXISTS interns_{$field}_hmac_idx ON interns ({$field}_hmac)");
        }
    }

    public function down(): void
    {
        foreach ([
            'name', 'pay_no', 'service_file_no', 'nic_number', 'wop_number',
            'professional_registration_no', 'email', 'whatsapp_mobile',
        ] as $field) {
            DB::statement("DROP INDEX IF EXISTS employees_{$field}_hmac_idx");
        }
        foreach (['name', 'nic_number', 'mobile_number'] as $field) {
            DB::statement("DROP INDEX IF EXISTS interns_{$field}_hmac_idx");
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'name_hmac', 'pay_no_hmac', 'service_file_no_hmac', 'nic_number_hmac',
                'wop_number_hmac', 'professional_registration_no_hmac', 'email_hmac',
                'whatsapp_mobile_hmac',
            ]);
        });

        Schema::table('interns', function (Blueprint $table) {
            $table->dropColumn(['name_hmac', 'nic_number_hmac', 'mobile_number_hmac']);
        });
    }
};
