<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            // null = use global system setting; set per-position to override
            $table->unsignedSmallInteger('vacancy_threshold_pct')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->dropColumn('vacancy_threshold_pct');
        });
    }
};
