<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ip_allowlist', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('cidr', 45);            // e.g. 192.168.1.0/24 or ::1/128
            $table->string('description', 120)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users');
            $table->index('is_active', 'idx_ial_active');
        });
    }

    public function down(): void { Schema::dropIfExists('ip_allowlist'); }
};
