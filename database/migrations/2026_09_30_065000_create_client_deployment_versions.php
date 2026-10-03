<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_deployment_versions', function (Blueprint $table) {
            $table->id();
            $table->string('client_name', 150);
            $table->string('edition', 100);
            $table->string('application_version', 60);
            $table->string('release_channel', 20)->default('stable');
            $table->jsonb('feature_snapshot');
            $table->text('change_reason');
            $table->foreignId('changed_by')->constrained('users');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['client_name', 'application_version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_deployment_versions');
    }
};
