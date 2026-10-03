<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_escalations', function (Blueprint $table) {
            $table->id();
            $table->string('source_type', 80);
            $table->unsignedBigInteger('source_id');
            $table->string('rule_code', 80);
            $table->string('severity', 20)->default('warning');
            $table->string('status', 20)->default('open');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestamps();
            $table->index(['source_type', 'source_id', 'status']);
        });

        Schema::table('employee_documents', function (Blueprint $table) {
            $table->foreignId('supersedes_id')->nullable()->constrained('employee_documents')->restrictOnDelete();
            $table->string('verification_status', 20)->default('pending');
            $table->foreignId('verified_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_note')->nullable();
            $table->index(['expiry_date', 'verification_status']);
        });

        Schema::create('employee_promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('from_grade_id')->nullable()->constrained('position_grades')->restrictOnDelete();
            $table->foreignId('to_grade_id')->constrained('position_grades')->restrictOnDelete();
            $table->date('effective_date');
            $table->string('status', 25)->default('draft');
            $table->string('reference_no', 100)->nullable();
            $table->text('justification')->nullable();
            $table->foreignId('prepared_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('checked_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('checked_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'status']);
        });

        Schema::create('reconciliation_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('position_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->string('severity', 20);
            $table->string('status', 20)->default('open');
            $table->jsonb('evidence');
            $table->text('explanation')->nullable();
            $table->text('resolution_note')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->unique(['position_id', 'year', 'month']);
        });

        Schema::create('recruitment_vacancies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('position_id')->constrained()->restrictOnDelete();
            $table->foreignId('retirement_project_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedInteger('vacancy_count')->default(1);
            $table->string('status', 25)->default('identified');
            $table->date('identified_on');
            $table->date('requested_on')->nullable();
            $table->date('filled_on')->nullable();
            $table->string('reference_no', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['position_id', 'status']);
        });

        Schema::create('duplicate_resolution_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('primary_employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('duplicate_employee_id')->constrained('employees')->restrictOnDelete();
            $table->string('match_key', 100);
            $table->string('status', 25)->default('open');
            $table->text('resolution_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['primary_employee_id', 'duplicate_employee_id']);
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Workflow audit history is retained; restore a verified backup to roll back.');
    }
};
