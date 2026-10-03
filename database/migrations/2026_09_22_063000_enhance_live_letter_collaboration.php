<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('letters', function (Blueprint $table) {
            $table->date('review_due_date')->nullable()->after('access_note');
            $table->timestamp('archived_at')->nullable()->after('issued_at');
        });

        Schema::table('letter_revisions', function (Blueprint $table) {
            $table->string('retention_category', 60)->nullable()->after('contains_personal_data');
            $table->text('access_note')->nullable()->after('retention_category');
            $table->date('review_due_date')->nullable()->after('access_note');
        });

        Schema::table('letter_comments', function (Blueprint $table) {
            $table->string('comment_type', 20)->default('comment')->after('body');
            $table->text('quoted_text')->nullable()->after('comment_type');
        });
    }

    public function down(): void
    {
        Schema::table('letter_revisions', function (Blueprint $table) {
            $table->string('retention_category', 60)->nullable()->after('contains_personal_data');
            $table->text('access_note')->nullable()->after('retention_category');
            $table->date('review_due_date')->nullable()->after('access_note');
        });

        Schema::table('letter_comments', function (Blueprint $table) {
            $table->dropColumn(['comment_type', 'quoted_text']);
        });

        Schema::table('letter_revisions', function (Blueprint $table) {
            $table->dropColumn(['retention_category', 'access_note', 'review_due_date']);
        });

        Schema::table('letters', function (Blueprint $table) {
            $table->dropColumn(['review_due_date', 'archived_at']);
        });
    }
};
