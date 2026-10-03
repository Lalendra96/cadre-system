<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->string('category', 60);
            $t->string('title', 180);
            $t->string('reference_no', 100)->nullable();
            $t->date('document_date')->nullable();
            $t->date('expiry_date')->nullable();
            $t->string('file_path', 500);
            $t->string('original_name', 255);
            $t->string('mime_type', 120)->nullable();
            $t->unsignedBigInteger('file_size')->nullable();
            $t->boolean('is_confidential')->default(false);
            $t->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index(['employee_id', 'category']);
            $t->index('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_documents');
    }
};
