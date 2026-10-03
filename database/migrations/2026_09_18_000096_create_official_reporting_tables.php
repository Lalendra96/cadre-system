<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('official_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_type', 80);
            $table->string('period_key', 30);
            $table->string('status', 20)->default('prepared');
            $table->jsonb('payload');
            $table->foreignId('prepared_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('checked_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('checked_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('signature_hash', 128)->nullable();
            $table->timestamps();
            $table->unique(['report_type', 'period_key']);
        });
        Schema::create('workforce_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('official_report_id')->unique()->constrained()->restrictOnDelete();
            $table->jsonb('payload');
            $table->string('content_hash', 128)->unique();
            $table->foreignId('signed_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('signed_at');
            $table->timestamp('archived_at');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Official report and snapshot history is immutable and retained.');
    }
};
