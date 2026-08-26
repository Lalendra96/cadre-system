<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table: approved_carders
 * The Ministry-approved establishment (cadre) for a designation, for a given
 * year. Maintained exclusively by Planning Officers. This is the "approved"
 * baseline that monthly actuals (carder_monthly_entries) are compared against.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approved_carders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('designation_id');
            $table->unsignedBigInteger('subject_code_id')->nullable();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('approved_male')->default(0);
            $table->unsignedInteger('approved_female')->default(0);
            $table->string('ministry_reference_no', 100)->nullable();
            $table->date('approved_date')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('designation_id')->references('id')->on('designations')->cascadeOnDelete();
            $table->foreign('subject_code_id')->references('id')->on('subject_codes')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            // A designation should only have one approved-carder record per year
            // (optionally split further per subject_code_id).
            $table->unique(['designation_id', 'subject_code_id', 'year'], 'uniq_approved_carder_period');
            $table->index('year', 'idx_approved_carders_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approved_carders');
    }
};
