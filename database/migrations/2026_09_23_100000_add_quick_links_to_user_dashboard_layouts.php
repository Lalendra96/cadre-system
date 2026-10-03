<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_dashboard_layouts', function (Blueprint $table): void {
            $table->jsonb('quick_links')->nullable()->after('layout');
        });
    }

    public function down(): void
    {
        Schema::table('user_dashboard_layouts', function (Blueprint $table): void {
            $table->dropColumn('quick_links');
        });
    }
};
