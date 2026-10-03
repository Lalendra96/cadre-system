<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Government deployments continue to use the paper-based leave process by default.
        // The digital module remains available for governed opt-in/private-sector deployments.
        DB::table('system_settings')->updateOrInsert(['key' => 'feature_leave_management'], [
            'value' => '0', 'type' => 'boolean', 'group' => 'features',
            'label' => 'Leave Management',
            'description' => 'Disabled by default for government deployments that continue to use the paper-based leave process. Enable only through an authorised governance decision.',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('system_settings')->updateOrInsert(['key' => 'leave_external_process_authoritative'], [
            'value' => '1', 'type' => 'boolean', 'group' => 'governance',
            'label' => 'External/Paper Leave Process Authoritative',
            'description' => 'Indicates that the official paper-based leave process remains authoritative unless the digital leave module is formally adopted.',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach ([
            ['roster.require_employee_acknowledgement','1','boolean','Require Roster Acknowledgement','Require employees to acknowledge published/active roster duties.'],
            ['roster.open_shift_claim_requires_manager_approval','1','boolean','Open Shift Claim Approval','Employee claims remain pending until an authorised manager approves them.'],
            ['roster.amendment_requires_approval','1','boolean','Approved Roster Amendment Approval','Approved/active roster changes require a recorded amendment decision.'],
        ] as [$key,$value,$type,$label,$description]) {
            DB::table('system_settings')->updateOrInsert(['key'=>$key],[
                'value'=>$value,'type'=>$type,'group'=>'roster','label'=>$label,'description'=>$description,
                'created_at'=>now(),'updated_at'=>now(),
            ]);
        }

        Schema::create('roster_open_shift_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roster_open_shift_id')->constrained('roster_open_shifts')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees');
            $table->foreignId('requested_by')->constrained('users');
            $table->text('note')->nullable();
            $table->string('status', 30)->default('pending_manager');
            $table->jsonb('compliance_snapshot')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
            $table->unique(['roster_open_shift_id','employee_id']);
            $table->index(['status','created_at']);
        });

        Schema::create('roster_acknowledgements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roster_assignment_id')->constrained('roster_assignments')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees');
            $table->foreignId('acknowledged_by')->constrained('users');
            $table->timestamp('acknowledged_at');
            $table->string('source_ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();
            $table->unique(['roster_assignment_id','employee_id']);
        });

        Schema::create('roster_amendment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roster_plan_id')->constrained('roster_plans')->cascadeOnDelete();
            $table->foreignId('roster_assignment_id')->nullable()->constrained('roster_assignments');
            $table->string('action', 30); // add, update, replace, cancel, swap
            $table->string('source_type', 40)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->boolean('is_emergency')->default(false);
            $table->jsonb('proposed_values')->nullable();
            $table->text('reason');
            $table->string('status', 30)->default('pending');
            $table->foreignId('requested_by')->constrained('users');
            $table->timestamp('requested_at');
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->unsignedInteger('applied_revision_no')->nullable();
            $table->timestamps();
            $table->index(['roster_plan_id','status']);
        });

        Schema::table('roster_shift_swap_requests', function (Blueprint $table) {
            $table->timestamp('peer_responded_at')->nullable()->after('peer_accepted_at');
            $table->text('peer_response_note')->nullable()->after('peer_responded_at');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('roster_shift_swap_requests')) {
            Schema::table('roster_shift_swap_requests', function (Blueprint $table) {
                $table->dropColumn(['peer_responded_at','peer_response_note']);
            });
        }
        Schema::dropIfExists('roster_amendment_requests');
        Schema::dropIfExists('roster_acknowledgements');
        Schema::dropIfExists('roster_open_shift_claims');
        DB::table('system_settings')->whereIn('key', [
            'leave_external_process_authoritative','roster.require_employee_acknowledgement',
            'roster.open_shift_claim_requires_manager_approval','roster.amendment_requires_approval'
        ])->delete();
    }
};
