<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Removes Designations from the system entirely.
 * Hierarchy was: Designation -> Position -> Subject Code
 * New hierarchy:                Position -> Subject Code
 *
 * positions.designation_id is the only remaining FK to the designations
 * table (all other tables had their designation_id columns dropped in
 * migrations 000019-000021). That FK is dropped, the column removed, and
 * the designations table itself is dropped.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->dropForeign(['designation_id']);
            $table->dropColumn('designation_id');
        });

        Schema::dropIfExists('designations');
    }

    public function down(): void
    {
        Schema::create('designations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code', 30)->unique();
            $table->string('title', 150);
            $table->string('grade', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('positions', function (Blueprint $table) {
            $table->unsignedBigInteger('designation_id')->nullable()->after('id');
            $table->foreign('designation_id')->references('id')->on('designations')->cascadeOnDelete();
        });
    }
};
