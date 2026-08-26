<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ApprovedCarder;
use App\Models\Position;
use App\Models\UnitType;
use App\Services\UnitBreakdownCalculator;
use Illuminate\Http\Request;

/**
 * Unit-wise breakdown of current staff availability per position.
 *
 * Designed for Medical Officer (Planning) to identify which units
 * are understaffed for each position. Data source: Employee profiles
 * (not monthly entries) so the breakdown reflects individual post
 * occupancy rather than aggregate figures.
 *
 * Accessible to: Super Admin, Planning Officer, Admin Group.
 *
 * See App\Services\UnitBreakdownCalculator for the actual computation —
 * both index() and exportPdf() here just call it with the request's
 * filters and hand the result to their respective views.
 */
class UnitBreakdownController extends Controller
{
    public function index(Request $request)
    {
        $year       = (int) $request->query('year', now()->year);
        $unitTypeId = $request->query('unit_type_id');
        $positionId = $request->query('position_id');

        $calc = UnitBreakdownCalculator::calculate($year, $unitTypeId ? (int) $unitTypeId : null, $positionId ? (int) $positionId : null);

        $threshold         = $calc->threshold;
        $criticalPositions = $calc->rows->filter(fn ($r) =>
            $r->approvedAmt > 0 && $r->vacancy / max($r->approvedAmt, 1) * 100 >= $threshold
        );

        $unitTypes    = UnitType::active()->ordered()->get();
        $allPositions = Position::active()->orderBy('title')->get(['id', 'title']);
        $years        = ApprovedCarder::select('year')->distinct()->orderByDesc('year')->pluck('year');

        return view('reports.unit-breakdown', [
            'rows'              => $calc->rows,
            'units'             => $calc->units,
            'positions'         => $calc->positions,
            'criticalPositions' => $criticalPositions,
            'unitTypes'         => $unitTypes,
            'allPositions'      => $allPositions,
            'unitTypeId'        => $unitTypeId,
            'positionId'        => $positionId,
            'year'              => $year,
            'years'             => $years,
            'threshold'         => $threshold,
            'hasAllocationData' => $calc->hasAllocationData,
        ]);
    }

    /**
     * PDF export — re-runs the calculation fresh from the request's query
     * parameters rather than sharing state with index(), so the PDF always
     * reflects exactly what the URL specifies regardless of prior page state.
     */
    public function exportPdf(Request $request)
    {
        $year       = (int) $request->query('year', now()->year);
        $unitTypeId = $request->query('unit_type_id');
        $positionId = $request->query('position_id');

        $calc = UnitBreakdownCalculator::calculate($year, $unitTypeId ? (int) $unitTypeId : null, $positionId ? (int) $positionId : null);

        return \App\Services\PdfExportService::make(
            view: 'pdf.unit-breakdown',
            data: [
                'rows'           => $calc->rows,
                'units'          => $calc->units,
                'positions'      => $calc->positions,
                'threshold'      => $calc->threshold,
                'reportTitle'    => 'Unit-wise Post Availability',
                'reportSubtitle' => "Year {$year}",
            ],
            filename: "unit-breakdown-{$year}.pdf",
            orientation: 'landscape',
            userId: $request->user()->id,
            exportType: 'unit_breakdown_pdf',
        )->download($request->ip(), $request->userAgent());
    }
}
