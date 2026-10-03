<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_planning_assessments', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_no', 80)->unique();
            $table->string('title', 200);
            $table->string('planning_area', 60);
            $table->text('planning_question');
            $table->text('objective');
            $table->text('evidence_summary');
            $table->text('assumptions')->nullable();
            $table->text('options_considered');
            $table->text('recommended_option')->nullable();
            $table->text('workforce_impact')->nullable();
            $table->text('financial_impact')->nullable();
            $table->text('service_delivery_impact')->nullable();
            $table->text('risks_and_mitigations')->nullable();
            $table->text('equity_and_access_considerations')->nullable();
            $table->string('authority_reference', 200)->nullable();
            $table->string('data_as_at', 80)->nullable();
            $table->string('status', 30)->default('draft');
            $table->boolean('support_only_acknowledged')->default(false);
            $table->boolean('evidence_verified')->default(false);
            $table->boolean('alternatives_considered')->default(false);
            $table->boolean('risks_considered')->default(false);
            $table->boolean('minimum_necessary_confirmed')->default(false);
            $table->foreignId('prepared_by')->constrained('users');
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('referred_at')->nullable();
            $table->text('review_note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('disabled_by')->nullable()->constrained('users');
            $table->timestamp('disabled_at')->nullable();
            $table->text('disable_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'planning_area']);
            $table->index(['prepared_by', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_planning_assessments');
    }
};
