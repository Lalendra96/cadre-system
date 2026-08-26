<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * service_letter_templates — Super Admin-managed letter body templates,
 * one per (name, language) pair, in Sinhala or English.
 *
 * Body uses simple {{placeholder}} tokens (e.g. {{employee_name}},
 * {{position_title}}, {{date_reported_for_duty}}, {{today}}) substituted
 * by ServiceLetterService when a Subject Officer drafts a letter — see
 * that service's docblock for the full list of supported placeholders
 * and the escaping rule that prevents template injection.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_letter_templates', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('name', 150);
            $table->enum('language', ['si', 'en']);
            $table->text('body'); // contains {{placeholder}} tokens
            $table->string('description', 300)->nullable();

            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete();

            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('disabled_by')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->text('disable_reason')->nullable();
            $table->foreign('disabled_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['name', 'language'], 'uniq_template_name_lang');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_letter_templates');
    }
};
