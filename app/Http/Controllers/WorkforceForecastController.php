<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ApprovedCarder;
use App\Models\Employee;
use App\Models\Position;
use App\Models\TransferRecord;
use Illuminate\Http\Request;

class WorkforceForecastController extends Controller
{
    public function index(Request $request)
    {
        $year = (int) $request->query('year', now()->year);
        $months = max(1, min(60, (int) $request->query('months', 12)));
        $until = now()->copy()->addMonths($months);
        $positions = Position::orderBy('title')->get();
        $approved = ApprovedCarder::forYear($year)->get()->groupBy('position_id')->map->sum('approved_amount');
        $active = Employee::where('is_active', true)->whereNotNull('position_id')->get();
        $current = $active->groupBy('position_id')->map->count();
        $retiring = $active->filter(fn ($e) => $e->retire_date && $e->retire_date->between(now(), $until))->groupBy('position_id')->map->count();
        $transferOut = TransferRecord::where('direction', 'out')->whereBetween('effective_date', [now()->toDateString(), $until->toDateString()])->whereNotNull('employee_id')->with('employee:id,position_id')->get()->groupBy(fn ($t) => $t->employee?->position_id)->map->count();
        $transferIn = TransferRecord::where('direction', 'in')->whereBetween('effective_date', [now()->toDateString(), $until->toDateString()])->whereNotNull('employee_id')->with('employee:id,position_id')->get()->groupBy(fn ($t) => $t->employee?->position_id)->map->count();
        $rows = $positions->map(function ($p) use ($approved, $current, $retiring, $transferOut, $transferIn) {
            $a = (int) ($approved[$p->id] ?? 0);
            $c = (int) ($current[$p->id] ?? 0);
            $r = (int) ($retiring[$p->id] ?? 0);
            $o = (int) ($transferOut[$p->id] ?? 0);
            $i = (int) ($transferIn[$p->id] ?? 0);
            $future = max(0, $c - $r - $o + $i);
            $vac = max(0, $a - $future);
            $fill = $a > 0 ? round(($future / $a) * 100, 1) : null;
            $retireExposure = $c > 0 ? round(($r / $c) * 100, 1) : 0;
            $risk = ($a > 0 && $fill < 70) || $retireExposure >= 25 ? 'critical' : (($a > 0 && $fill < 85) || $retireExposure >= 15 ? 'high' : (($vac > 0 || $retireExposure >= 8) ? 'watch' : 'stable'));

            return ['position' => $p, 'approved' => $a, 'current' => $c, 'retiring' => $r, 'transfer_out' => $o, 'transfer_in' => $i, 'future_headcount' => $future, 'projected_vacancy' => $vac, 'projected_fill_rate' => $fill, 'retirement_exposure' => $retireExposure, 'risk' => $risk];
        })->filter(fn ($r) => $r['approved'] + $r['current'] + $r['retiring'] > 0)->sortByDesc(fn ($r) => [$r['risk'] === 'critical' ? 3 : ($r['risk'] === 'high' ? 2 : ($r['risk'] === 'watch' ? 1 : 0)), $r['projected_vacancy']])->values();
        $summary = ['positions' => $rows->count(), 'critical' => $rows->where('risk', 'critical')->count(), 'high' => $rows->where('risk', 'high')->count(), 'retiring' => $rows->sum('retiring'), 'projected_vacancies' => $rows->sum('projected_vacancy')];

        return view('workforce.forecast',compact('rows','months','year','summary'));
    }
}
