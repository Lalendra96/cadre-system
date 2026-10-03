<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('approval_authorities')) {
            Schema::create('approval_authorities', function (Blueprint $table) {
                $table->id();
                $table->string('process_key', 80);
                $table->string('process_label', 160);
                $table->string('authority_source', 220)->nullable();
                $table->string('reference_no', 100)->nullable();
                $table->string('preparer_role', 80)->nullable();
                $table->string('checker_role', 80)->nullable();
                $table->string('approver_role', 80);
                $table->date('effective_from')->nullable();
                $table->date('effective_to')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['process_key', 'is_active']);
            });
        }

        if (! Schema::hasTable('authority_delegations')) {
            Schema::create('authority_delegations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('from_user_id')->constrained('users')->restrictOnDelete();
                $table->foreignId('to_user_id')->constrained('users')->restrictOnDelete();
                $table->string('scope', 180);
                $table->string('reference_no', 100)->nullable();
                $table->date('starts_on');
                $table->date('ends_on');
                $table->text('conditions')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['is_active', 'ends_on']);
            });
        }

        if (! Schema::hasTable('governance_exceptions')) {
            Schema::create('governance_exceptions', function (Blueprint $table) {
                $table->id();
                $table->string('area', 100);
                $table->string('title', 180);
                $table->text('reason');
                $table->text('mitigation')->nullable();
                $table->string('status', 30)->default('open');
                $table->date('review_due_on')->nullable();
                $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('closed_at')->nullable();
                $table->timestamps();
                $table->index(['status', 'review_due_on']);
            });
        }

        if (! Schema::hasTable('access_reviews')) {
            Schema::create('access_reviews', function (Blueprint $table) {
                $table->id();
                $table->string('review_period', 40);
                $table->string('scope', 160);
                $table->string('status', 30)->default('planned');
                $table->date('due_on')->nullable();
                $table->unsignedInteger('accounts_reviewed')->default(0);
                $table->unsignedInteger('exceptions_found')->default(0);
                $table->text('evidence_reference')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
                $table->index(['status', 'due_on']);
            });
        }

        if (! Schema::hasTable('backup_assurance_records')) {
            Schema::create('backup_assurance_records', function (Blueprint $table) {
                $table->id();
                $table->string('backup_type', 40)->default('database');
                $table->timestamp('backup_at');
                $table->string('status', 30)->default('successful');
                $table->string('storage_reference', 255)->nullable();
                $table->timestamp('restore_tested_at')->nullable();
                $table->string('restore_result', 30)->nullable();
                $table->text('restore_evidence')->nullable();
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['backup_at', 'status']);
            });
        }

        if (! Schema::hasTable('integration_health_checks')) {
            Schema::create('integration_health_checks', function (Blueprint $table) {
                $table->id();
                $table->string('name', 160);
                $table->string('integration_type', 80)->nullable();
                $table->string('endpoint_label', 180)->nullable();
                $table->string('status', 30)->default('unknown');
                $table->timestamp('last_checked_at')->nullable();
                $table->timestamp('last_success_at')->nullable();
                $table->date('certificate_expires_on')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['status', 'certificate_expires_on']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_health_checks');
        Schema::dropIfExists('backup_assurance_records');
        Schema::dropIfExists('access_reviews');
        Schema::dropIfExists('governance_exceptions');
        Schema::dropIfExists('authority_delegations');
        Schema::dropIfExists('approval_authorities');
    }
};
