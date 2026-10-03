<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_increments', function (Blueprint $table) {
            $table->string('workflow_status', 30)->default('upcoming')->after('increment_date');
            $table->date('granted_date')->nullable()->after('workflow_status');
            $table->string('decision_reason', 255)->nullable()->after('reference_no');
            $table->foreignId('reviewed_by')->nullable()->after('recorded_by')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->index(['workflow_status', 'increment_date']);
        });
    }

    public function down(): void
    {
        Schema::table('employee_increments', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropIndex(['workflow_status', 'increment_date']);
            $table->dropColumn(['workflow_status', 'granted_date', 'decision_reason', 'reviewed_by', 'reviewed_at']);
        });
    }
};
