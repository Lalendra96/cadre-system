<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\EmployeeChangeRequest;
use Illuminate\Http\Request;

class EmployeeSelfServiceController extends Controller
{
    public function index(Request $request)
    {
        $employee = $request
            ->user()
            ->employee()
            ->with(['position', 'unit', 'documents', 'incrementRecords', 'gradeRecords'])
            ->firstOrFail();
        $requests = EmployeeChangeRequest::where('employee_id', $employee->id)->latest()->get();

        return view('employee-self-service.index', compact('employee', 'requests'));
    }

    public function acknowledgeRetirement(Request $request)
    {
        $employee = $request->user()->employee()->firstOrFail();
        $project = $employee->retirementProjects()->latest()->firstOrFail();
        $project->update(['employee_acknowledged_date' => today()]);

        return back()->with('success', 'Retirement notice acknowledgement recorded.');
    }
}
