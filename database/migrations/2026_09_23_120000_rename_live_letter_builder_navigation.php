<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('nav_items')
            ->where('route_name', 'service-letters.index')
            ->update([
                'label' => '✉️ Service & Official Letter Builder',
                'updated_at' => now(),
            ]);

        DB::table('nav_items')
            ->where('route_name', 'service-letters.create')
            ->update([
                'label' => '✍️ Create Service / Official Letter',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('nav_items')
            ->where('route_name', 'service-letters.index')
            ->update([
                'label' => '✉️ Live Letter Builder',
                'updated_at' => now(),
            ]);

        DB::table('nav_items')
            ->where('route_name', 'service-letters.create')
            ->update([
                'label' => '✍️ Create Live Letter',
                'updated_at' => now(),
            ]);
    }
};
