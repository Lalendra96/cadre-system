<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $edition = strtolower((string) (DB::table('system_settings')->where('key','client_profile_edition')->value('value') ?: 'Government / Custom'));
        $private = str_contains($edition,'private') || str_contains($edition,'gp') || str_contains($edition,'clinic') || str_contains($edition,'commercial');

        foreach ([
            ['feature_biometric_attendance','Biometric / Multi-Punch Attendance Adapters'],
            ['feature_leave_automation','Leave Accrual / Carry-Forward Support'],
            ['feature_contract_lifecycle','Contract Renewal / Probation Workflow'],
            ['feature_locum_pool','Locum Pool / Booking / Reconciliation'],
            ['feature_demand_forecasting','Workforce Demand Forecasting'],
            ['feature_roster_optimizer','Advanced Roster Optimization'],
        ] as [$key,$label]) {
            DB::table('system_settings')->updateOrInsert(['key'=>$key],[
                'value'=>$private?'1':'0','type'=>'boolean','group'=>'features','label'=>$label,
                'description'=>'Private-sector support capability. Government deployments remain disabled by default unless explicitly adopted through governance.',
                'created_at'=>now(),'updated_at'=>now(),
            ]);
        }
        DB::table('system_settings')->updateOrInsert(['key'=>'leave_automation_support_only'],[
            'value'=>'1','type'=>'boolean','group'=>'governance','label'=>'Leave Automation Is Support-Only',
            'description'=>'Accrual/carry-forward calculations are decision support and do not become the official leave record unless the organisation formally adopts the digital leave process.',
            'created_at'=>now(),'updated_at'=>now(),
        ]);
        DB::table('system_settings')->updateOrInsert(['key'=>'attendance.biometric_templates_external'],[
            'value'=>'1','type'=>'boolean','group'=>'governance','label'=>'Biometric Templates Remain External',
            'description'=>'Carder must not ingest fingerprint/face templates. It stores attendance punch metadata and external references only.',
            'created_at'=>now(),'updated_at'=>now(),
        ]);

        Schema::create('attendance_devices', function (Blueprint $table) {
            $table->id(); $table->string('name',120); $table->string('vendor',60); $table->string('adapter',60);
            $table->string('base_url',500)->nullable(); $table->text('encrypted_config')->nullable(); $table->boolean('is_active')->default(true);
            $table->timestamp('last_sync_at')->nullable(); $table->string('last_sync_status',40)->nullable(); $table->text('last_sync_message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users'); $table->timestamps();
        });
        Schema::create('attendance_device_employee_maps', function (Blueprint $table) {
            $table->id(); $table->foreignId('attendance_device_id')->constrained('attendance_devices')->cascadeOnDelete();
            $table->string('external_user_ref',120); $table->foreignId('employee_id')->constrained('employees'); $table->boolean('is_active')->default(true); $table->timestamps();
            $table->unique(['attendance_device_id','external_user_ref']);
        });
        Schema::create('attendance_punches', function (Blueprint $table) {
            $table->id(); $table->foreignId('attendance_device_id')->nullable()->constrained('attendance_devices'); $table->foreignId('employee_id')->nullable()->constrained('employees');
            $table->string('external_user_ref',120)->nullable(); $table->timestamp('punched_at'); $table->string('punch_type',30)->default('unknown');
            $table->string('source_reference',180)->nullable(); $table->string('event_hash',64)->unique(); $table->jsonb('raw_payload')->nullable();
            $table->string('processing_status',30)->default('pending'); $table->text('processing_note')->nullable(); $table->timestamp('processed_at')->nullable(); $table->timestamps();
            $table->index(['employee_id','punched_at']);
        });
        Schema::create('attendance_import_batches', function (Blueprint $table) {
            $table->id(); $table->foreignId('attendance_device_id')->nullable()->constrained('attendance_devices'); $table->string('source_type',30);
            $table->string('source_name',255)->nullable(); $table->integer('total_events')->default(0); $table->integer('accepted_events')->default(0); $table->integer('rejected_events')->default(0);
            $table->string('status',30)->default('processing'); $table->jsonb('errors')->nullable(); $table->foreignId('imported_by')->nullable()->constrained('users'); $table->timestamps();
        });

        Schema::create('leave_accrual_policies', function (Blueprint $table) {
            $table->id(); $table->foreignId('leave_type_id')->constrained('leave_types'); $table->string('contract_type',40)->nullable();
            $table->string('accrual_frequency',20)->default('monthly'); $table->decimal('accrual_amount',7,2)->default(0); $table->decimal('annual_cap',7,2)->nullable();
            $table->boolean('carry_forward_enabled')->default(false); $table->decimal('carry_forward_cap',7,2)->default(0); $table->boolean('support_only')->default(true);
            $table->date('effective_from'); $table->date('effective_to')->nullable(); $table->boolean('is_active')->default(true); $table->foreignId('updated_by')->nullable()->constrained('users'); $table->timestamps();
        });
        Schema::create('leave_accrual_runs', function (Blueprint $table) {
            $table->id(); $table->smallInteger('year'); $table->unsignedTinyInteger('month')->nullable(); $table->string('run_type',30); $table->string('status',30)->default('preview');
            $table->jsonb('summary')->nullable(); $table->foreignId('run_by')->constrained('users'); $table->timestamp('applied_at')->nullable(); $table->text('governance_note')->nullable(); $table->timestamps();
        });

        Schema::create('contract_probation_reviews', function (Blueprint $table) {
            $table->id(); $table->foreignId('employee_contract_id')->constrained('employee_contracts')->cascadeOnDelete(); $table->date('review_due_date');
            $table->string('status',30)->default('pending'); $table->string('recommendation',30)->nullable(); $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users'); $table->timestamp('reviewed_at')->nullable(); $table->date('extended_to')->nullable(); $table->timestamps();
        });
        Schema::create('contract_renewal_requests', function (Blueprint $table) {
            $table->id(); $table->foreignId('employee_contract_id')->constrained('employee_contracts')->cascadeOnDelete(); $table->date('proposed_start_date'); $table->date('proposed_end_date')->nullable();
            $table->jsonb('proposed_terms')->nullable(); $table->string('status',30)->default('pending'); $table->text('reason')->nullable();
            $table->foreignId('requested_by')->constrained('users'); $table->foreignId('reviewed_by')->nullable()->constrained('users'); $table->timestamp('reviewed_at')->nullable(); $table->text('review_note')->nullable();
            $table->foreignId('new_contract_id')->nullable()->constrained('employee_contracts'); $table->timestamps();
        });

        Schema::create('locum_pool_profiles', function (Blueprint $table) {
            $table->id(); $table->foreignId('employee_id')->unique()->constrained('employees'); $table->string('pool_status',30)->default('available');
            $table->decimal('preferred_hourly_rate',14,2)->nullable(); $table->decimal('preferred_session_rate',14,2)->nullable(); $table->jsonb('preferred_unit_ids')->nullable();
            $table->date('available_from')->nullable(); $table->date('available_to')->nullable(); $table->text('notes')->nullable(); $table->boolean('is_active')->default(true); $table->foreignId('updated_by')->nullable()->constrained('users'); $table->timestamps();
        });
        Schema::create('locum_bookings', function (Blueprint $table) {
            $table->id(); $table->foreignId('employee_id')->constrained('employees'); $table->foreignId('unit_id')->constrained('units'); $table->foreignId('contract_id')->nullable()->constrained('employee_contracts');
            $table->date('booking_date'); $table->time('start_time'); $table->time('end_time'); $table->string('status',30)->default('requested'); $table->text('request_reason')->nullable();
            $table->foreignId('requested_by')->constrained('users'); $table->foreignId('accepted_by')->nullable()->constrained('users'); $table->timestamp('accepted_at')->nullable(); $table->foreignId('approved_by')->nullable()->constrained('users'); $table->timestamp('approved_at')->nullable();
            $table->foreignId('locum_session_id')->nullable()->constrained('locum_sessions'); $table->timestamps();
        });
        Schema::table('locum_sessions', function (Blueprint $table) {
            $table->foreignId('booking_id')->nullable()->constrained('locum_bookings'); $table->string('reconciliation_status',30)->default('unreconciled'); $table->text('reconciliation_note')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users'); $table->timestamp('verified_at')->nullable(); $table->string('external_payment_reference',120)->nullable();
        });

        Schema::create('workforce_demand_rules', function (Blueprint $table) {
            $table->id(); $table->foreignId('unit_id')->constrained('units'); $table->foreignId('position_id')->nullable()->constrained('positions');
            $table->string('demand_metric',40); $table->decimal('units_per_fte',10,2); $table->decimal('minimum_fte',8,2)->default(0); $table->boolean('is_active')->default(true); $table->foreignId('updated_by')->nullable()->constrained('users'); $table->timestamps();
            $table->unique(['unit_id','position_id','demand_metric']);
        });
        Schema::create('workforce_demand_inputs', function (Blueprint $table) {
            $table->id(); $table->foreignId('unit_id')->constrained('units'); $table->date('period_date'); $table->string('metric',40); $table->decimal('value',14,2);
            $table->string('source',40)->default('manual'); $table->string('source_reference',180)->nullable(); $table->foreignId('recorded_by')->nullable()->constrained('users'); $table->timestamps();
            $table->unique(['unit_id','period_date','metric','source']);
        });

        if (Schema::hasTable('nav_items')) {
            $roles = json_encode(['super_admin','admin_group','planning_officer','unit_manager']);
            foreach ([
                ['Workforce','🧬 Attendance Integrations','workforce.attendance-integration.index',22],
                ['Workforce','🌱 Leave Accrual Support','workforce.leave-automation.index',32],
                ['Workforce','🔁 Contract Lifecycle','workforce.contract-lifecycle.index',52],
                ['Workforce','🩺 Locum Pool & Booking','workforce.locum-pool.index',72],
                ['Workforce','📊 Demand Forecast','workforce.demand.index',82],
                ['Workforce','✨ Roster Optimizer','roster.optimizer.index',14],
            ] as [$section,$label,$route,$sort]) {
                DB::table('nav_items')->updateOrInsert(['route_name'=>$route],[
                    'section'=>$section,'label'=>$label,'allowed_roles'=>$roles,'custom_checks'=>json_encode([]),'sort_order'=>$sort,'is_active'=>true,'open_in_new_tab'=>false,'updated_at'=>now(),'created_at'=>now(),
                ]);
            }
        }

        foreach ([
            ['roster.optimizer.fairness_weight','30','integer','Fairness Weight'],
            ['roster.optimizer.preference_weight','20','integer','Preference Weight'],
            ['roster.optimizer.workload_weight','25','integer','Workload Weight'],
            ['roster.optimizer.skill_weight','25','integer','Skill/Compliance Weight'],
        ] as [$key,$value,$type,$label]) {
            DB::table('system_settings')->updateOrInsert(['key'=>$key],[ 'value'=>$value,'type'=>$type,'group'=>'roster','label'=>$label,'description'=>'Decision-support weighting for roster suggestions; human approval remains mandatory.','created_at'=>now(),'updated_at'=>now() ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('locum_sessions')) Schema::table('locum_sessions', function (Blueprint $table) {
            foreach (['booking_id','verified_by'] as $fk) { try { $table->dropForeign([$fk]); } catch (\Throwable $e) {} }
            $table->dropColumn(['booking_id','reconciliation_status','reconciliation_note','verified_by','verified_at','external_payment_reference']);
        });
        foreach (['workforce_demand_inputs','workforce_demand_rules','locum_bookings','locum_pool_profiles','contract_renewal_requests','contract_probation_reviews','leave_accrual_runs','leave_accrual_policies','attendance_import_batches','attendance_punches','attendance_device_employee_maps','attendance_devices'] as $t) Schema::dropIfExists($t);
        DB::table('system_settings')->whereIn('key',['feature_biometric_attendance','feature_leave_automation','feature_contract_lifecycle','feature_locum_pool','feature_demand_forecasting','feature_roster_optimizer','leave_automation_support_only','attendance.biometric_templates_external','roster.optimizer.fairness_weight','roster.optimizer.preference_weight','roster.optimizer.workload_weight','roster.optimizer.skill_weight'])->delete();
    }
};
