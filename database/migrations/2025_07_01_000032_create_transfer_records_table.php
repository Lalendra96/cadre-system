<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transfer_records', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('carder_entry_id')->nullable(); // links to parent monthly entry
            $table->unsignedBigInteger('employee_id')->nullable();     // if known, link to employee profile
            $table->string('employee_name', 150);                      // free-text in case not in system
            $table->string('designation', 100)->nullable();
            $table->string('direction', 3);                            // 'in' | 'out'
            $table->string('transfer_type', 30)->default('permanent'); // permanent|temporary|deputation|secondment
            $table->string('from_location', 150)->nullable();          // hospital / ward / unit
            $table->string('to_location', 150)->nullable();
            $table->date('effective_date');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('recorded_by');
            $table->timestamps();

            $table->foreign('carder_entry_id')->references('id')->on('carder_monthly_entries')->nullOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees')->nullOnDelete();
            $table->foreign('recorded_by')->references('id')->on('users');
            $table->index(['carder_entry_id'], 'idx_tr_entry');
            $table->index(['effective_date'], 'idx_tr_date');
        });
    }

    public function down(): void { Schema::dropIfExists('transfer_records'); }
};
