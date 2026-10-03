<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\RoleDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleDashboardController extends Controller
{
    public function __construct(private readonly RoleDashboardService $dashboardService) {}

    public function index(Request $request): View
    {
        $user = $request->user()->loadMissing(['category', 'userRoles']);

        return view('dashboard.role-analytics', $this->dashboardService->data($user));
    }

    public function saveLayout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'layout' => ['present', 'array'],
            'layout.*' => ['string', 'max:100'],
        ]);

        $layout = $this->dashboardService->saveLayout(
            $request->user(),
            $validated['layout']
        );

        AuditLog::query()->create([
            'user_id' => $request->user()->id,
            'action' => 'update',
            'auditable_type' => 'dashboard_layout',
            'auditable_id' => $request->user()->id,
            'description' => 'Updated personal dashboard widget layout.',
            'old_values' => null,
            'new_values' => [
                'role_key' => $this->dashboardService->roleKey($request->user()),
                'widget_count' => count($layout),
            ],
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'saved' => true,
            'layout' => $layout,
        ]);
    }

    public function saveQuickLinks(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'quick_links' => ['nullable', 'array', 'max:12'],
            'quick_links.*' => ['string', 'max:100'],
        ]);

        $quickLinks = $this->dashboardService->saveQuickLinks(
            $request->user(),
            $validated['quick_links'] ?? []
        );

        AuditLog::query()->create([
            'user_id' => $request->user()->id,
            'action' => 'update',
            'auditable_type' => 'dashboard_quick_links',
            'auditable_id' => $request->user()->id,
            'description' => 'Updated personal dashboard quick links.',
            'old_values' => null,
            'new_values' => [
                'role_key' => $this->dashboardService->roleKey($request->user()),
                'quick_link_count' => count($quickLinks),
            ],
            'ip_address' => $request->ip(),
        ]);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Quick Links updated.');
    }

    public function resetLayout(Request $request): RedirectResponse
    {
        $layout = $this->dashboardService->defaultLayout($request->user());
        $this->dashboardService->saveLayout($request->user(), $layout);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Dashboard layout restored to the role default.');
    }
}
