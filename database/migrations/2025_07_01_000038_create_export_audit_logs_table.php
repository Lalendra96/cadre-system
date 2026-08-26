<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('export_audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('export_type', 60);       // letter_attachment|csv_export|pdf_report|excel_export
            $table->string('resource_type', 60)->nullable(); // Letter|CarderMonthlyEntry|etc.
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->string('filename', 255)->nullable();
            $table->unsignedInteger('file_size_bytes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('exported_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['user_id','exported_at'], 'idx_eal_user_time');
            $table->index(['export_type'], 'idx_eal_type');
        });
    }

    public function down(): void { Schema::dropIfExists('export_audit_logs'); }
};
