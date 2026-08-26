<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\UnitBreakdownCalculator;
use Illuminate\Http\Request;

/**
 * Presentation Mode — a PUBLIC, unauthenticated, TV/kiosk-style display of
 * hospital-wide cadre statistics. No login required, by design (e.g. a
 * lobby or planning-office display screen).
 *
 * DATA SECURITY: this is the one screen in the whole system explicitly
 * built for an unauthenticated audience, so it is scoped deliberately
 * narrow — aggregate numbers only:
 *   - Position-level totals (title + counts), never per-unit breakdown
 *   - Unit-TYPE-level aggregates (e.g. "A & E", "LAB" — generic category
 *     labels), never individual unit names like "Ward 7 (Female)"
 *   - No notes/remarks fields (those can contain free text like staff
 *     names or leave details entered by Planning Officer)
 *   - No employee data of any kind
 * If a future request wants finer detail here, that is a fresh data-
 * exposure decision, not an assumption to extend quietly.
 *
 * Uses UnitBreakdownCalculator in respectBindings mode — the same
 * "curated, not raw" mode built for the Executive Summary — since a
 * passive public display has the same "clean at-a-glance numbers, not
 * operational noise" requirement, not the operational report's "show
 * everything so nothing is hidden from Planning Officer" requirement.
 */
class PresentationController extends Controller
{
    public function index(Request $request)
    {
        $year = (int) $request->query('year', now()->year);

        $calc = UnitBreakdownCalculator::calculate($year, respectBindings: true);

        $totalApproved = $calc->rows->sum('approvedAmt');
        $totalActual   = $calc->rows->sum('totalInPos');
        $totalVacancy  = $calc->rows->sum('vacancy');
        $overallFillPct = $totalApproved > 0 ? (int) round($totalActual / $totalApproved * 100) : 0;

        // Top 10 positions by absolute vacancy — a ranking, so a
        // horizontal bar chart (per standard chart-selection guidance:
        // ranking/many-category comparisons read better horizontally).
        $topVacancies = $calc->rows
            ->filter(fn ($r) => $r->approvedAmt > 0)
            ->sortByDesc('vacancy')
            ->take(10)
            ->map(fn ($r) => [
                'label'    => $r->position->title,
                'approved' => $r->approvedAmt,
                'actual'   => $r->totalInPos,
                'vacancy'  => $r->vacancy,
                'fillPct'  => $r->fillPct,
            ])->values();

        // Approved vs actual aggregated by UNIT TYPE (generic category
        // labels, e.g. "A & E", "LAB") — never by individual unit name.
        // Built from the single $calc result already computed above —
        // unitCells is index-aligned to $calc->units — rather than
        // re-invoking the calculator once per unit type, which would
        // mean 20+ full recalculations of the entire hospital just to
        // build one summary chart.
        $unitTypeNames = $calc->units->map(fn ($u) => $u->unitType->name ?? 'Other');
        $approvedByType = [];
        $actualByType   = [];
        foreach ($calc->rows as $row) {
            foreach ($row->unitCells as $i => $cellActual) {
                $typeName = $unitTypeNames[$i] ?? 'Other';
                $actualByType[$typeName] = ($actualByType[$typeName] ?? 0) + $cellActual;
            }
            // approvedAmt is hospital-wide per position (ApprovedCarder has
            // no unit dimension), so it can't be split by unit type the
            // same way — omitted from this chart rather than guessed at.
        }
        $byUnitType = collect($actualByType)
            ->map(fn ($actual, $label) => ['label' => $label, 'actual' => $actual])
            ->filter(fn ($row) => $row['actual'] > 0)
            ->sortByDesc('actual')
            ->values();

        return view('presentation.index', compact(
            'year', 'totalApproved', 'totalActual', 'totalVacancy', 'overallFillPct',
            'topVacancies', 'byUnitType'
        ));
    }
}
