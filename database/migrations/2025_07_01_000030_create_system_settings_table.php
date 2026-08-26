<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema, DB};

return new class extends Migration {
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->string('key', 80)->primary();
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string'); // string|integer|boolean|json
            $table->string('group', 40)->default('general');
            $table->string('label', 120)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index('group', 'idx_settings_group');
        });

        DB::table('system_settings')->insert([
            // ── Feature 1: Submission Deadlines ──
            ['key'=>'deadline_day','value'=>'15','type'=>'integer','group'=>'deadlines',
             'label'=>'Monthly entry due day','description'=>'Day of the current month by which entries for the PREVIOUS month must be submitted (1–28).',
             'created_at'=>now(),'updated_at'=>now()],
            ['key'=>'deadline_grace_days','value'=>'0','type'=>'integer','group'=>'deadlines',
             'label'=>'Grace period (days)','description'=>'Additional days after the deadline before entries are locked.',
             'created_at'=>now(),'updated_at'=>now()],
            ['key'=>'deadline_enforcement_enabled','value'=>'true','type'=>'boolean','group'=>'deadlines',
             'label'=>'Enforce deadlines','description'=>'When enabled, entries are locked after the deadline passes.',
             'created_at'=>now(),'updated_at'=>now()],

            // ── Feature 10: Verification Layer ──
            ['key'=>'entry_verification_required','value'=>'false','type'=>'boolean','group'=>'verification',
             'label'=>'Require entry verification','description'=>'When enabled, submitted entries must be verified by a Planning Officer or Super Admin before appearing in reports.',
             'created_at'=>now(),'updated_at'=>now()],
            ['key'=>'entry_verifier_role','value'=>'planning_officer','type'=>'string','group'=>'verification',
             'label'=>'Verifier role','description'=>'Role that can verify monthly entries (planning_officer or super_admin).',
             'created_at'=>now(),'updated_at'=>now()],

            // ── Feature 7: Vacancy Alerts ──
            ['key'=>'vacancy_alert_threshold_pct','value'=>'20','type'=>'integer','group'=>'alerts',
             'label'=>'Vacancy alert threshold (%)','description'=>'Show an alert when vacancies exceed this percentage of the approved cadre.',
             'created_at'=>now(),'updated_at'=>now()],
            ['key'=>'vacancy_alert_enabled','value'=>'true','type'=>'boolean','group'=>'alerts',
             'label'=>'Enable vacancy alerts','description'=>'Show vacancy alerts on dashboards.',
             'created_at'=>now(),'updated_at'=>now()],

            // ── Security 16: Concurrent Sessions ──
            ['key'=>'concurrent_session_lock_enabled','value'=>'true','type'=>'boolean','group'=>'security',
             'label'=>'Prevent concurrent sessions','description'=>'When enabled, logging in from a new browser invalidates all previous sessions for the same account.',
             'created_at'=>now(),'updated_at'=>now()],

            // ── Security 17: IP Allowlist ──
            ['key'=>'ip_allowlist_enabled','value'=>'false','type'=>'boolean','group'=>'security',
             'label'=>'Enable IP allowlist','description'=>'When enabled, only requests from allowlisted IP ranges are accepted.',
             'created_at'=>now(),'updated_at'=>now()],

            // ── Security 18: Export Audit ──
            ['key'=>'export_audit_enabled','value'=>'true','type'=>'boolean','group'=>'security',
             'label'=>'Audit data exports','description'=>'Log all file downloads and data exports with user, timestamp and IP.',
             'created_at'=>now(),'updated_at'=>now()],
        ]);
    }

    public function down(): void { Schema::dropIfExists('system_settings'); }
};
