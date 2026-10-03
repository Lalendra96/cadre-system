<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intern_batches', function (Blueprint $table): void {
            $table->date('start_date')->nullable()->after('name');
            $table->date('end_date')->nullable()->after('start_date');
            $table->foreignId('assigned_subject_officer_id')
                ->nullable()
                ->after('end_date')
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('responsibility_assigned_by')
                ->nullable()
                ->after('assigned_subject_officer_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('responsibility_assigned_at')->nullable()->after('responsibility_assigned_by');
            $table->index(['start_date', 'end_date']);
        });

        Schema::create('intern_rho_placements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('intern_batch_id')->constrained('intern_batches')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('intern_id')->constrained('interns')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('status', 30)->default('pending');
            $table->string('placement_institution', 180)->nullable();
            $table->string('placement_unit', 180)->nullable();
            $table->date('effective_date')->nullable();
            $table->string('reference_no', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['intern_batch_id', 'intern_id']);
            $table->index(['intern_batch_id', 'status']);
        });

        Schema::table('interns', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true)->after('mobile_number');
            $table->foreignId('disabled_by')->nullable()->after('is_active')->constrained('users')->nullOnDelete();
            $table->timestamp('disabled_at')->nullable()->after('disabled_by');
            $table->text('disable_reason')->nullable()->after('disabled_at');
        });
    }

    public function down(): void
    {
        Schema::table('interns', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('disabled_by');
            $table->dropColumn(['is_active', 'disabled_at', 'disable_reason']);
        });

        Schema::dropIfExists('intern_rho_placements');

        Schema::table('intern_batches', function (Blueprint $table): void {
            $table->dropIndex(['start_date', 'end_date']);
            $table->dropConstrainedForeignId('responsibility_assigned_by');
            $table->dropConstrainedForeignId('assigned_subject_officer_id');
            $table->dropColumn(['start_date', 'end_date', 'responsibility_assigned_at']);
        });
    }
};
