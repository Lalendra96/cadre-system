<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Governance default: this installation does not provide the authoritative payroll engine.
        DB::table('system_settings')->updateOrInsert(['key' => 'feature_payroll'], [
            'value' => '0', 'type' => 'boolean', 'group' => 'features', 'label' => 'Payroll',
            'description' => 'Disabled by default. Payroll is handled by a separate Sri Lankan Government-approved system; enable only after an authorised governance decision.',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('system_settings')->updateOrInsert(['key' => 'payroll_external_system_authoritative'], [
            'value' => '1', 'type' => 'boolean', 'group' => 'governance', 'label' => 'External Payroll System Authoritative',
            'description' => 'Marks the separate Sri Lankan Government-approved payroll system as the authoritative payroll source.',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach ([
            'client_profile_name' => config('app.name'),
            'client_profile_edition' => 'Government / Custom',
            'client_application_version' => 'Unspecified',
            'client_release_channel' => 'stable',
        ] as $key => $value) {
            DB::table('system_settings')->updateOrInsert(['key' => $key], [
                'value' => (string) $value, 'type' => 'string', 'group' => 'client_deployment',
                'label' => ucwords(str_replace('_', ' ', $key)),
                'description' => 'Client deployment/version metadata managed by the Client Version & Feature Manager.',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        DB::table('nav_items')->updateOrInsert(['route_name' => 'admin.client-features'], [
            'section' => 'Administration', 'label' => '🧩 Client Version & Features',
            'allowed_roles' => json_encode(['super_admin', 'client_feature_manager']),
            'sort_order' => 905, 'is_active' => true, 'updated_at' => now(), 'created_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('nav_items')->where('route_name', 'admin.client-features')->delete();
        DB::table('system_settings')->whereIn('key', [
            'payroll_external_system_authoritative','client_profile_name','client_profile_edition',
            'client_application_version','client_release_channel',
        ])->delete();
    }
};
