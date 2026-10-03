<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeLifecycleEvent;
use App\Services\AuditLogService;
use App\Services\WorkforceScopeService;
use Illuminate\Http\Request;

class EmployeeLifecycleController extends Controller
{
    public function store(Request $request, Employee $employee)
    {
        WorkforceScopeService::authorizeEmployee($request->user(), $employee);
        $data = $request->validate([
            'event_type' => 'required|in:joined_service,active,on_leave,no_pay_leave,temporary_transfer,permanent_transfer,secondment,deputation,acting,interdicted,suspended,resigned,retired,deceased,promotion,other',
            'status' => 'nullable|string|max:50',
            'effective_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:effective_date',
            'reference_no' => 'nullable|string|max:100',
            'title' => 'required|string|max:180',
            'details' => 'nullable|string|max:2000',
        ]);
        $data['employee_id'] = $employee->id;
        $data['recorded_by'] = $request->user()->id;
        $event = EmployeeLifecycleEvent::create($data);
        AuditLogService::created($event, 'Employee lifecycle event recorded');

        return redirect()->route('employees.show', $employee)->with('success', 'Service/lifecycle event added.');
    }
}
