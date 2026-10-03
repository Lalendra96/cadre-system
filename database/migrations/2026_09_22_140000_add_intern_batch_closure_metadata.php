<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intern_batches', function (Blueprint $table): void {
            $table->timestamp('closed_at')->nullable()->after('is_active');
            $table->unsignedBigInteger('closed_by')->nullable()->after('closed_at');
            $table->string('close_reason', 500)->nullable()->after('closed_by');
            $table->boolean('closed_automatically')->default(false)->after('close_reason');

            $table->foreign('closed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('intern_batches', function (Blueprint $table): void {
            $table->dropForeign(['closed_by']);
            $table->dropColumn([
                'closed_at',
                'closed_by',
                'close_reason',
                'closed_automatically',
            ]);
        });
    }
};
