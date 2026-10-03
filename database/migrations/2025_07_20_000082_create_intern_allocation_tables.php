<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Intern Medical Officer Allocation — a self-contained module for
 * assigning interns to rotation postings (Medicine, Surgery, Paediatrics
 * (Prof.), Gyn & Obs (Prof./New), SBSCH, etc.) across two appointments
 * per intake batch (e.g. "July 2026"), matching the hospital's standard
 * paper allocation form.
 *
 * intern_batches           — one row per intake, e.g. "July 2026"
 * intern_rotation_units     — the fixed set of rotation categories on the
 *                             form (Super-Admin-editable, not hardcoded,
 *                             since the exact list can change by batch)
 * intern_unit_allocations   — the CAPACITY: how many slots a rotation unit
 *                             has, per batch, per appointment (1st/2nd) —
 *                             this is the "amount" from the request
 * interns                   — the uploaded name list for a batch
 * intern_assignments        — the actual filled-in slot: which intern
 *                             holds which numbered slot, for which
 *                             rotation unit, for which appointment
 *
 * Two uniqueness rules enforce the two things that must never happen:
 *   - the same intern holding two DIFFERENT rotations for the SAME
 *     appointment number (they get exactly one 1st and one 2nd)
 *   - two DIFFERENT interns holding the SAME numbered slot in the SAME
 *     rotation unit for the SAME appointment
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intern_batches', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 100); // e.g. "July 2026"
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
            $table->unique('name');
        });

        Schema::create('intern_rotation_units', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 100); // e.g. "Medicine", "Gyn & Obs (Prof.)"
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('intern_unit_allocations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('intern_batch_id');
            $table->unsignedBigInteger('intern_rotation_unit_id');
            $table->unsignedTinyInteger('appointment_number'); // 1 or 2
            $table->unsignedSmallInteger('capacity')->default(0); // the "amount" of slots

            $table->foreign('intern_batch_id')->references('id')->on('intern_batches')->cascadeOnDelete();
            $table->foreign('intern_rotation_unit_id')->references('id')->on('intern_rotation_units')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['intern_batch_id', 'intern_rotation_unit_id', 'appointment_number'], 'uniq_batch_unit_appt');
        });

        Schema::create('interns', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('intern_batch_id');
            $table->string('name', 150);
            $table->string('nic_number', 12)->nullable();
            $table->string('mobile_number', 20)->nullable();

            $table->foreign('intern_batch_id')->references('id')->on('intern_batches')->cascadeOnDelete();
            $table->timestamps();

            $table->index('intern_batch_id');
        });

        Schema::create('intern_assignments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('intern_id');
            $table->unsignedBigInteger('intern_batch_id');       // denormalized for batch-scoped queries
            $table->unsignedBigInteger('intern_rotation_unit_id');
            $table->unsignedTinyInteger('appointment_number');    // denormalized from the allocation, for the uniqueness rule below
            $table->unsignedSmallInteger('slot_number');
            $table->unsignedBigInteger('assigned_by')->nullable(); // null = self-selected via the public portal
            $table->timestamp('assigned_at')->nullable();

            $table->foreign('intern_id')->references('id')->on('interns')->cascadeOnDelete();
            $table->foreign('intern_batch_id')->references('id')->on('intern_batches')->cascadeOnDelete();
            $table->foreign('intern_rotation_unit_id')->references('id')->on('intern_rotation_units')->cascadeOnDelete();
            $table->foreign('assigned_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['intern_id', 'appointment_number'], 'uniq_intern_one_slot_per_appt');
            $table->unique(['intern_batch_id', 'intern_rotation_unit_id', 'appointment_number', 'slot_number'], 'uniq_slot_occupant');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('can_manage_intern_assignments')->default(false)->after('can_manage_circular_groups');
        });

        // Seed the fixed rotation units shown on the standard form
        DB::table('intern_rotation_units')->insert([
            ['name' => 'Medicine',              'sort_order' => 10, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Surgery',                'sort_order' => 20, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Paediatrics (Prof.)',    'sort_order' => 30, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Gyn & Obs (Prof.)',      'sort_order' => 40, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Gyn & Obs (New)',        'sort_order' => 50, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'SBSCH',                  'sort_order' => 60, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('can_manage_intern_assignments');
        });
        Schema::dropIfExists('intern_assignments');
        Schema::dropIfExists('interns');
        Schema::dropIfExists('intern_unit_allocations');
        Schema::dropIfExists('intern_rotation_units');
        Schema::dropIfExists('intern_batches');
    }
};
