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
            $table->string('service_file_no', 60)->nullable()->after('pay_no');
            $table->string('professional_registration_no', 80)->nullable()->after('wop_number');
            $table->date('professional_registration_expiry')->nullable()->after('professional_registration_no');
            $table->string('preferred_language', 10)->default('en')->after('gender');
            $table->index('service_file_no');
            $table->index('professional_registration_expiry');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Profile history is retained; restore a verified backup to roll back.');
    }
};
