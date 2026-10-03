<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('system_settings')) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => 'feature_offline_kb_assistant'],
                [
                    'value' => 'false',
                    'type' => 'boolean',
                    'group' => 'features',
                    'label' => 'AI Chat — Offline Help, KB & Decision Support',
                    'description' => 'Master Super Admin switch for governed AI Chat phases 1–4. When disabled, chat routes, navigation and context-help entry points are unavailable while knowledge-source administration remains accessible to Super Admin.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        if (Schema::hasTable('nav_items')) {
            DB::table('nav_items')
                ->where('route_name', 'offline-assistant.index')
                ->update([
                    'label' => '🤖 AI Chat',
                    'section' => 'Help & Intelligence',
                    'updated_at' => now(),
                ]);

            DB::table('nav_items')
                ->where('route_name', 'offline-assistant.sources.index')
                ->update([
                    'label' => '📚 AI Chat Knowledge Sources',
                    'section' => 'Administration',
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('system_settings')) {
            DB::table('system_settings')
                ->where('key', 'feature_offline_kb_assistant')
                ->update([
                    'label' => 'Offline Help & Knowledge Assistant',
                    'description' => 'Enable governed offline application help, knowledge-base chat, context help and planning decision support.',
                    'updated_at' => now(),
                ]);
        }
    }
};
