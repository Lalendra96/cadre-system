<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('acting_appointments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('acting_position_id');  // position they are acting in
            $table->unsignedBigInteger('substantive_position_id'); // their actual position
            $table->date('start_date');
            $table->date('end_date')->nullable(); // null = ongoing
            $table->string('appointment_order_no', 60)->nullable();
            $table->text('remarks')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('acting_position_id')->references('id')->on('positions');
            $table->foreign('substantive_position_id')->references('id')->on('positions');
            $table->foreign('created_by')->references('id')->on('users');
            $table->index(['employee_id'], 'idx_aa_employee');
            $table->index(['is_active', 'end_date'], 'idx_aa_active');
        });
    }

    public function down(): void { Schema::dropIfExists('acting_appointments'); }
};
