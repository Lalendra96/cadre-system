<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Unit Types classify hospital organisational units.
 * Examples: Ward, Department, Clinic, Theatre, ICU, OPD, Laboratory.
 * Units are then assigned to a type for structured reporting.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('unit_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 80)->unique();
            $table->string('code', 20)->unique()->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['is_active', 'sort_order'], 'idx_unit_types_active_sort');
        });

        // Seed the most common hospital unit types
        \Illuminate\Support\Facades\DB::table('unit_types')->insert([
            ['name' => 'Ward',          'code' => 'WRD',  'sort_order' => 10, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Department',    'code' => 'DEPT', 'sort_order' => 20, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'OPD Clinic',    'code' => 'OPD',  'sort_order' => 30, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Theatre',       'code' => 'THR',  'sort_order' => 40, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ICU / HDU',     'code' => 'ICU',  'sort_order' => 50, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Laboratory',    'code' => 'LAB',  'sort_order' => 60, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pharmacy',      'code' => 'PHRM', 'sort_order' => 70, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Radiology',     'code' => 'RAD',  'sort_order' => 80, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Administration','code' => 'ADMIN','sort_order' => 90, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
    public function down(): void { Schema::dropIfExists('unit_types'); }
};
