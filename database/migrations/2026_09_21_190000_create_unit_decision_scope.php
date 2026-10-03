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
        if (! Schema::hasTable('unit_user_decision_scope')) {
            Schema::create('unit_user_decision_scope', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
                $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('assigned_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'unit_id'], 'uniq_unit_user_decision_scope');
                $table->index(['unit_id', 'user_id'], 'idx_unit_user_decision_scope');
            });
        }

        if (Schema::hasTable('nav_items')) {
            DB::table('nav_items')->updateOrInsert(
                ['route_name' => 'unit-decision-support.index'],
                [
                    'section' => 'Planning',
                    'label' => 'Unit Decision Support',
                    'route_params' => null,
                    'open_in_new_tab' => false,
                    'allowed_roles' => json_encode(['unit_manager', 'super_admin']),
                    'custom_checks' => null,
                    'sort_order' => 72,
                    'is_active' => true,
                    'created_by' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('nav_items')) {
            DB::table('nav_items')->where('route_name', 'unit-decision-support.index')->delete();
        }

        Schema::dropIfExists('unit_user_decision_scope');
    }
};
