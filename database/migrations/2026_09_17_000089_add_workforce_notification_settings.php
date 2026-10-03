<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $settings = [
            ['key' => 'workforce_notifications_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'workforce_notifications', 'label' => 'Enable workforce reminders', 'description' => 'Master switch for increment, retirement and workforce reminders.'],
            ['key' => 'increment_reminder_days', 'value' => '[90,60,30,14,7,0]', 'type' => 'json', 'group' => 'workforce_notifications', 'label' => 'Increment reminder days', 'description' => 'Days before an increment date to notify the responsible Subject Officer.'],
            ['key' => 'retirement_reminder_days', 'value' => '[365,180,90,60,30,14,7]', 'type' => 'json', 'group' => 'workforce_notifications', 'label' => 'Retirement reminder days', 'description' => 'Days before retirement to notify the responsible Subject Officer.'],
            ['key' => 'acting_expiry_reminder_days', 'value' => '[30,14,7,0]', 'type' => 'json', 'group' => 'workforce_notifications', 'label' => 'Acting appointment reminder days', 'description' => 'Days before acting appointments expire.'],
            ['key' => 'workforce_overdue_escalation_days', 'value' => '14', 'type' => 'integer', 'group' => 'workforce_notifications', 'label' => 'Overdue escalation days', 'description' => 'Escalate unresolved workforce actions after this many days.'],
        ];
        foreach ($settings as $setting) {
            DB::table('system_settings')->updateOrInsert(['key' => $setting['key']], $setting + ['created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        DB::table('system_settings')->whereIn('key', [
            'workforce_notifications_enabled', 'increment_reminder_days', 'retirement_reminder_days',
            'acting_expiry_reminder_days', 'workforce_overdue_escalation_days',
        ])->delete();
    }
};
