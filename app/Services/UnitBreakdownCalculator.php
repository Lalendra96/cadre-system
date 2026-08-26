<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ApprovedCarder;
use App\Models\CarderMonthlyEntry;
use App\Models\Employee;
use App\Models\Position;
use App\Models\SystemSetting;
use App\Models\Unit;
use App\Models\UnitPositionAllocation;

/**
 * Computes the unit x position headcount/vacancy matrix — extracted from
 * UnitBreakdownController, which previously duplicated this exact ~60-line
 * calculation between index() and exportPdf(). Now used by THREE consumers
 * (those two, plus ExecutiveUnitBreakdownController), which is the point
 * past which duplication stops being "simpler" and starts being a real
 * maintenance risk — a bug fixed in one copy and not the other is exactly
 * how these two computations would have quietly drifted apart over time.
 *
 * DATA SOURCE PRIORITY (unchanged from the original):
 *   Unit Post Allocation entry (Planning Officer's own headcount figure)
 *   takes priority over the Employee-profile-derived count for a given
 *   unit+position cell. Falls back to the profile count only where no
 *   allocation has been entered.
 */
class UnitBreakdownCalculator
{
    /**
     * @return object{
     *   rows: \Illuminate\Support\Collection,
     *   units: \Illuminate\Support\Collection,
     *   positions: \Illuminate\Support\Collection,
     *   threshold: int,
     *   hasAllocationData: bool,
     * }
     */
    public static function calculate(int $year, ?int $unitTypeId = null, ?int $positionId = null, bool $respectBindings = false): object
    {
        $approved = ApprovedCarder::forYear($year)
            ->get()
            ->groupBy('position_id')
            ->map(fn ($rows) => (int) $rows->sum('approved_amount'));

        $unitsQuery = Unit::active()->with(['unitType', 'boundPositions:id'])->orderBy('name');
        if ($unitTypeId) {
            $unitsQuery->where('unit_type_id', $unitTypeId);
        }
        $units = $unitsQuery->get();

        $positionsQuery = Position::active()->orderBy('title');
        if ($positionId) {
            $positionsQuery->where('id', $positionId);
        }
        $positions = $positionsQuery->get();

        $employeeQuery = Employee::with(['position', 'unit'])
            ->whereNotNull('position_id')
            ->whereNotNull('unit_id');
        if ($positionId) {
            $employeeQuery->where('position_id', $positionId);
        }
        if ($unitTypeId) {
            $employeeQuery->whereHas('unit', fn ($q) => $q->where('unit_type_id', $unitTypeId));
        }

        $profileCounts = [];
        foreach ($employeeQuery->get() as $emp) {
            $profileCounts[$emp->position_id][$emp->unit_id] =
                ($profileCounts[$emp->position_id][$emp->unit_id] ?? 0) + 1;
        }

        // ── Unit Post Allocation figures, split three ways ──────────────────
        //
        // With position_subcategory_id now nullable on this table, a plain
        // `where('year', $year)->get()` returns BOTH main-line rows AND
        // subcategory rows for the same position+unit — building a map keyed
        // only by position_id+unit_id would let one silently overwrite the
        // other depending on iteration order. Split explicitly instead:
        //
        //   1. Main-line rows (position_subcategory_id IS NULL) — the
        //      position's own base figure, exactly as before this feature.
        //   2. Subcategory rows where counts_toward_parent_total = true —
        //      ADDED on top of the main-line figure (e.g. a "Health
        //      Information Consultant" subcategory genuinely is part of
        //      the parent Medical Consultant headcount).
        //   3. Subcategory rows where counts_toward_parent_total = false —
        //      tracked and shown as a remark, NEVER added to any total
        //      (this is how PGIM trainees are represented: visible, but
        //      not inflating the main Medical Officer figure).
        $allocQuery = UnitPositionAllocation::where('year', $year)
            ->with('subcategory')
            ->whereHas('unit', fn ($q) => $q->where('is_active', true));
        if ($unitTypeId) {
            $allocQuery->whereHas('unit', fn ($q) => $q->where('unit_type_id', $unitTypeId));
        }
        if ($positionId) {
            $allocQuery->where('position_id', $positionId);
        }

        $mainAllocMap  = [];   // [position_id][unit_id] => UnitPositionAllocation (main line)
        $countedExtra  = [];   // [position_id][unit_id] => int (sum of counted-subcategory actual_in_post)
        $excludedNotes = [];   // [position_id][unit_id] => Collection of excluded-subcategory rows, for display

        foreach ($allocQuery->get() as $a) {
            if ($a->position_subcategory_id === null) {
                $mainAllocMap[$a->position_id][$a->unit_id] = $a;
                continue;
            }

            if ($a->subcategory && $a->subcategory->counts_toward_parent_total) {
                $countedExtra[$a->position_id][$a->unit_id] =
                    ($countedExtra[$a->position_id][$a->unit_id] ?? 0) + $a->actual_in_post;
            } else {
                $excludedNotes[$a->position_id][$a->unit_id][] = $a;
            }
        }

        $counts = [];
        foreach ($units as $unit) {
            foreach ($positions as $pos) {
                // Bindings-respecting mode (Executive Summary only): a
                // unit contributes to a position's total ONLY if that
                // exact position is bound to it. A unit with ZERO
                // bindings configured is excluded entirely here — not
                // fallback-included. Earlier this fell back to including
                // everything from an unconfigured unit ("undetermined
                // relevance"), which is the right call for the
                // operational entry grid (never hide real data) but was
                // exactly what let a single unconfigured unit inject its
                // full profile-count/fallback data into every position's
                // executive total, producing misleading aggregate
                // figures. The operational report (respectBindings=false)
                // is unaffected — it still shows everything, always.
                if ($respectBindings && ! $unit->boundPositions->contains('id', $pos->id)) {
                    $counts[$pos->id][$unit->id] = 0;
                    continue;
                }

                $mainAlloc = $mainAllocMap[$pos->id][$unit->id] ?? null;
                $base = $mainAlloc
                    ? $mainAlloc->actual_in_post
                    : ($profileCounts[$pos->id][$unit->id] ?? 0);
                $extra = $countedExtra[$pos->id][$unit->id] ?? 0;
                $counts[$pos->id][$unit->id] = $base + $extra;
            }
        }

        // ── Reconciliation against carder_monthly_entries ───────────────────
        // Subject Officers submit their own monthly aggregate per subject
        // code + position; Planning Officer/Admin Group separately maintain
        // the unit-level breakdown here. The two are entered independently
        // by different people and can drift apart — this computes the
        // hospital-wide monthly-entry total per position (same
        // carry-forward logic used everywhere else this figure appears) so
        // the two can be shown side by side and flagged when they disagree.
        //
        // Always uses the TRUE current month regardless of which $year is
        // being viewed, matching the same principle already applied to the
        // Quick Glance panel — a reconciliation check answers "does the
        // breakdown match reality right now," not "did it match on some
        // date tied to an unrelated filter."
        $monthlyEntryTotals = CarderMonthlyEntry::forPeriodWithCarryForward($year, now()->month)
            ->groupBy('position_id')
            ->map(fn ($rows) => $rows->sum('males') + $rows->sum('females') + $rows->sum('no_pay_leave'));

        $rows = $positions->map(function ($position) use ($counts, $units, $approved, $excludedNotes, $monthlyEntryTotals, $respectBindings) {
            $positionCounts = $counts[$position->id] ?? [];
            $totalInPos     = array_sum($positionCounts);
            $approvedAmt    = $approved->get($position->id, 0);
            $vacancy        = max($approvedAmt - $totalInPos, 0);
            $fillPct        = $approvedAmt > 0 ? (int) round(($totalInPos / $approvedAmt) * 100) : 0;
            $unitCells      = $units->map(fn ($u) => $positionCounts[$u->id] ?? 0);

            // Per-unit vacancy detail, used by the narrative summarizer AND
            // the Executive Summary's "weakest unit" calculation.
            //
            // FILTERED to relevant units only. In the default mode
            // (operational report), a unit is relevant if it's bound to
            // this position, has real actual data (never hidden regardless
            // of binding), OR simply has no bindings configured yet
            // (undetermined relevance, included rather than assumed
            // irrelevant).
            //
            // In respectBindings mode (Executive Summary only), that last
            // allowance is removed — a unit with ZERO bindings configured
            // is excluded outright, matching how $counts/totalInPos are
            // computed above. Including "undetermined" units here was
            // exactly what let a single unconfigured unit's fallback data
            // dominate a position's "weakest unit" pick with noise that
            // had nothing to do with a genuine staffing gap.
            //
            // Without any of this filtering, "weakest unit" was picking
            // from ~100+ units that were never relevant to the position at
            // all — every one of them legitimately has 0, so the badge was
            // effectively showing a near-random irrelevant unit, not a
            // genuine problem spot. unitCells (the wide comparison-matrix
            // table) is DELIBERATELY left unfiltered — its columns are
            // aligned by index position against a fixed unit header row,
            // so touching it would misalign every table that renders it.
            $unitBreakdown = $units->filter(function ($u) use ($positionCounts, $position, $respectBindings) {
                $actual = $positionCounts[$u->id] ?? 0;
                if ($respectBindings) {
                    return $u->boundPositions->contains('id', $position->id);
                }
                return $u->boundPositions->isEmpty()
                    || $u->boundPositions->contains('id', $position->id)
                    || $actual > 0;
            })->map(function ($u) use ($positionCounts) {
                return (object) [
                    'unit'   => $u,
                    'actual' => $positionCounts[$u->id] ?? 0,
                ];
            })->values();

            // Flags units where this position has real headcount data but
            // isn't in that unit's bound-positions list — a possible
            // data-entry mistake (e.g. someone allocated "Cardiologist" to
            // "Hospital Kitchen"). Only checked for units that actually
            // HAVE bindings configured (an empty boundPositions list means
            // "not scoped yet," not "nothing belongs here" — see
            // UnitAllocationController's identical fallback logic).
            $unboundDataWarnings = $units->filter(function ($u) use ($positionCounts, $position) {
                $actual = $positionCounts[$u->id] ?? 0;
                if ($actual <= 0 || $u->boundPositions->isEmpty()) {
                    return false;
                }
                return ! $u->boundPositions->contains('id', $position->id);
            })->map(fn ($u) => (object) [
                'unit'   => $u,
                'actual' => $positionCounts[$u->id] ?? 0,
            ])->values();

            // Excluded-from-total subcategory remarks (e.g. PGIM trainees),
            // flattened across every unit for this position — shown on the
            // breakdown as informational remarks, never folded into totalInPos.
            $excludedRemarks = collect($excludedNotes[$position->id] ?? [])
                ->flatten(1)
                ->map(fn ($row) => (object) [
                    'unit'         => $units->firstWhere('id', $row->unit_id),
                    'subcategory'  => $row->subcategory,
                    'actual'       => $row->actual_in_post,
                    'allocated'    => $row->allocated_posts,
                    'notes'        => $row->notes,
                ])
                ->filter(fn ($r) => $r->unit !== null)
                ->values();

            // Hospital-wide monthly-entry total for this same position — see
            // the computation above for why this can legitimately differ
            // from totalInPos (two independently-maintained figures), and
            // why "reconciles" is the more useful signal than either number
            // alone.
            //
            // hasMonthlyEntryData distinguishes "this position was never
            // submitted via any subject code's monthly entry at all" (e.g.
            // Electrician, Cook — purely unit-tracked support roles that
            // were never part of that workflow to begin with) from "it was
            // submitted, and the two figures disagree." Only the latter is
            // a real reconciliation problem — flagging the former on every
            // non-clinical position would just be noise that trains people
            // to ignore the warning.
            $hasMonthlyEntryData = $monthlyEntryTotals->has($position->id);
            $monthlyEntryTotal   = (int) $monthlyEntryTotals->get($position->id, 0);
            $reconciles          = ! $hasMonthlyEntryData || $monthlyEntryTotal === $totalInPos;

            return (object) compact(
                'position', 'approvedAmt', 'totalInPos',
                'vacancy', 'fillPct', 'unitCells', 'unitBreakdown', 'excludedRemarks',
                'monthlyEntryTotal', 'reconciles', 'hasMonthlyEntryData', 'unboundDataWarnings'
            );
        })->filter(fn ($r) => $r->approvedAmt > 0 || $r->totalInPos > 0)->values();

        $threshold = SystemSetting::getInt('vacancy_alert_threshold_pct', 20);

        return (object) [
            'rows'              => $rows,
            'units'             => $units,
            'positions'         => $positions,
            'threshold'         => $threshold,
            'hasAllocationData' => ! empty($mainAllocMap) || ! empty($countedExtra) || ! empty($excludedNotes),
        ];
    }
}
