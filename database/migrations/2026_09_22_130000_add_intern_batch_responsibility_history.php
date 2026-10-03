<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intern_batch_responsibility_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('intern_batch_id')
                ->constrained('intern_batches')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('previous_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('new_user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->date('effective_date');
            $table->string('reference_no', 100)->nullable();
            $table->text('reason');
            $table->foreignId('changed_by')
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamps();

            $table->index(['intern_batch_id', 'effective_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intern_batch_responsibility_history');
    }
};
