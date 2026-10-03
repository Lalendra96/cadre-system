<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Services\FeatureToggleService;
use App\Services\WorkforceAuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ClientFeatureManagementController extends Controller
{
    public function index()
    {
        $features = collect(FeatureToggleService::FEATURES)
            ->filter(fn ($config, $id) => FeatureToggleService::clientManageable((string) $id))
            ->map(fn ($config, $id) => $config + ['id' => $id, 'enabled' => FeatureToggleService::enabled((string) $id)]);

        $profile = [
            'client_name' => SystemSetting::get('client_profile_name', config('app.name')),
            'edition' => SystemSetting::get('client_profile_edition', 'Government / Custom'),
            'application_version' => SystemSetting::get('client_application_version', 'Unspecified'),
            'release_channel' => SystemSetting::get('client_release_channel', 'stable'),
        ];

        $history = DB::table('client_deployment_versions')->leftJoin('users', 'users.id', '=', 'client_deployment_versions.changed_by')->select('client_deployment_versions.*', 'users.name as changed_by_name')->latest('client_deployment_versions.id')->limit(20)->get();

        return view('admin.client-features', compact('features', 'profile', 'history'));
    }

    public function update(Request $request, WorkforceAuditService $audit)
    {
        $data = $request->validate([
            'client_name' => ['required', 'string', 'max:150'],
            'edition' => ['required', 'string', 'max:100'],
            'application_version' => ['required', 'string', 'max:60'],
            'release_channel' => ['required', Rule::in(['stable', 'pilot', 'testing'])],
            'features' => ['sometimes', 'array'],
            'features.*' => ['boolean'],
            'change_reason' => ['required', 'string', 'min:8', 'max:500'],
        ]);

        $oldProfile = [
            'client_name' => SystemSetting::get('client_profile_name', ''),
            'edition' => SystemSetting::get('client_profile_edition', ''),
            'application_version' => SystemSetting::get('client_application_version', ''),
            'release_channel' => SystemSetting::get('client_release_channel', ''),
        ];

        SystemSetting::set('client_profile_name', trim($data['client_name']));
        SystemSetting::set('client_profile_edition', trim($data['edition']));
        SystemSetting::set('client_application_version', trim($data['application_version']));
        SystemSetting::set('client_release_channel', $data['release_channel']);

        $requested = collect(array_keys(FeatureToggleService::FEATURES))
            ->filter(fn (string $id) => FeatureToggleService::clientManageable($id))
            ->mapWithKeys(fn (string $id) => [$id => $request->boolean('features.'.$id)]);

        $dependencies = [
            'overtime' => ['attendance'],
            'biometric_attendance' => ['attendance'],
            'leave_automation' => ['leave_management'],
            'contract_lifecycle' => ['contracts'],
            'locum_sessions' => ['contracts'],
            'locum_pool' => ['locum_sessions', 'contracts'],
            'roster_optimizer' => ['roster'],
        ];
        foreach ($dependencies as $feature => $requires) {
            if (! $requested->get($feature, false)) {
                continue;
            }
            foreach ($requires as $requiredFeature) {
                if (! $requested->get($requiredFeature, false)) {
                    return back()->withErrors(['features' => FeatureToggleService::FEATURES[$feature]['label'].' requires '.FeatureToggleService::FEATURES[$requiredFeature]['label'].'.'])->withInput();
                }
            }
        }

        foreach (FeatureToggleService::FEATURES as $id => $config) {
            if (! FeatureToggleService::clientManageable($id)) {
                continue;
            }
            SystemSetting::set($config['key'], $request->boolean('features.'.$id));
        }

        DB::table('client_deployment_versions')->insert([
            'client_name' => trim($data['client_name']),
            'edition' => trim($data['edition']),
            'application_version' => trim($data['application_version']),
            'release_channel' => $data['release_channel'],
            'feature_snapshot' => json_encode($requested->all()),
            'change_reason' => trim($data['change_reason']),
            'changed_by' => (int) $request->user()->id,
            'created_at' => now(),
        ]);

        $audit->log('updated', 'ClientDeploymentProfile', null, 'Updated client version and feature profile: '.$data['change_reason'], $oldProfile, [
            'client_name' => $data['client_name'],
            'edition' => $data['edition'],
            'application_version' => $data['application_version'],
            'release_channel' => $data['release_channel'],
        ]);

        return back()->with('success', 'Client version and feature profile updated. The change was audit logged.');
    }
}
