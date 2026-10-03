<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AdministrativeIntelligenceService;
use Illuminate\Http\Request;

class InternalWorkforceApiController extends Controller
{
    public function summary(Request $request)
    {
        abort_unless(
            $request->user()?->is_active &&
                ($request->user()->isSuperAdmin() ||
                    $request->user()->isPlanningOfficer() ||
                    $request->user()->isAdminGroup()),
            403,
        );
        $data = $request->validate([
            'year' => 'nullable|integer|min:2000|max:2200',
            'months' => 'nullable|integer|min:1|max:60',
        ]);

        return response()->json([
            'data' => AdministrativeIntelligenceService::summary(
                (int) ($data['year'] ?? now()->year),
                (int) ($data['months'] ?? 12),
                0,
                0,
            ),
            'read_only' => true,
        ]);
    }
}
