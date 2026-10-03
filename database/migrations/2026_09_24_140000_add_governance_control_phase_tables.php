<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('governance_rule_versions')) {
            Schema::create('governance_rule_versions', function (Blueprint $table) {
                $table->id();
                $table->string('rule_key', 100);
                $table->string('title', 180);
                $table->string('authority_type', 80)->nullable();
                $table->string('authority_reference', 120)->nullable();
                $table->unsignedInteger('version_no');
                $table->date('effective_from');
                $table->date('effective_until')->nullable();
                $table->foreignId('supersedes_id')->nullable()->constrained('governance_rule_versions')->nullOnDelete();
                $table->string('applicable_category', 180)->nullable();
                $table->text('rule_definition');
                $table->text('implementation_notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['rule_key', 'version_no']);
                $table->index(['rule_key', 'effective_from', 'effective_until']);
            });
        }

        if (! Schema::hasTable('administrative_decisions')) {
            Schema::create('administrative_decisions', function (Blueprint $table) {
                $table->id();
                $table->string('decision_no', 80)->unique();
                $table->string('decision_type', 80);
                $table->string('title', 200);
                $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
                $table->string('source_type', 100)->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->foreignId('approval_authority_id')->nullable()->constrained('approval_authorities')->nullOnDelete();
                $table->foreignId('rule_version_id')->nullable()->constrained('governance_rule_versions')->nullOnDelete();
                $table->string('authority_reference', 160)->nullable();
                $table->date('effective_date')->nullable();
                $table->string('status', 30)->default('draft');
                $table->text('decision_text')->nullable();
                $table->text('supporting_evidence')->nullable();
                $table->jsonb('provenance')->nullable();
                $table->boolean('sod_override')->default(false);
                $table->text('sod_override_reason')->nullable();
                $table->string('sod_override_authority', 160)->nullable();
                $table->foreignId('prepared_by')->constrained('users')->restrictOnDelete();
                $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('recommended_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('checked_at')->nullable();
                $table->timestamp('recommended_at')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
                $table->index(['status', 'decision_type']);
                $table->index(['source_type', 'source_id']);
            });
        }

        if (! Schema::hasTable('regulatory_changes')) {
            Schema::create('regulatory_changes', function (Blueprint $table) {
                $table->id();
                $table->string('change_no', 80)->unique();
                $table->string('source_type', 60)->default('circular');
                $table->string('source_reference', 120);
                $table->string('title', 200);
                $table->boolean('applicable')->nullable();
                $table->text('applicability_assessment')->nullable();
                $table->text('affected_rules_modules')->nullable();
                $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status', 40)->default('received');
                $table->date('received_on')->nullable();
                $table->date('target_effective_on')->nullable();
                $table->date('implemented_on')->nullable();
                $table->date('verified_on')->nullable();
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('test_evidence')->nullable();
                $table->text('implementation_evidence')->nullable();
                $table->timestamps();
                $table->index(['status', 'target_effective_on']);
            });
        }

        if (! Schema::hasTable('records_retention_cases')) {
            Schema::create('records_retention_cases', function (Blueprint $table) {
                $table->id();
                $table->string('record_type', 100);
                $table->unsignedBigInteger('record_id')->nullable();
                $table->string('record_reference', 160)->nullable();
                $table->string('title', 200);
                $table->string('status', 30)->default('active');
                $table->date('closed_on')->nullable();
                $table->date('archived_on')->nullable();
                $table->unsignedInteger('retention_years')->nullable();
                $table->date('eligible_disposal_on')->nullable();
                $table->boolean('legal_hold')->default(false);
                $table->text('hold_reason')->nullable();
                $table->timestamp('hold_released_at')->nullable();
                $table->foreignId('hold_released_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('disposal_authorized_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('disposed_at')->nullable();
                $table->string('disposal_evidence_reference', 255)->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['status', 'eligible_disposal_on', 'legal_hold']);
            });
        }

        if (! Schema::hasTable('handover_certificates')) {
            Schema::create('handover_certificates', function (Blueprint $table) {
                $table->id();
                $table->string('handover_no', 80)->unique();
                $table->foreignId('from_user_id')->constrained('users')->restrictOnDelete();
                $table->foreignId('to_user_id')->constrained('users')->restrictOnDelete();
                $table->foreignId('supervisor_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->date('effective_on');
                $table->string('status', 30)->default('draft');
                $table->jsonb('workload_snapshot')->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('handed_over_at')->nullable();
                $table->timestamp('accepted_at')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->string('content_hash', 128)->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['status', 'effective_on']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('handover_certificates');
        Schema::dropIfExists('records_retention_cases');
        Schema::dropIfExists('regulatory_changes');
        // administrative_decisions predates this migration; retain it and its audit evidence.
        Schema::dropIfExists('governance_rule_versions');
    }
};
