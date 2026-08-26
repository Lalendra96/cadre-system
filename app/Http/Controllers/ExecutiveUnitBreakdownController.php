<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ApprovedCarder;
use App\Services\UnitBreakdownCalculator;
use App\Services\UnitBreakdownNarrativeService;
use Illuminate\Http\Request;

/**
 * Executive Unit-wise Breakdown — read-only summary for Director, Deputy
 * Director, and Deputy Director General only (see User::isExecutiveViewer()).
 *
 * Distinct from UnitBreakdownController (the operational report available
 * more broadly to Admin Group / Planning Officer / Super Admin): this view
 * leads with a plain-language narrative digest rather than the raw matrix,
 * has no filters/export actions, and no path to any write action anywhere
 * in this controller — it is intentionally view-only, matching the request
 * this was built for.
 *
 * Shares its underlying computation with UnitBreakdownController via
 * UnitBreakdownCalculator, so the two views can never silently disagree
 * on the numbers.
 */
class ExecutiveUnitBreakdownController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isExecutiveViewer(), 403,
            'This summary is available to the Director, Deputy Director, and Deputy Director General only.'
        );

        $year = (int) $request->query('year', now()->year);

        $calc = UnitBreakdownCalculator::calculate($year, respectBindings: true);
        $narrative = UnitBreakdownNarrativeService::summarize($calc, $year);

        $years = ApprovedCarder::select('year')->distinct()->orderByDesc('year')->pluck('year');

        return view('executive.unit-breakdown', [
            'rows'              => $calc->rows,
            'units'             => $calc->units,
            'threshold'         => $calc->threshold,
            'hasAllocationData' => $calc->hasAllocationData,
            'narrative'         => $narrative,
            'year'              => $year,
            'years'             => $years,
        ]);
    }
}
