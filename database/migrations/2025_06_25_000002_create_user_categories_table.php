<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table: user_categories
 * Display-level category/title for a user (e.g. Director, Deputy Director,
 * Administrative Officer, Chief Clerk, Subject Officer, Planning Officer).
 * Managed exclusively by Super Admin. Does NOT drive permissions on its own —
 * permissions come from users.role. This table only controls the human-readable
 * title shown for a user within the Admin Group.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 100)->unique();
            $table->string('description', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('category_id')->references('id')->on('user_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
        });
        Schema::dropIfExists('user_categories');
    }
};
