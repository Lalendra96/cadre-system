<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_letters', function (Blueprint $table) {
            $table->string('document_classification', 30)->default('internal')->after('copy_type');
            $table->boolean('contains_personal_data')->default(true)->after('document_classification');
            $table->string('retention_category', 40)->default('official_record')->after('contains_personal_data');
            $table->string('access_note', 300)->nullable()->after('retention_category');
            $table->unsignedInteger('editor_lock_version')->default(1)->after('access_note');
            $table->string('approved_content_hash', 128)->nullable()->after('editor_lock_version');
            $table->timestamp('submitted_at')->nullable()->after('approved_content_hash');
            $table->timestamp('issued_at')->nullable()->after('submitted_at');
        });

        Schema::create('service_letter_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_letter_id')->constrained('service_letters')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('subject', 200);
            $table->text('rendered_body');
            $table->string('reference_no', 100)->nullable();
            $table->string('snapshot_hash', 128);
            $table->string('change_reason', 120)->default('autosave');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->unique(['service_letter_id', 'version_number'], 'sl_revision_version_unique');
            $table->index(['service_letter_id', 'created_at']);
        });

        Schema::create('service_letter_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_letter_id')->constrained('service_letters')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->text('body');
            $table->boolean('is_resolved')->default(false);
            $table->foreignId('resolved_by')->nullable()->constrained('users');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['service_letter_id', 'is_resolved']);
        });

        Schema::create('service_letter_presence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_letter_id')->constrained('service_letters')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamp('last_seen_at');
            $table->timestamps();
            $table->unique(['service_letter_id', 'user_id'], 'sl_presence_user_unique');
            $table->index(['service_letter_id', 'last_seen_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_letter_presence');
        Schema::dropIfExists('service_letter_comments');
        Schema::dropIfExists('service_letter_revisions');

        Schema::table('service_letters', function (Blueprint $table) {
            $table->dropColumn([
                'document_classification', 'contains_personal_data', 'retention_category',
                'access_note', 'editor_lock_version', 'approved_content_hash', 'submitted_at', 'issued_at',
            ]);
        });
    }
};
