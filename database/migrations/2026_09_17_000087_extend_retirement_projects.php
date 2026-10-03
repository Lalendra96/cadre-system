<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retirement_projects', function (Blueprint $table) {
            $table->boolean('dob_verified')->default(false);
            $table->boolean('service_verified')->default(false);
            $table->boolean('contact_verified')->default(false);
            $table->boolean('documents_verified')->default(false);
            $table->boolean('vacancy_created')->default(false);
            $table->timestamp('last_reminder_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('retirement_projects', fn (Blueprint $table) => $table->dropColumn(['dob_verified', 'service_verified', 'contact_verified', 'documents_verified', 'vacancy_created', 'last_reminder_at']));
    }
};
