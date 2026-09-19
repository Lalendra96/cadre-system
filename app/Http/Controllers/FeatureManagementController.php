<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Services\FeatureToggleService;
use Illuminate\Http\Request;

class FeatureManagementController extends Controller
{
    public function index()
    {
        $features = collect(FeatureToggleService::FEATURES)->map(fn($config,$id) => $config + ['id'=>$id,'enabled'=>FeatureToggleService::enabled($id)]);
        return view('admin.features', compact('features'));
    }

    public function update(Request $request)
    {
        foreach (FeatureToggleService::FEATURES as $id => $config) {
            SystemSetting::set($config['key'], $request->boolean('features.'.$id));
        }
        SystemSetting::set('ai_local_endpoint', trim((string)$request->input('ai_local_endpoint','')));
        SystemSetting::set('ai_local_model', trim((string)$request->input('ai_local_model','carder-local')) ?: 'carder-local');
        return back()->with('success', 'Feature configuration updated. Disabled modules are hidden and route-protected.');
    }
}
