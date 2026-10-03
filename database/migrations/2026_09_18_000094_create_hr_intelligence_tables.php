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
        Schema::create('hr_intelligence_status', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->timestamp('last_completed_at')->nullable();
            $table->unsignedInteger('changes_recorded')->default(0);
        });
        DB::table('hr_intelligence_status')->insert(['id' => 1]);

        Schema::create('hr_employee_ownership', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->unique()->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('position_id')->nullable();
            $table->boolean('employee_active');
            $table->jsonb('owner_ids');
            $table->string('fingerprint', 64);
            $table->timestamp('observed_at');
            $table->timestamps();
        });

        Schema::create('hr_ownership_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->string('kind', 40);
            $table->jsonb('previous_state')->nullable();
            $table->jsonb('current_state');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('observed_at');
            $table->index(['employee_id', 'observed_at']);
        });

        Schema::create('hr_reassignment_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('ownership_event_id')->unique()->constrained('hr_ownership_events')->restrictOnDelete();
            $table->string('fingerprint', 64);
            $table->string('status', 30);
            $table->foreignId('proposed_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('accepted_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->jsonb('work_transfer')->nullable();
            $table->timestamps();
            $table->index(['status', 'proposed_user_id']);
            $table->index(['employee_id', 'status']);
        });

        Schema::create('hr_coverage_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('position_id')->constrained()->restrictOnDelete();
            $table->string('kind', 30);
            $table->boolean('is_open')->default(true);
            $table->unsignedInteger('occurrence')->default(1);
            $table->timestamp('opened_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();
            $table->unique(['position_id', 'kind']);
        });

        foreach (['employee_increments', 'retirement_projects'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->foreignId('responsible_user_id')->nullable()->constrained('users')->restrictOnDelete();
            });
        }

        DB::table('nav_items')->insert([
            'section' => 'Workforce',
            'label' => 'HR Intelligence & Reassignments',
            'route_name' => 'hr-intelligence.index',
            'allowed_roles' => json_encode(['super_admin', 'planning_officer', 'admin_group', 'subject_officer']),
            'sort_order' => 6,
            'custom_checks' => json_encode(['canAccessHrIntelligence']),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        throw new RuntimeException(
            'HR intelligence history must be retained. Restore a verified backup to revert this migration.',
        );
    }
};
