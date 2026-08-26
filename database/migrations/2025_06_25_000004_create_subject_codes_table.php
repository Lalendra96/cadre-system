<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table: subject_codes
 * Each Subject Officer is identified by a unique subject_code. A code is
 * usually tied to one designation, but designation_id is nullable in case a
 * code spans more than one designation in your structure.
 * CRUD by: Super Admin.
 * NOTE: You will populate this table's data yourself — structure only is provided.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_codes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->unsignedBigInteger('designation_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('designation_id')->references('id')->on('designations')->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('subject_code_id')->references('id')->on('subject_codes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['subject_code_id']);
        });
        Schema::dropIfExists('subject_codes');
    }
};
