<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roster_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees');
            $table->date('available_date');
            $table->time('available_from')->nullable();
            $table->time('available_to')->nullable();
            $table->string('availability_type', 30)->default('available');
            $table->string('preferred_shift', 30)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->unique(['employee_id', 'available_date']);
        });

        Schema::create('roster_open_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roster_plan_id')->constrained('roster_plans')->cascadeOnDelete();
            $table->date('duty_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->foreignId('duty_unit_id')->constrained('units');
            $table->foreignId('position_id')->nullable()->constrained('positions');
            $table->string('duty_role', 150)->nullable();
            $table->smallInteger('required_staff')->default(1);
            $table->smallInteger('filled_staff')->default(0);
            $table->string('status', 30)->default('open');
            $table->text('details')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->index(['duty_date', 'duty_unit_id', 'status']);
        });

        Schema::create('roster_shift_swap_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('roster_assignments');
            $table->foreignId('requested_by_employee_id')->constrained('employees');
            $table->foreignId('target_employee_id')->nullable()->constrained('employees');
            $table->foreignId('replacement_assignment_id')->nullable()->constrained('roster_assignments');
            $table->string('swap_type', 30)->default('swap');
            $table->text('reason');
            $table->string('status', 30)->default('pending_peer');
            $table->foreignId('peer_accepted_by')->nullable()->constrained('users');
            $table->timestamp('peer_accepted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
        });

        Schema::create('roster_staffing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units');
            $table->unsignedTinyInteger('day_of_week')->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->foreignId('position_id')->nullable()->constrained('positions');
            $table->foreignId('competency_id')->nullable()->constrained('competencies');
            $table->unsignedSmallInteger('minimum_staff')->default(1);
            $table->boolean('is_hard_stop')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('public_holidays', function (Blueprint $table) {
            $table->id();
            $table->date('holiday_date')->unique();
            $table->string('name', 150);
            $table->string('holiday_type', 30)->default('public');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        DB::table('system_settings')->updateOrInsert(['key' => 'roster.minimum_rest_hours'], [
            'value' => '10', 'type' => 'integer', 'group' => 'roster',
            'label' => 'Minimum Rest Hours',
            'description' => 'Minimum recommended rest period between roster duties. Governance warning only unless policy makes it a hard stop.',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('system_settings')->updateOrInsert(['key' => 'roster.max_weekly_hours'], [
            'value' => '48', 'type' => 'integer', 'group' => 'roster',
            'label' => 'Maximum Weekly Roster Hours',
            'description' => 'Configurable roster warning threshold. Must be aligned with the employment regime applicable to the client.',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('system_settings')->whereIn('key', ['roster.minimum_rest_hours', 'roster.max_weekly_hours'])->delete();
        Schema::dropIfExists('public_holidays');
        Schema::dropIfExists('roster_staffing_rules');
        Schema::dropIfExists('roster_shift_swap_requests');
        Schema::dropIfExists('roster_open_shifts');
        Schema::dropIfExists('roster_availabilities');
    }
};
