<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeTrainingRecord;
use App\Services\WorkforceScopeService;
use Illuminate\Http\Request;

class EmployeeTrainingController extends Controller
{
    public function index(Request $request, Employee $employee)
    {
        WorkforceScopeService::authorizeEmployee($request->user(), $employee);
        $training = EmployeeTrainingRecord::where('employee_id', $employee->id)->latest('completed_on')->get();

        return view('employee-training.index', compact('employee', 'training'));
    }

    public function store(Request $request, Employee $employee)
    {
        WorkforceScopeService::authorizeEmployee($request->user(), $employee);
        $data = $request->validate([
            'course_name' => 'required|string|max:180',
            'provider' => 'nullable|string|max:180',
            'completed_on' => 'nullable|date',
            'expires_on' => 'nullable|date|after_or_equal:completed_on',
            'certificate_reference' => 'nullable|string|max:100',
        ]);
        EmployeeTrainingRecord::create($data + ['employee_id' => $employee->id, 'recorded_by' => $request->user()->id]);

        return redirect()->route('employee-training.index', $employee)->with('success', 'Training record added.');
    }
}
