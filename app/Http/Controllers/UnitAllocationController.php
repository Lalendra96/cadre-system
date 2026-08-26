<?php

namespace App\Http\Controllers;

use App\Models\ApprovedCarder;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Unit;
use App\Models\UnitPositionAllocation;
use App\Models\UnitType;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Unit Post Allocation — lets Planning Officer / MO Planning enter and
 * maintain the headcount breakdown per Unit × Position per year.
 *
 * Access: super_admin, planning_officer, admin_group
 * Fine-grained admin_group restriction (MO Planning category) is handled
 * at route level via EnsureUserRole; no further gating needed here.
 */
class UnitAllocationController extends Controller
{
    // ── Index: list all active units with their completion status ─────────

    public function index(Request $request)
    {
        $year       = (int) $request->query('year', now()->year);
        $unitTypeId = $request->query('unit_type_id');

        $positions = Position::active()->orderBy('title')->get(['id', 'title']);

        $unitsQuery = Unit::active()
            ->with(['unitType', 'boundPositions:id', 'unitPositionAllocations' => fn ($q) => $q->forYear($year)])
            ->orderBy('name');

        if ($unitTypeId) {
            $unitsQuery->where('unit_type_id', $unitTypeId);
        }

        $units = $unitsQuery->get();

        // For each unit, compute how many positions have been filled in —
        // against THAT unit's own bound positions where configured, not
        // the full hospital-wide catalog. A unit with no bindings yet
        // falls back to the full catalog, matching the same principle
        // used in UnitAllocationController::edit() and
        // UnitBreakdownCalculator — bindings scope relevance, they never
        // silently inflate or deflate a denominator for an unconfigured unit.
        $units->each(function ($unit) use ($positions, $year) {
            $relevantPositionIds = $unit->boundPositions->isNotEmpty()
                ? $unit->boundPositions->pluck('id')
                : $positions->pluck('id');

            $recorded = $unit->unitPositionAllocations
                ->where('year', $year)
                ->whereNotNull('allocated_posts')
                ->whereIn('position_id', $relevantPositionIds)
                ->unique('position_id')
                ->count();

            $unit->recorded_count   = $recorded;
            $unit->total_positions  = $relevantPositionIds->count();
            $unit->completion_pct   = $relevantPositionIds->count() > 0
                ? round($recorded / $relevantPositionIds->count() * 100)
                : 0;
        });

        $unitTypes = UnitType::active()->ordered()->get(['id', 'name']);
        $years     = collect(range(now()->year - 2, now()->year + 1))->sort()->values();

        return view('unit-allocations.index', compact('units', 'positions', 'year', 'unitTypes', 'unitTypeId', 'years'));
    }

    // ── Edit: show the allocation grid for one unit ───────────────────────

    public function edit(Request $request, Unit $unit)
    {
        $year = (int) $request->query('year', now()->year);

        // Scope to bound positions if Super Admin has configured any for
        // this unit; otherwise fall back to every active position, so a
        // unit nobody has gotten around to configuring yet still works
        // exactly as before — bindings are a scoping aid, never a gate
        // that can hide data entry.
        $boundIds = $unit->boundPositions()->pluck('positions.id');
        $positionsQuery = Position::active()->with(['subcategories' => fn ($q) => $q->active()->orderBy('sort_order')])->orderBy('title');
        if ($boundIds->isNotEmpty()) {
            $positionsQuery->whereIn('id', $boundIds);
        }
        $positions = $positionsQuery->get();
        $isScoped  = $boundIds->isNotEmpty();

        // Existing allocations for this unit/year — keyed by position_id for O(1) access.
        // Main-line rows only (subcategory rows are keyed separately below).
        $existing = UnitPositionAllocation::forUnit($unit->id)
            ->forYear($year)
            ->mainLine()
            ->get()
            ->keyBy('position_id');

        // Existing subcategory-level allocations, keyed by "positionId:subcategoryId"
        // so the view can look each one up directly without a nested loop per row.
        $existingSubcategoryRows = UnitPositionAllocation::forUnit($unit->id)
            ->forYear($year)
            ->subcategoryLines()
            ->get()
            ->keyBy(fn ($row) => "{$row->position_id}:{$row->position_subcategory_id}");

        // Employee-profile counts for this unit: position_id → count
        // Shown as a read-only reference column so the officer can see
        // what the system thinks vs what they're entering.
        $profileCounts = Employee::where('unit_id', $unit->id)
            ->whereNotNull('position_id')
            ->select('position_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('position_id')
            ->pluck('cnt', 'position_id');

        // Approved hospital-wide carder for reference
        $approvedCarder = ApprovedCarder::forYear($year)
            ->get()
            ->groupBy('position_id')
            ->map(fn ($rows) => (int) $rows->sum('approved_amount'));

        $years = collect(range(now()->year - 2, now()->year + 1))->sort()->values();

        return view('unit-allocations.edit', compact(
            'unit', 'positions', 'existing', 'existingSubcategoryRows', 'profileCounts',
            'approvedCarder', 'year', 'years', 'isScoped'
        ));
    }

    // ── Update: save the entire grid for one unit/year ───────────────────

    public function update(Request $request, Unit $unit)
    {
        $year = (int) $request->input('year', now()->year);

        $request->validate([
            'year'                            => ['required', 'integer', 'min:2020', 'max:2040'],
            'rows'                            => ['nullable', 'array'],
            'rows.*.position_id'              => ['required', 'integer', 'exists:positions,id'],
            'rows.*.allocated_posts'          => ['required', 'integer', 'min:0', 'max:9999'],
            'rows.*.actual_in_post'           => ['required', 'integer', 'min:0', 'max:9999'],
            'rows.*.notes'                    => ['nullable', 'string', 'max:300'],
            'sub_rows'                        => ['nullable', 'array'],
            'sub_rows.*.position_id'          => ['required', 'integer', 'exists:positions,id'],
            'sub_rows.*.subcategory_id'       => ['required', 'integer', 'exists:position_subcategories,id'],
            'sub_rows.*.allocated_posts'      => ['required', 'integer', 'min:0', 'max:9999'],
            'sub_rows.*.actual_in_post'       => ['required', 'integer', 'min:0', 'max:9999'],
            'sub_rows.*.notes'                => ['nullable', 'string', 'max:300'],
        ]);

        $rows    = $request->input('rows', []);
        $subRows = $request->input('sub_rows', []);

        // Extra: actual_in_post cannot exceed allocated_posts by more than 50%
        // without a note — catches obvious typos. Applied to main rows only;
        // subcategory rows are a smaller-scale breakdown where this heuristic
        // is less meaningful (e.g. a PGIM subcategory with 0 allocated but
        // several actually attached is completely normal, not a typo).
        $errors = [];
        foreach ($rows as $i => $row) {
            $alloc  = (int) $row['allocated_posts'];
            $actual = (int) $row['actual_in_post'];
            if ($alloc > 0 && $actual > $alloc * 1.5 && empty(trim($row['notes'] ?? ''))) {
                $errors["rows.{$i}.notes"] = 'Actual headcount is more than 50% above allocated posts — add a note to explain (e.g. "temporary overcrowding").';
            }
        }

        if (! empty($errors)) {
            return back()->withErrors($errors)->withInput()->with('error',
                'Some rows need attention before saving.'
            );
        }

        DB::transaction(function () use ($unit, $year, $rows, $subRows, $request) {
            foreach ($rows as $row) {
                UnitPositionAllocation::updateOrCreate(
                    [
                        'unit_id'                  => $unit->id,
                        'position_id'              => $row['position_id'],
                        'position_subcategory_id'  => null, // explicit — without this, the match could hit a subcategory row instead of the main line
                        'year'                      => $year,
                    ],
                    [
                        'allocated_posts'  => (int) $row['allocated_posts'],
                        'actual_in_post'   => (int) $row['actual_in_post'],
                        'notes'            => filled($row['notes'] ?? '') ? trim($row['notes']) : null,
                        'last_updated_by'  => $request->user()->id,
                    ]
                );
            }

            foreach ($subRows as $subRow) {
                UnitPositionAllocation::updateOrCreate(
                    [
                        'unit_id'                  => $unit->id,
                        'position_id'              => $subRow['position_id'],
                        'position_subcategory_id'  => $subRow['subcategory_id'],
                        'year'                      => $year,
                    ],
                    [
                        'allocated_posts'  => (int) $subRow['allocated_posts'],
                        'actual_in_post'   => (int) $subRow['actual_in_post'],
                        'notes'            => filled($subRow['notes'] ?? '') ? trim($subRow['notes']) : null,
                        'last_updated_by'  => $request->user()->id,
                    ]
                );
            }
        });

        AuditLogService::logExport(
            $request->user()->id,
            'unit_allocation_update',
            "unit-allocation-{$unit->id}-{$year}",
            'Unit', $unit->id,
            null,
            $request->ip(),
            $request->userAgent()
        );

        return redirect()
            ->route('unit-allocations.edit', [$unit, 'year' => $year])
            ->with('success', "Headcount allocations for {$unit->name} ({$year}) saved successfully.");
    }
}
