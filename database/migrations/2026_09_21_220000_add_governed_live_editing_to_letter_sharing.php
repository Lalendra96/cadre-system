<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('letters', function (Blueprint $table) {
            $table->boolean('live_edit_enabled')->default(false)->after('description');
            $table->longText('live_content')->nullable()->after('live_edit_enabled');
            $table->string('reference_no', 100)->nullable()->after('live_content');
            $table->string('workflow_status', 30)->default('draft')->after('reference_no');
            $table->string('document_classification', 30)->default('internal')->after('workflow_status');
            $table->boolean('contains_personal_data')->default(false)->after('document_classification');
            $table->string('retention_category', 60)->default('official_correspondence')->after('contains_personal_data');
            $table->text('access_note')->nullable()->after('retention_category');
            $table->unsignedInteger('editor_lock_version')->default(1)->after('access_note');
            $table->string('approved_content_hash', 128)->nullable()->after('editor_lock_version');
            $table->foreignId('approved_by')->nullable()->after('approved_content_hash')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->timestamp('issued_at')->nullable()->after('approved_at');
            $table->index(['workflow_status', 'live_edit_enabled'], 'idx_letters_live_workflow');
        });

        Schema::table('letter_recipients', function (Blueprint $table) {
            $table->string('access_level', 20)->default('reviewer')->after('user_id');
        });

        Schema::create('letter_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('letter_id')->constrained('letters')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('title', 200);
            $table->longText('live_content')->nullable();
            $table->string('reference_no', 100)->nullable();
            $table->string('document_classification', 30)->default('internal');
            $table->boolean('contains_personal_data')->default(false);
            $table->string('snapshot_hash', 128);
            $table->string('change_reason', 120)->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['letter_id', 'version_number']);
        });

        Schema::create('letter_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('letter_id')->constrained('letters')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->text('body');
            $table->boolean('is_resolved')->default(false);
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('letter_presence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('letter_id')->constrained('letters')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('last_seen_at');
            $table->timestamps();
            $table->unique(['letter_id', 'user_id']);
            $table->index(['letter_id', 'last_seen_at']);
        });

        DB::table('system_settings')->updateOrInsert(
            ['key' => 'feature_live_letter_editing'],
            [
                'value' => 'true', 'type' => 'boolean', 'group' => 'features',
                'label' => 'Live Letter Editing',
                'description' => 'Enable governed collaborative editing inside Letter Sharing. When disabled, normal attachment sharing and review remain available.',
                'created_at' => now(), 'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('system_settings')->where('key', 'feature_live_letter_editing')->delete();
        Schema::dropIfExists('letter_presence');
        Schema::dropIfExists('letter_comments');
        Schema::dropIfExists('letter_revisions');
        Schema::table('letter_recipients', fn (Blueprint $table) => $table->dropColumn('access_level'));
        Schema::table('letters', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropIndex('idx_letters_live_workflow');
            $table->dropColumn([
                'live_edit_enabled', 'live_content', 'reference_no', 'workflow_status', 'document_classification',
                'contains_personal_data', 'retention_category', 'access_note', 'editor_lock_version',
                'approved_content_hash', 'approved_by', 'approved_at', 'issued_at',
            ]);
        });
    }
};
