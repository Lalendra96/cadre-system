<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table: subject_code_user
 * Pivot table — a Subject Officer (user) can be assigned MULTIPLE subject
 * codes, and a subject code could in principle be worked by more than one
 * officer over time (e.g. handover), so this is modelled as a proper
 * many-to-many rather than a single FK on users.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_code_user', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('subject_code_id');
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('subject_code_id')->references('id')->on('subject_codes')->cascadeOnDelete();
            $table->foreign('assigned_by')->references('id')->on('users')->nullOnDelete();

            $table->unique(['user_id', 'subject_code_id'], 'uniq_subject_code_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_code_user');
    }
};
