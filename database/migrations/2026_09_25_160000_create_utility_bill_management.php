<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utility_bill_responsibility_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_officer_id')->constrained('users');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('reference_no', 100)->nullable();
            $table->text('reason');
            $table->foreignId('assigned_by')->constrained('users');
            $table->timestamp('ended_at')->nullable();
            $table->foreignId('ended_by')->nullable()->constrained('users');
            $table->text('end_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('utility_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('utility_type', 30);
            $table->string('provider_name', 150);
            $table->string('account_no', 100);
            $table->string('meter_no', 100)->nullable();
            $table->string('service_location', 200);
            $table->foreignId('unit_id')->nullable()->constrained('units');
            $table->string('contact_reference', 120)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('disabled_by')->nullable()->constrained('users');
            $table->timestamp('disabled_at')->nullable();
            $table->text('disable_reason')->nullable();
            $table->timestamps();
            $table->unique(['provider_name', 'account_no']);
            $table->index(['utility_type', 'is_active']);
        });

        Schema::create('utility_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('utility_account_id')->constrained('utility_accounts');
            $table->string('bill_reference', 120)->nullable();
            $table->date('billing_period_start');
            $table->date('billing_period_end');
            $table->date('bill_date');
            $table->date('due_date');
            $table->decimal('previous_balance', 14, 2)->default(0);
            $table->decimal('current_charges', 14, 2);
            $table->decimal('adjustments', 14, 2)->default(0);
            $table->decimal('bill_amount', 14, 2);
            $table->decimal('amount_paid', 14, 2)->default(0);
            $table->string('status', 30)->default('unpaid');
            $table->string('consumption_value', 80)->nullable();
            $table->string('consumption_unit', 30)->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->index(['due_date', 'status']);
            $table->index(['utility_account_id', 'billing_period_end']);
        });

        Schema::create('utility_bill_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('utility_bill_id')->constrained('utility_bills');
            $table->date('payment_date');
            $table->decimal('amount', 14, 2);
            $table->string('payment_method', 40);
            $table->string('payment_reference', 120);
            $table->string('voucher_no', 100)->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->timestamps();
            $table->index(['payment_date', 'payment_method']);
        });

        DB::table('system_settings')->updateOrInsert(
            ['key' => 'feature_utility_bills'],
            [
                'value' => 'false', 'type' => 'boolean', 'group' => 'features',
                'label' => 'Utility Bill Management',
                'description' => 'Enable utility account, bill payment monitoring and analytics.',
                'created_at' => now(), 'updated_at' => now(),
            ]
        );
        DB::table('system_settings')->updateOrInsert(
            ['key' => 'utility_bill_due_warning_days'],
            [
                'value' => '7', 'type' => 'integer', 'group' => 'utility_bills',
                'label' => 'Utility bill due warning days',
                'description' => 'Days before due date to highlight bills requiring action.',
                'created_at' => now(), 'updated_at' => now(),
            ]
        );

        if (Schema::hasTable('nav_items')) {
            DB::table('nav_items')->updateOrInsert(
                ['route_name' => 'utility-bills.index'],
                [
                    'section' => 'My Work', 'label' => '💡 Utility Bill Management',
                    'open_in_new_tab' => false,
                    'allowed_roles' => json_encode(['subject_officer', 'admin_group', 'super_admin']),
                    'custom_checks' => null, 'sort_order' => 408, 'is_active' => true,
                    'created_at' => now(), 'updated_at' => now(),
                ]
            );
            DB::table('nav_items')->updateOrInsert(
                ['route_name' => 'utility-bills.admin.index'],
                [
                    'section' => 'Administration', 'label' => '💡 Utility Bill Administration',
                    'open_in_new_tab' => false,
                    'allowed_roles' => json_encode(['super_admin']),
                    'custom_checks' => null, 'sort_order' => 448, 'is_active' => true,
                    'created_at' => now(), 'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('nav_items')) {
            DB::table('nav_items')->whereIn('route_name', ['utility-bills.index', 'utility-bills.admin.index'])->delete();
        }
        if (Schema::hasTable('system_settings')) {
            DB::table('system_settings')->whereIn('key', ['feature_utility_bills', 'utility_bill_due_warning_days'])->delete();
        }
        Schema::dropIfExists('utility_bill_payments');
        Schema::dropIfExists('utility_bills');
        Schema::dropIfExists('utility_accounts');
        Schema::dropIfExists('utility_bill_responsibility_assignments');
    }
};
