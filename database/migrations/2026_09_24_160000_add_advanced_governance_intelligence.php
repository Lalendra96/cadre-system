<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('governance_rule_provisions')) {
            Schema::create('governance_rule_provisions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('rule_version_id')->constrained('governance_rule_versions')->cascadeOnDelete();
                $table->string('provision_code', 100);
                $table->string('subject_area', 80);
                $table->jsonb('conditions')->default('{}');
                $table->jsonb('outcomes')->default('{}');
                $table->jsonb('required_evidence')->default('[]');
                $table->boolean('requires_human_decision')->default(true);
                $table->text('plain_language_summary')->nullable();
                $table->timestamps();
                $table->unique(['rule_version_id', 'provision_code']);
                $table->index('subject_area');
            });
        }

        if (! Schema::hasTable('administrative_eligibility_findings')) {
            Schema::create('administrative_eligibility_findings', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->string('finding_type', 60);
                $table->date('target_date')->nullable();
                $table->string('status', 30)->default('approaching');
                $table->string('severity', 20)->default('info');
                $table->jsonb('facts')->default('{}');
                $table->jsonb('missing_evidence')->default('[]');
                $table->jsonb('matched_rules')->default('[]');
                $table->text('explanation');
                $table->boolean('requires_human_decision')->default(true);
                $table->timestamp('evaluated_at');
                $table->timestamps();
                $table->unique(['employee_id', 'finding_type', 'target_date']);
                $table->index(['finding_type', 'status', 'target_date']);
            });
        }

        if (! Schema::hasTable('external_hr_sources')) {
            Schema::create('external_hr_sources', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 60)->unique();
                $table->string('name', 160);
                $table->string('source_type', 30);
                $table->string('base_url', 500)->nullable();
                $table->string('authority', 180)->nullable();
                $table->boolean('is_authoritative')->default(false);
                $table->boolean('is_active')->default(true);
                $table->jsonb('configuration')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('external_hr_sync_runs')) {
            Schema::create('external_hr_sync_runs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('external_hr_source_id')->constrained('external_hr_sources')->cascadeOnDelete();
                $table->string('direction', 20)->default('inbound');
                $table->string('status', 30)->default('pending');
                $table->unsignedInteger('records_seen')->default(0);
                $table->unsignedInteger('records_matched')->default(0);
                $table->unsignedInteger('records_changed')->default(0);
                $table->unsignedInteger('records_conflicted')->default(0);
                $table->jsonb('summary')->nullable();
                $table->text('error_message')->nullable();
                $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('external_hr_reconciliation_items')) {
            Schema::create('external_hr_reconciliation_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('sync_run_id')->constrained('external_hr_sync_runs')->cascadeOnDelete();
                $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
                $table->string('external_identifier', 120)->nullable();
                $table->string('field_name', 100);
                $table->text('local_value')->nullable();
                $table->text('external_value')->nullable();
                $table->string('status', 30)->default('review');
                $table->string('authority_preference', 20)->default('manual');
                $table->text('resolution_note')->nullable();
                $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
                $table->index(['status', 'field_name']);
            });
        }

        if (! Schema::hasTable('document_requirements')) {
            Schema::create('document_requirements', function (Blueprint $table): void {
                $table->id();
                $table->string('lifecycle_event', 60);
                $table->string('document_category', 60);
                $table->string('label', 180);
                $table->boolean('is_mandatory')->default(true);
                $table->string('applicable_position_code', 30)->nullable();
                $table->date('effective_from')->nullable();
                $table->date('effective_until')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index(['lifecycle_event', 'is_active']);
            });
        }

        if (! Schema::hasTable('employee_field_provenance')) {
            Schema::create('employee_field_provenance', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->string('field_name', 100);
                $table->text('field_value')->nullable();
                $table->string('source_type', 60);
                $table->string('source_reference', 180)->nullable();
                $table->string('source_system', 100)->nullable();
                $table->date('effective_from')->nullable();
                $table->date('effective_until')->nullable();
                $table->boolean('is_authoritative')->default(false);
                $table->unsignedTinyInteger('confidence')->default(100);
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('recorded_at');
                $table->timestamps();
                $table->index(['employee_id', 'field_name', 'effective_from']);
            });
        }

        if (! Schema::hasTable('establishment_temporal_events')) {
            Schema::create('establishment_temporal_events', function (Blueprint $table): void {
                $table->id();
                $table->string('entity_type', 60);
                $table->unsignedBigInteger('entity_id');
                $table->string('event_type', 60);
                $table->date('effective_date');
                $table->date('valid_until')->nullable();
                $table->jsonb('before_state')->nullable();
                $table->jsonb('after_state')->nullable();
                $table->string('source_type', 80)->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->string('source_reference', 180)->nullable();
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('recorded_at');
                $table->timestamps();
                $table->index(['entity_type', 'entity_id', 'effective_date']);
                $table->index(['source_type', 'source_id']);
            });
        }

        if (! Schema::hasTable('formal_case_bundles')) {
            Schema::create('formal_case_bundles', function (Blueprint $table): void {
                $table->id();
                $table->string('bundle_no', 80)->unique();
                $table->string('case_type', 60);
                $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
                $table->string('source_type', 80)->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->string('status', 30)->default('generated');
                $table->jsonb('manifest');
                $table->string('content_hash', 128);
                $table->string('file_path', 500)->nullable();
                $table->foreignId('generated_by')->constrained('users')->restrictOnDelete();
                $table->timestamp('generated_at');
                $table->timestamps();
                $table->index(['case_type', 'status']);
            });
        }

        if (! Schema::hasTable('assurance_exports')) {
            Schema::create('assurance_exports', function (Blueprint $table): void {
                $table->id();
                $table->string('export_no', 80)->unique();
                $table->string('audience', 60);
                $table->string('report_type', 80);
                $table->date('as_at_date');
                $table->jsonb('parameters')->nullable();
                $table->jsonb('manifest');
                $table->string('content_hash', 128);
                $table->string('file_path', 500)->nullable();
                $table->foreignId('generated_by')->constrained('users')->restrictOnDelete();
                $table->timestamp('generated_at');
                $table->timestamps();
                $table->index(['audience', 'report_type', 'as_at_date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('assurance_exports');
        Schema::dropIfExists('formal_case_bundles');
        Schema::dropIfExists('establishment_temporal_events');
        Schema::dropIfExists('employee_field_provenance');
        Schema::dropIfExists('document_requirements');
        Schema::dropIfExists('external_hr_reconciliation_items');
        Schema::dropIfExists('external_hr_sync_runs');
        Schema::dropIfExists('external_hr_sources');
        Schema::dropIfExists('administrative_eligibility_findings');
        Schema::dropIfExists('governance_rule_provisions');
    }
};
