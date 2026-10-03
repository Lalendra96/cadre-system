<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profile_completion_targets', function (Blueprint $table): void {
            $table->unsignedBigInteger('subject_code_id')->nullable()->change();
            $table->unsignedBigInteger('position_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Existing officer-total records intentionally prevent reverting these columns to NOT NULL.
    }
};
