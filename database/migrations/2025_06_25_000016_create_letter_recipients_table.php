<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Table: letter_recipients
 * One row per (letter, recipient) — Director / Deputy Director /
 * Administrative Officer each get their own read/status tracking on the
 * same letter, so the Subject Officer's dashboard can show exactly who has
 * seen it and what each of them decided.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('letter_recipients', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('letter_id');
            $table->unsignedBigInteger('user_id');
            $table->string('status', 20)->default('pending'); // pending | reviewed | approved | rejected
            $table->text('remarks')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->foreign('letter_id')->references('id')->on('letters')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->unique(['letter_id', 'user_id'], 'uniq_letter_recipient');
        });

        DB::statement("ALTER TABLE letter_recipients ADD CONSTRAINT chk_letter_recipient_status CHECK (status IN ('pending','reviewed','approved','rejected'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('letter_recipients');
    }
};
