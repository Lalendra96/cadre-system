<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * salary_scales — master lookup of official MoH salary scale codes
 * (e.g. "MN-1", "MT-1-2016"), referenced by employees.salary_scale_id.
 *
 * Kept as its own table (not a plain string on employees) so the list is
 * centrally maintained by Super Admin and can carry a min/max range for
 * validation and future payroll integration, without a migration every
 * time a new scale is gazetted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_scales', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->decimal('min_salary', 12, 2)->nullable();
            $table->decimal('max_salary', 12, 2)->nullable();
            $table->decimal('increment_amount', 12, 2)->nullable();
            $table->string('notes', 500)->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('disabled_by')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->text('disable_reason')->nullable();
            $table->foreign('disabled_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_scales');
    }
};
