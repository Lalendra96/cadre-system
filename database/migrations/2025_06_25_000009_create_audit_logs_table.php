<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Table: audit_logs
 * Immutable change log. Any create/update/delete made by Subject Officers,
 * Planning Officers, or Super Admin against carder data, designations,
 * subject codes, or users is recorded here so Admin Group / Super Admin can
 * review who changed what and when.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable(); // who made the change
            $table->string('action', 20);                     // created | updated | deleted
            $table->string('auditable_type', 150);             // model class
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->string('description', 255)->nullable();
            $table->jsonb('old_values')->nullable();
            $table->jsonb('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['auditable_type', 'auditable_id'], 'idx_audit_auditable');
            $table->index('created_at', 'idx_audit_created_at');
        });

        DB::statement("CREATE INDEX idx_audit_action ON audit_logs (action)");
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
