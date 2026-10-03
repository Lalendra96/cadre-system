<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\InternAssignment;

class CurrentInternAssignmentsController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();

        $assignments = InternAssignment::query()
            ->whereHas('batch', function ($query) use ($today): void {
                $query->where('is_active', true)
                    ->where(function ($dateQuery) use ($today): void {
                        $dateQuery->whereNull('start_date')->orWhereDate('start_date', '<=', $today);
                    })
                    ->where(function ($dateQuery) use ($today): void {
                        $dateQuery->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
                    });
            })
            ->whereHas('intern', fn ($query) => $query->where('is_active', true))
            ->with(['intern', 'rotationUnit', 'batch.assignedSubjectOfficer'])
            ->latest('assigned_at')
            ->paginate(50);

        return view('intern-batches.current-readonly', compact('assignments'));
    }
}
