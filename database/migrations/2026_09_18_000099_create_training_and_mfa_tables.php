<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competencies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('position_competencies', function (Blueprint $table) {
            $table->foreignId('position_id')->constrained()->restrictOnDelete();
            $table->foreignId('competency_id')->constrained()->restrictOnDelete();
            $table->string('required_level', 30)->default('basic');
            $table->unique(['position_id', 'competency_id']);
        });
        Schema::create('employee_competencies', function (Blueprint $table) {
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('competency_id')->constrained()->restrictOnDelete();
            $table->string('level', 30)->default('basic');
            $table->date('assessed_on')->nullable();
            $table->date('expires_on')->nullable();
            $table->foreignId('assessed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'competency_id']);
        });
        Schema::create('employee_training_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->string('course_name', 180);
            $table->string('provider', 180)->nullable();
            $table->date('completed_on')->nullable();
            $table->date('expires_on')->nullable();
            $table->string('certificate_reference', 100)->nullable();
            $table->string('status', 20)->default('completed');
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->text('mfa_secret')->nullable();
            $table->boolean('mfa_enabled')->default(false);
            $table->timestamp('mfa_verified_at')->nullable();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Competency, training and MFA history is retained.');
    }
};
