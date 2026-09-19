<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'incident_reports',
            function (Blueprint $table): void {
                $table->id();
                $table->string('reference_no', 40)
                    ->nullable()
                    ->unique();

                $table->string('title', 200);
                $table->string('category', 60);
                $table->string('severity', 20)
                    ->default('medium');
                $table->string('status', 40)
                    ->default('open');

                $table->text('description');
                $table->text('impact_summary')->nullable();
                $table->text('immediate_action')->nullable();
                $table->text('root_cause')->nullable();
                $table->text('corrective_action')->nullable();
                $table->text('preventive_action')->nullable();

                $table->timestamp('detected_at')->nullable();
                $table->unsignedInteger('affected_records_count')
                    ->nullable();

                $table->foreignId('affected_employee_id')
                    ->nullable()
                    ->constrained('employees')
                    ->nullOnDelete();

                $table->string('related_model_type', 180)
                    ->nullable();
                $table->unsignedBigInteger('related_model_id')
                    ->nullable();
                $table->string('related_reference', 180)
                    ->nullable();

                $table->foreignId('reported_by')
                    ->constrained('users');
                $table->timestamp('reported_at')
                    ->useCurrent();

                $table->foreignId('assigned_to')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->date('target_resolution_date')
                    ->nullable();

                $table->foreignId('resolved_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestamp('resolved_at')->nullable();

                $table->foreignId('closed_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestamp('closed_at')->nullable();
                $table->text('closure_notes')->nullable();

                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index([
                    'status',
                    'severity',
                ]);

                $table->index([
                    'category',
                    'reported_at',
                ]);

                $table->index([
                    'reported_by',
                    'status',
                ]);

                $table->index([
                    'assigned_to',
                    'status',
                ]);
            }
        );

        Schema::create(
            'incident_events',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('incident_report_id')
                    ->constrained('incident_reports')
                    ->cascadeOnDelete();

                $table->string('event_type', 50);
                $table->string('from_status', 40)->nullable();
                $table->string('to_status', 40)->nullable();
                $table->text('comment')->nullable();
                $table->json('metadata')->nullable();

                $table->foreignId('actor_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamp('created_at')->useCurrent();

                $table->index([
                    'incident_report_id',
                    'created_at',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_events');
        Schema::dropIfExists('incident_reports');
    }
};
