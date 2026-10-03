<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\EmployeeIncrement;
use App\Services\WorkforceScopeService;
use Illuminate\Http\Request;

class IncrementDashboardController extends Controller
{
    public function index(Request $request)
    {
        $allowedIds = WorkforceScopeService::employeeQuery($request->user())->pluck('id');
        $base = EmployeeIncrement::query()->active()->whereIn('employee_id', $allowedIds)->with(['employee.position', 'employee.unit', 'employee.subjectCode']);
        $pending = (clone $base)->whereDate('increment_date', '>=', now()->toDateString())->orderBy('increment_date')->paginate(25);
        $due30 = (clone $base)->whereBetween('increment_date', [now()->toDateString(), now()->addDays(30)->toDateString()])->count();
        $due90 = (clone $base)->whereBetween('increment_date', [now()->toDateString(), now()->addDays(90)->toDateString()])->count();
        $overdue = (clone $base)->whereDate('increment_date', '<', now()->toDateString())->count();
        $notified = (clone $base)->whereNotNull('notified_at')->count();
        $isScoped = $request->user()->isSubjectOfficer() && ! $request->user()->isSuperAdmin() && ! $request->user()->isPlanningOfficer();

        return view('increments.dashboard', compact('pending', 'due30', 'due90', 'overdue', 'notified', 'isScoped'));
    }
}
