<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Models\UserCategory;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

/**
 * Super Admin only — manage system-wide configuration settings.
 * Exposes configurable knobs for Features 1 (deadline), 7 (alerts),
 * 10 (verification), and Security 16–18.
 */
class SystemSettingController extends Controller
{
    public function index()
    {
        $groups = [
            'deadlines'    => SystemSetting::group('deadlines'),
            'verification' => SystemSetting::group('verification'),
            'alerts'       => SystemSetting::group('alerts'),
            'security'     => SystemSetting::group('security'),
        ];

        // Available categories for the category-based verifier picker
        $verifierCategories = \App\Models\UserCategory::active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'sort_order']);

        return view('admin.settings', compact('groups', 'verifierCategories'));
    }

    public function update(Request $request)
    {
        $allowed = [
            // Deadlines
            'deadline_enforcement_enabled', 'deadline_day', 'deadline_grace_days',
            // Verification
            'entry_verification_required', 'entry_verifier_role', 'entry_verifier_category_ids',
            // Alerts
            'vacancy_alert_enabled', 'vacancy_alert_threshold_pct',
            // Security
            'concurrent_session_lock_enabled', 'ip_allowlist_enabled', 'export_audit_enabled',
            'login_show_username',
        ];

        $request->validate([
            'deadline_day'                  => ['nullable','integer','min:1','max:28'],
            'deadline_grace_days'           => ['nullable','integer','min:0','max:14'],
            'entry_verifier_role'            => ['nullable','in:planning_officer,super_admin,category'],
            'entry_verifier_category_ids'    => ['nullable','array'],
            'entry_verifier_category_ids.*'  => ['integer','exists:user_categories,id'],
            'vacancy_alert_threshold_pct'   => ['nullable','integer','min:1','max:100'],
        ]);

        $old = [];
        foreach ($allowed as $key) {
            $old[$key] = SystemSetting::get($key);

            // JSON array setting — serialize before storing
            if ($key === 'entry_verifier_category_ids') {
                $ids = array_map('intval', $request->input($key, []));
                SystemSetting::set($key, json_encode(array_values($ids)));
                continue;
            }

            if ($request->has($key)) {
                $value = $request->input($key);
                $row   = SystemSetting::find($key);

                if ($row && $row->type === 'boolean') {
                    $value = $request->boolean($key);
                }

                SystemSetting::set($key, $value);
            } else {
                $row = SystemSetting::find($key);
                if ($row && $row->type === 'boolean') {
                    SystemSetting::set($key, false);
                }
            }
        }

        // Log the setting change (audit)
        \App\Services\AuditLogService::logExport(
            $request->user()->id, 'settings_update',
            'system_settings', null, null, null,
            $request->ip(), $request->userAgent()
        );

        return redirect()->route('admin.settings')->with('success', 'Settings saved successfully.');
    }
}
