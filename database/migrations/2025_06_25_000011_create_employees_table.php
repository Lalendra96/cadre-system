<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Table: employees
 * Individual employee profile records held under a subject code. Only the
 * Subject Officer(s) assigned to that subject code (via subject_code_user)
 * may create/update these — enforced in EmployeeRequest + EmployeeController,
 * not just the UI. Super Admin retains full oversight access for support
 * and corrections.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('salutation', 20);
            $table->string('name', 150);
            $table->string('pay_no', 50)->nullable()->unique();
            $table->unsignedBigInteger('subject_code_id');
            $table->unsignedBigInteger('designation_id')->nullable(); // derived from subject_code, kept for fast reporting
            $table->char('gender', 1); // 'M', 'F', 'O'
            $table->string('email', 150)->nullable();
            $table->string('whatsapp_mobile', 20);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('subject_code_id')->references('id')->on('subject_codes')->cascadeOnDelete();
            $table->foreign('designation_id')->references('id')->on('designations')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();

            $table->index('subject_code_id', 'idx_employees_subject_code');
            $table->index('name', 'idx_employees_name');
        });

        DB::statement("ALTER TABLE employees ADD CONSTRAINT chk_employees_gender CHECK (gender IN ('M','F','O'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
