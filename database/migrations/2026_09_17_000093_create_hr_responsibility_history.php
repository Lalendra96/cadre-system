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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('hr_scope_configured')->default(false);
        });

        Schema::create('hr_responsibilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('position_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('kind', 20);
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->foreignId('ended_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('end_reason')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'starts_on', 'ends_on']);
            $table->index(['position_id', 'starts_on', 'ends_on']);
        });

        Schema::create('hr_handovers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hr_responsibility_id')->constrained()->restrictOnDelete();
            $table->foreignId('to_user_id')->constrained('users')->restrictOnDelete();
            $table->date('effective_on');
            $table->string('status', 20)->default('pending');
            $table->text('notes');
            $table->text('outstanding_actions');
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('accepted_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table
                ->foreignId('new_responsibility_id')
                ->nullable()
                ->constrained('hr_responsibilities')
                ->restrictOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamps();
            $table->index(['to_user_id', 'status']);
        });

        Schema::create('hr_reminder_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('event_key', 150);
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->timestamp('created_at');
            $table->unique(['event_key', 'user_id']);
        });

        if (! DB::table('nav_items')->where('route_name', 'hr-responsibilities.index')->exists()) {
            DB::table('nav_items')->insert([
                'section' => 'Workforce',
                'label' => 'HR Responsibilities',
                'route_name' => 'hr-responsibilities.index',
                'allowed_roles' => json_encode(['super_admin', 'planning_officer', 'admin_group', 'subject_officer']),
                'sort_order' => 5,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // The pivot only proves ownership from its recorded assignment date.
        DB::table('hr_position_user')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('hr_responsibilities')->insert([
                        'position_id' => $row->position_id,
                        'user_id' => $row->user_id,
                        'kind' => 'permanent',
                        'starts_on' => substr($row->assigned_at ?? ($row->created_at ?? now()->toDateString()), 0, 10),
                        'assigned_by' => $row->assigned_by,
                        'notes' => 'Migrated from the v6 HR position assignment. Earlier ownership is not inferred.',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    DB::table('users')
                        ->where('id', $row->user_id)
                        ->update(['hr_scope_configured' => true]);
                }
            });
    }

    public function down(): void
    {
        // Responsibility history must not be erased by an accidental rollback.
        throw new RuntimeException(
            'This migration preserves HR ownership history. Restore a verified backup to revert it.',
        );
    }
};
