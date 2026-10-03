<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roster_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units');
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('assignment_type', 50)->default('regular');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->unique(['unit_id', 'name']);
        });

        Schema::create('roster_template_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roster_template_id')->constrained('roster_templates')->cascadeOnDelete();
            $table->string('label', 120);
            $table->time('start_time');
            $table->time('end_time');
            $table->foreignId('default_duty_unit_id')->nullable()->constrained('units');
            $table->foreignId('position_id')->nullable()->constrained('positions');
            $table->string('duty_role', 150)->nullable();
            $table->text('additional_details')->nullable();
            $table->smallInteger('required_staff')->default(1);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('roster_plans', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no', 40)->unique();
            $table->foreignId('unit_id')->constrained('units');
            $table->foreignId('roster_template_id')->nullable()->constrained('roster_templates');
            $table->string('title', 180);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 40)->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('submitted_by')->nullable()->constrained('users');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('started_by')->nullable()->constrained('users');
            $table->timestamp('started_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users');
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users');
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->unsignedInteger('revision_no')->default(1);
            $table->timestamps();
            $table->index(['unit_id', 'status', 'start_date']);
        });

        Schema::create('roster_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roster_plan_id')->constrained('roster_plans')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees');
            $table->date('duty_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->foreignId('home_unit_id')->constrained('units');
            $table->foreignId('duty_unit_id')->constrained('units');
            $table->foreignId('position_id')->nullable()->constrained('positions');
            $table->string('duty_role', 150)->nullable();
            $table->string('coverage_type', 40)->default('home_unit');
            $table->string('location', 150)->nullable();
            $table->text('details')->nullable();
            $table->string('status', 30)->default('planned');
            $table->foreignId('assigned_by')->constrained('users');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'duty_date']);
            $table->index(['duty_unit_id', 'duty_date']);
        });

        Schema::create('roster_workflows', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->foreignId('unit_id')->nullable()->constrained('units');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('roster_workflow_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roster_workflow_id')->constrained('roster_workflows')->cascadeOnDelete();
            $table->smallInteger('level_no');
            $table->string('label', 120);
            $table->string('approver_type', 30)->default('category');
            $table->jsonb('approver_config')->nullable();
            $table->boolean('is_required')->default(true);
            $table->boolean('allow_return')->default(true);
            $table->boolean('notify_email')->default(false);
            $table->timestamps();
            $table->unique(['roster_workflow_id', 'level_no']);
        });

        Schema::create('roster_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roster_plan_id')->constrained('roster_plans')->cascadeOnDelete();
            $table->foreignId('roster_workflow_level_id')->constrained('roster_workflow_levels');
            $table->smallInteger('level_no');
            $table->string('status', 30)->default('pending');
            $table->foreignId('acted_by')->nullable()->constrained('users');
            $table->timestamp('acted_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->unique(['roster_plan_id', 'level_no']);
        });

        Schema::create('roster_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roster_plan_id')->constrained('roster_plans')->cascadeOnDelete();
            $table->unsignedInteger('revision_no');
            $table->jsonb('snapshot');
            $table->string('reason', 500)->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['roster_plan_id', 'revision_no']);
        });

        DB::table('system_settings')->updateOrInsert(
            ['key' => 'feature_roster'],
            [
                'value' => '1', 'type' => 'boolean', 'group' => 'features',
                'label' => 'Roster Module',
                'description' => 'Enable or disable roster planning, approvals and duty assignments.',
                'created_at' => now(), 'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('system_settings')->where('key', 'feature_roster')->delete();
        Schema::dropIfExists('roster_revisions');
        Schema::dropIfExists('roster_approvals');
        Schema::dropIfExists('roster_workflow_levels');
        Schema::dropIfExists('roster_workflows');
        Schema::dropIfExists('roster_assignments');
        Schema::dropIfExists('roster_plans');
        Schema::dropIfExists('roster_template_slots');
        Schema::dropIfExists('roster_templates');
    }
};
