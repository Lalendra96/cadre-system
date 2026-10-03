<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('letters', function (Blueprint $table) {
            $table->foreignId('letterhead_id')
                ->nullable()
                ->after('reference_no')
                ->constrained('service_letter_letterheads')
                ->nullOnDelete();

            $table->jsonb('letterhead_snapshot')->nullable()->after('letterhead_id');

            $table->foreignId('e_signature_id')
                ->nullable()
                ->after('approved_at')
                ->constrained('e_signatures')
                ->nullOnDelete();
        });

        Schema::table('letter_revisions', function (Blueprint $table) {
            $table->unsignedBigInteger('letterhead_id')->nullable()->after('reference_no');
            $table->index('letterhead_id');
        });

        DB::table('nav_items')
            ->where('route_name', 'service-letter-letterheads.index')
            ->update(['label' => '🏛 Official Letter Headers', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('nav_items')
            ->where('route_name', 'service-letter-letterheads.index')
            ->update(['label' => '🏛 Letterheads', 'updated_at' => now()]);

        Schema::table('letter_revisions', function (Blueprint $table) {
            $table->dropIndex(['letterhead_id']);
            $table->dropColumn('letterhead_id');
        });

        Schema::table('letters', function (Blueprint $table) {
            $table->dropForeign(['e_signature_id']);
            $table->dropForeign(['letterhead_id']);
            $table->dropColumn(['e_signature_id', 'letterhead_snapshot', 'letterhead_id']);
        });
    }
};
