<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retirement_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->date('retirement_date');
            $table->string('status', 40)->default('identified');
            $table->date('notification_issued_date')->nullable();
            $table->date('employee_acknowledged_date')->nullable();
            $table->boolean('replacement_required')->default(false);
            $table->string('pension_status', 40)->default('not_started');
            $table->string('clearance_status', 40)->default('not_started');
            $table->string('handover_status', 40)->default('not_started');
            $table->date('final_working_date')->nullable();
            $table->date('completed_at')->nullable();
            $table->string('reference_no', 80)->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique('employee_id');
            $table->index(['retirement_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retirement_projects');
    }
};
