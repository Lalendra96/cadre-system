<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('current_service_name', 180)->nullable()->after('employment_status');
            $table->string('combined_service_name', 180)->nullable()->after('current_service_name');
            $table->date('date_joined_combined_service')->nullable()->after('date_joined_public_service');
        });
        Schema::create('employee_service_periods', function (Blueprint $table) {
            $table->id(); $table->foreignId('employee_id')->constrained('employees'); $table->string('period_kind',30)->default('posting'); $table->string('sector',60)->nullable(); $table->string('service_name',180)->nullable(); $table->string('service_category',80)->nullable(); $table->string('institution_name',200)->nullable(); $table->string('ministry_department',200)->nullable(); $table->string('province',100)->nullable(); $table->foreignId('position_id')->nullable()->constrained('positions'); $table->string('position_text',180)->nullable(); $table->foreignId('position_grade_id')->nullable()->constrained('position_grades'); $table->string('grade_text',120)->nullable(); $table->string('appointment_type',80)->nullable(); $table->date('start_date'); $table->date('end_date')->nullable(); $table->string('movement_type',80)->nullable(); $table->string('movement_reference',120)->nullable(); $table->date('movement_reference_date')->nullable(); $table->string('from_institution',200)->nullable(); $table->string('to_institution',200)->nullable(); $table->string('salary_scale_text',120)->nullable(); $table->boolean('confirmed_status')->nullable(); $table->boolean('counts_for_service')->default(true); $table->boolean('counts_for_grade_service')->default(true); $table->boolean('counts_for_pension')->default(true); $table->foreignId('source_document_id')->nullable()->constrained('employee_documents')->nullOnDelete(); $table->string('verification_status',30)->default('document_pending'); $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('verified_at')->nullable(); $table->text('remarks')->nullable(); $table->boolean('is_current')->default(false); $table->boolean('is_active')->default(true); $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete(); $table->foreignId('disabled_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('disabled_at')->nullable(); $table->text('disable_reason')->nullable(); $table->timestamps(); $table->index(['employee_id','start_date']); $table->index(['employee_id','is_current']); $table->index(['service_name','institution_name']); $table->index(['verification_status','is_active']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('employee_service_periods');
        Schema::table('employees', function (Blueprint $table) { $table->dropColumn(['current_service_name','combined_service_name','date_joined_combined_service']); });
    }
};
