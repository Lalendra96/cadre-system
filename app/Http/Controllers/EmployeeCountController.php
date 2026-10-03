<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Employee;

class EmployeeCountController extends Controller
{
    public function index()
    {
        $counts = Employee::query()
            ->where('is_active', true)
            ->whereNotNull('position_id')
            ->with('position:id,title')
            ->get()
            ->groupBy('position.title')
            ->map->count()
            ->sortDesc();

        return view('workforce.employee-counts', compact('counts'));
    }
}
