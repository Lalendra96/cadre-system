<?php

namespace App\Http\Controllers;

use App\Models\RosterAssignment;
use App\Models\RosterPlan;
use App\Models\RosterTemplate;
use Illuminate\Support\Facades\DB;

class RosterController extends Controller
{
    public function index()
    {
        $stats = [
            'templates' => RosterTemplate::where('is_active', true)->count(),
            'assignments' => DB::table('roster_assignments')
                ->whereBetween('duty_date', [
                    now()->startOfMonth()->toDateString(),
                    now()->endOfMonth()->toDateString(),
                ])
                ->count(),
            'pending' => RosterPlan::whereIn('status', ['submitted', 'in_approval'])->count(),
            'active' => RosterPlan::where('status', 'active')->count(),
        ];

        // Employee.name is protected PII. Always load employees through the
        // Employee Eloquent model so EncryptsPersonnelData can decrypt it.
        // A raw DB join would expose the encrypted database value in the UI.
        $upcoming = RosterAssignment::query()
            ->with([
                'employee:id,salutation,name,unit_id,position_id',
                'dutyUnit:id,name',
            ])
            ->whereDate('duty_date', '>=', today())
            ->orderBy('duty_date')
            ->orderBy('start_time')
            ->limit(20)
            ->get();

        return view('roster.dashboard.index', compact('stats', 'upcoming'));
    }
}
