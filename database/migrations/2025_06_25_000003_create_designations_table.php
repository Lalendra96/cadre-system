<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table: designations
 * Master list of job designations/posts (e.g. "Medical Officer", "Staff Nurse").
 * CRUD by: Super Admin AND Planning Officer.
 * NOTE: You will populate this table's data yourself — structure only is provided.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('designations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code', 30)->unique();
            $table->string('title', 150);
            $table->string('grade', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('title', 'idx_designations_title');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('designations');
    }
};
