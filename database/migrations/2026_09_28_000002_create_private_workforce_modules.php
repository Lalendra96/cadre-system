<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cost_centres', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 150);
            $table->foreignId('unit_id')->nullable()->constrained('units');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('disabled_by')->nullable()->constrained('users');
            $table->timestamp('disabled_at')->nullable();
            $table->text('disable_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('attendance_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 100);
            $table->boolean('requires_device_reference')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees');
            $table->date('work_date');
            $table->timestamp('clock_in_at')->nullable();
            $table->timestamp('clock_out_at')->nullable();
            $table->string('status', 30)->default('present');
            $table->foreignId('attendance_method_id')->nullable()->constrained('attendance_methods');
            $table->string('device_reference', 120)->nullable();
            $table->foreignId('unit_id')->nullable()->constrained('units');
            $table->foreignId('roster_assignment_id')->nullable()->constrained('roster_assignments');
            $table->integer('worked_minutes')->default(0);
            $table->integer('late_minutes')->default(0);
            $table->integer('early_departure_minutes')->default(0);
            $table->boolean('is_exception')->default(false);
            $table->text('exception_reason')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users');
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'work_date']);
        });

        Schema::create('attendance_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_record_id')->constrained('attendance_records');
            $table->foreignId('employee_id')->constrained('employees');
            $table->timestamp('requested_clock_in_at')->nullable();
            $table->timestamp('requested_clock_out_at')->nullable();
            $table->text('reason');
            $table->string('status', 30)->default('pending');
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
        });

        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 120);
            $table->decimal('annual_entitlement', 6, 2)->default(0);
            $table->boolean('is_paid')->default(true);
            $table->boolean('allow_half_day')->default(true);
            $table->boolean('allow_carry_forward')->default(false);
            $table->decimal('max_carry_forward', 6, 2)->default(0);
            $table->boolean('requires_attachment')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('employee_leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees');
            $table->foreignId('leave_type_id')->constrained('leave_types');
            $table->smallInteger('year');
            $table->decimal('opening_balance', 7, 2)->default(0);
            $table->decimal('accrued', 7, 2)->default(0);
            $table->decimal('used', 7, 2)->default(0);
            $table->decimal('adjusted', 7, 2)->default(0);
            $table->timestamps();
            $table->unique(['employee_id', 'leave_type_id', 'year']);
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees');
            $table->foreignId('leave_type_id')->constrained('leave_types');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('days', 6, 2);
            $table->string('portion', 20)->default('full');
            $table->text('reason')->nullable();
            $table->string('attachment_path', 500)->nullable();
            $table->string('status', 30)->default('pending');
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users');
            $table->timestamp('cancelled_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'start_date', 'end_date']);
        });

        Schema::create('overtime_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees');
            $table->date('work_date');
            $table->foreignId('attendance_record_id')->nullable()->constrained('attendance_records');
            $table->foreignId('roster_assignment_id')->nullable()->constrained('roster_assignments');
            $table->string('overtime_type', 30)->default('regular');
            $table->integer('minutes_requested');
            $table->integer('minutes_approved')->nullable();
            $table->decimal('rate_multiplier', 6, 3)->default(1.5);
            $table->text('reason')->nullable();
            $table->string('status', 30)->default('pending');
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->boolean('included_in_payroll')->default(false);
            $table->timestamps();
        });

        Schema::create('employee_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees');
            $table->string('contract_type', 40);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('probation_end_date')->nullable();
            $table->decimal('basic_salary', 14, 2)->nullable();
            $table->string('pay_frequency', 30)->default('monthly');
            $table->decimal('hourly_rate', 14, 2)->nullable();
            $table->decimal('session_rate', 14, 2)->nullable();
            $table->decimal('patient_rate', 14, 2)->nullable();
            $table->decimal('revenue_share_percent', 7, 3)->nullable();
            $table->foreignId('cost_centre_id')->nullable()->constrained('cost_centres');
            $table->string('status', 30)->default('active');
            $table->string('reference_no', 100)->nullable();
            $table->text('terms_summary')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['employee_id', 'status']);
        });

        Schema::create('employee_payroll_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->unique()->constrained('employees');
            $table->text('bank_name_encrypted')->nullable();
            $table->text('bank_branch_encrypted')->nullable();
            $table->text('bank_account_no_encrypted')->nullable();
            $table->string('tax_reference', 100)->nullable();
            $table->string('epf_member_no', 100)->nullable();
            $table->boolean('epf_applicable')->default(true);
            $table->boolean('etf_applicable')->default(true);
            $table->boolean('apit_applicable')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('statutory_rate_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->decimal('epf_employee_percent', 6, 3)->default(8.000);
            $table->decimal('epf_employer_percent', 6, 3)->default(12.000);
            $table->decimal('etf_employer_percent', 6, 3)->default(3.000);
            $table->jsonb('apit_rules')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('year');
            $table->smallInteger('month');
            $table->string('name', 120);
            $table->string('status', 30)->default('draft');
            $table->foreignId('statutory_rate_profile_id')->nullable()->constrained('statutory_rate_profiles');
            $table->foreignId('prepared_by')->constrained('users');
            $table->foreignId('checked_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('checked_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->timestamps();
            $table->unique(['year', 'month']);
        });

        Schema::create('payroll_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees');
            $table->foreignId('contract_id')->nullable()->constrained('employee_contracts');
            $table->foreignId('cost_centre_id')->nullable()->constrained('cost_centres');
            $table->decimal('basic_salary', 14, 2)->default(0);
            $table->decimal('allowances', 14, 2)->default(0);
            $table->decimal('overtime_amount', 14, 2)->default(0);
            $table->decimal('locum_amount', 14, 2)->default(0);
            $table->decimal('gross_pay', 14, 2)->default(0);
            $table->decimal('epf_employee', 14, 2)->default(0);
            $table->decimal('epf_employer', 14, 2)->default(0);
            $table->decimal('etf_employer', 14, 2)->default(0);
            $table->decimal('apit', 14, 2)->default(0);
            $table->decimal('other_deductions', 14, 2)->default(0);
            $table->decimal('net_pay', 14, 2)->default(0);
            $table->jsonb('calculation_snapshot');
            $table->timestamps();
            $table->unique(['payroll_run_id', 'employee_id']);
        });

        Schema::create('payroll_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees');
            $table->string('type', 30);
            $table->string('code', 50);
            $table->string('description', 180);
            $table->decimal('amount', 14, 2);
            $table->smallInteger('year');
            $table->smallInteger('month');
            $table->boolean('recurring')->default(false);
            $table->string('status', 30)->default('approved');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('locum_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees');
            $table->foreignId('contract_id')->nullable()->constrained('employee_contracts');
            $table->foreignId('unit_id')->constrained('units');
            $table->foreignId('cost_centre_id')->nullable()->constrained('cost_centres');
            $table->date('session_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('patients_seen')->default(0);
            $table->decimal('revenue_amount', 14, 2)->default(0);
            $table->string('calculation_method', 30)->default('session');
            $table->decimal('calculated_amount', 14, 2)->default(0);
            $table->string('status', 30)->default('draft');
            $table->foreignId('recorded_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->boolean('included_in_payroll')->default(false);
            $table->timestamps();
        });

        Schema::create('self_service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees');
            $table->string('request_type', 50);
            $table->jsonb('payload');
            $table->string('status', 30)->default('pending');
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
        });

        Schema::create('workforce_policy_rules', function (Blueprint $table) {
            $table->id();
            $table->string('module', 40);
            $table->string('code', 80);
            $table->string('label', 180);
            $table->jsonb('rule_config');
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->unique(['module', 'code']);
        });

        DB::table('system_settings')->updateOrInsert(['key' => 'workforce.super_admin_roles'], [
            'value' => json_encode(['super_admin','admin_group','admin']), 'type' => 'json', 'group' => 'security',
            'label' => 'Workforce Super Admin Roles', 'description' => 'Roles permitted to manage workforce feature toggles and sensitive module configuration.',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $features = [
            'feature_attendance' => 'Attendance',
            'feature_leave_management' => 'Leave Management',
            'feature_overtime' => 'Overtime',
            'feature_contracts' => 'Contracts',
            'feature_payroll' => 'Payroll',
            'feature_employee_self_service' => 'Employee Self-Service',
            'feature_locum_sessions' => 'Locum / Session Payments',
            'feature_cost_centre_analytics' => 'Cost Centre Analytics',
        ];
        foreach ($features as $settingKey => $label) {
            DB::table('system_settings')->updateOrInsert(['key' => $settingKey], [
                'value' => in_array($settingKey, ['feature_payroll', 'feature_leave_management'], true) ? '0' : '1', 'type' => 'boolean', 'group' => 'features', 'label' => $label,
                'description' => "Enable or disable {$label} workforce functionality.",
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        foreach ([
            ['ATT_MANUAL','Manual Entry',false], ['ATT_QR','QR Code',true],
            ['ATT_BIO','Biometric',true], ['ATT_IMPORT','Bulk Import',true]
        ] as [$code,$name,$requires]) {
            DB::table('attendance_methods')->insert(['code'=>$code,'name'=>$name,'requires_device_reference'=>$requires,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        }
        foreach ([
            ['ANNUAL','Annual Leave',14,true,true,7], ['CASUAL','Casual Leave',7,true,false,0],
            ['MEDICAL','Medical Leave',7,true,false,0], ['NOPAY','No Pay Leave',0,false,false,0]
        ] as [$code,$name,$ent,$paid,$carry,$max]) {
            DB::table('leave_types')->insert(['code'=>$code,'name'=>$name,'annual_entitlement'=>$ent,'is_paid'=>$paid,'allow_half_day'=>true,'allow_carry_forward'=>$carry,'max_carry_forward'=>$max,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        }
        DB::table('statutory_rate_profiles')->insert([
            'name'=>'Sri Lanka statutory defaults - verify before production', 'effective_from'=>'2026-01-01',
            'epf_employee_percent'=>8, 'epf_employer_percent'=>12, 'etf_employer_percent'=>3,
            'apit_rules'=>json_encode(['mode'=>'configure_from_current_IRD_tables','automatic_calculation'=>false]),
            'is_active'=>true, 'created_at'=>now(), 'updated_at'=>now(),
        ]);
    }

    public function down(): void
    {
        DB::table('system_settings')->whereIn('key', ['feature_attendance','feature_leave_management','feature_overtime','feature_contracts','feature_payroll','feature_employee_self_service','feature_locum_sessions','feature_cost_centre_analytics'])->delete();
        DB::table('system_settings')->where('key', 'workforce.super_admin_roles')->delete();
        foreach (['workforce_policy_rules','self_service_requests','locum_sessions','payroll_adjustments','payroll_lines','payroll_runs','statutory_rate_profiles','employee_payroll_profiles','employee_contracts','overtime_requests','leave_requests','employee_leave_balances','leave_types','attendance_corrections','attendance_records','attendance_methods','cost_centres'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
