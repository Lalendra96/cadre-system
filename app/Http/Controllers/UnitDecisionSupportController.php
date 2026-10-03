<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\DecisionSupportService;
use Illuminate\Http\Request;

/**
 * Unit-level decision support for assigned Unit Managers.
 *
 * The screen is aggregate-only by design: it does not list employee names,
 * NICs, service-file numbers or direct profile links. It is intended to help
 * unit leadership identify workload/risk signals and escalate through the
 * governed HR workflows rather than make consequential personnel decisions
 * directly from dashboard indicators.
 */
class UnitDecisionSupportController extends Controller
{
    public function index(Request $request, DecisionSupportService $decisionSupport)
    {
        $user = $request->user()->loadMissing('decisionUnits');
        $units = $user->decisionUnits->where('is_active', true)->values();

        $pulse = $decisionSupport->unitPulse($units);

        return view('dashboard.unit-manager', [
            'asAt' => now(),
            'units' => $units,
            ...$pulse,
        ]);
    }
}
