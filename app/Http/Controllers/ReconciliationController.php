<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ApprovedCarder;
use App\Models\CarderMonthlyEntry;
use App\Models\Employee;
use App\Models\Position;
use App\Models\UnitPositionAllocation;
use Illuminate\Http\Request;

class ReconciliationController extends Controller
{
    public function index(Request $request)
    {
        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);
        $positions = Position::orderBy('title')->get();
        $approved = ApprovedCarder::forYear($year)->get()->groupBy('position_id')->map->sum('approved_amount');
        $unit = UnitPositionAllocation::forYear($year)->mainLine()->get()->groupBy('position_id')->map->sum('actual_in_post');
        $employees = Employee::where('is_active', true)->whereNotNull('position_id')->get()->groupBy('position_id')->map->count();
        $monthly = CarderMonthlyEntry::forPeriodWithCarryForward($year, $month)->groupBy('position_id')->map->sum('in_position');
        $rows = $positions->map(function ($p) use ($approved, $unit, $employees, $monthly) {
            $vals = ['approved' => (int) ($approved[$p->id] ?? 0), 'unit' => (int) ($unit[$p->id] ?? 0), 'employees' => (int) ($employees[$p->id] ?? 0), 'monthly' => (int) ($monthly[$p->id] ?? 0)];
            $operational = [$vals['unit'], $vals['employees'], $vals['monthly']];
            $spread = max($operational) - min($operational);
            $baseline = max($vals['approved'], 1);
            $spreadPct = round(($spread / $baseline) * 100, 1);
            $severity = $spread === 0 ? 'aligned' : ($spreadPct >= 20 ? 'critical' : ($spreadPct >= 10 ? 'high' : 'review'));

            return ['p' => $p, 'vals' => $vals, 'mismatch' => $spread > 0, 'spread' => $spread, 'spread_pct' => $spreadPct, 'severity' => $severity,
                'employee_vs_monthly' => $vals['employees'] - $vals['monthly'], 'unit_vs_employee' => $vals['unit'] - $vals['employees']];
        })->filter(fn ($r) => array_sum($r['vals']) > 0)->sortByDesc(fn ($r) => [$r['mismatch'] ? 1 : 0, $r['spread_pct']])->values();
        $summary = ['positions' => $rows->count(), 'mismatched' => $rows->where('mismatch', true)->count(), 'critical' => $rows->where('severity', 'critical')->count(), 'max_spread' => (int) ($rows->max('spread') ?? 0)];

        return view('workforce.reconciliation', compact('rows', 'year', 'month', 'summary'));
    }
}
