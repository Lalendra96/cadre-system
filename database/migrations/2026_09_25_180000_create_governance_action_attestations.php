<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('governance_action_attestations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('action_key', 100);
            $table->string('resource_type', 120);
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->string('administrative_purpose', 500);
            $table->string('authority_reference', 180)->nullable();
            $table->boolean('evidence_reviewed')->default(false);
            $table->boolean('accuracy_confirmed')->default(false);
            $table->boolean('minimum_necessary_confirmed')->default(false);
            $table->boolean('no_conflict_confirmed')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('attested_at')->useCurrent();
            $table->timestamps();

            $table->index(['resource_type', 'resource_id']);
            $table->index(['action_key', 'attested_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('governance_action_attestations');
    }
};
