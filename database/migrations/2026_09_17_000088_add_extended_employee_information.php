<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $t) {
            $t->string('employment_status', 40)->default('active')->after('is_active');
            $t->text('permanent_address')->nullable()->after('whatsapp_mobile');
            $t->text('current_address')->nullable()->after('permanent_address');
            $t->string('emergency_contact_name', 150)->nullable()->after('current_address');
            $t->string('emergency_contact_relationship', 80)->nullable()->after('emergency_contact_name');
            $t->string('emergency_contact_mobile', 30)->nullable()->after('emergency_contact_relationship');
            $t->date('date_joined_public_service')->nullable()->after('date_of_appointment');
            $t->date('date_joined_institution')->nullable()->after('date_joined_public_service');
            $t->date('date_current_grade')->nullable()->after('date_joined_institution');
            $t->date('last_increment_date')->nullable()->after('date_current_grade');
            $t->date('next_increment_date')->nullable()->after('last_increment_date');
        });
    }

    public function down(): void
    {
        Schema::table('employees', fn (Blueprint $t) => $t->dropColumn(['employment_status', 'permanent_address', 'current_address', 'emergency_contact_name', 'emergency_contact_relationship', 'emergency_contact_mobile', 'date_joined_public_service', 'date_joined_institution', 'date_current_grade', 'last_increment_date', 'next_increment_date']));
    }
};
