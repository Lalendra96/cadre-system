<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Competency;
use App\Models\Employee;
use App\Models\EmployeeCompetency;
use App\Services\WorkforceScopeService;
use Illuminate\Http\Request;

class EmployeeCompetencyController extends Controller
{
    public function index(Request $request, Employee $employee)
    {
        WorkforceScopeService::authorizeEmployee($request->user(), $employee);
        $competencies = Competency::where('is_active', true)->orderBy('name')->get();
        $records = $employee->competencies()->with('competency')->latest('assessed_on')->get();

        return view('employee-competencies.index', compact('employee', 'competencies', 'records'));
    }

    public function store(Request $request, Employee $employee)
    {
        WorkforceScopeService::authorizeEmployee($request->user(), $employee);
        $data = $request->validate([
            'competency_id' => 'required|exists:competencies,id',
            'level' => 'required|in:basic,intermediate,advanced,expert',
            'assessed_on' => 'required|date',
            'expires_on' => 'nullable|date|after_or_equal:assessed_on',
            'notes' => 'nullable|string|max:2000',
        ]);
        EmployeeCompetency::updateOrCreate(
            ['employee_id' => $employee->id, 'competency_id' => $data['competency_id']],
            [...$data, 'assessed_by' => $request->user()->id],
        );

        return back()->with('success', 'Competency assessment saved.');
    }
}
