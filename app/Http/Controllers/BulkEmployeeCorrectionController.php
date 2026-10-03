<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\SubjectCode;
use App\Models\Unit;
use App\Services\AdministrativeDecisionService;
use Illuminate\Http\Request;

class BulkEmployeeCorrectionController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(
            $request->user()->isSuperAdmin() || $request->user()->isPlanningOfficer(),
            403
        );

        $employees = Employee::with(['position', 'unit', 'subjectCode'])
            ->active()
            ->orderBy('id')
            ->paginate(50);

        $units = Unit::active()
            ->orderBy('name')
            ->get(['id', 'name']);

        $subjectCodes = SubjectCode::active()
            ->orderBy('code')
            ->get(['id', 'code']);

        return view(
            'employees.bulk-correction',
            compact('employees', 'units', 'subjectCodes')
        );
    }

    public function update(Request $request)
    {
        abort_unless(
            $request->user()->isSuperAdmin() || $request->user()->isPlanningOfficer(),
            403
        );

        $data = $request->validate([
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'integer|exists:employees,id',
            'field' => 'required|in:unit_id,subject_code_id,employment_status',
            'unit_id' => 'nullable|integer|exists:units,id',
            'subject_code_id' => 'nullable|integer|exists:subject_codes,id',
            'employment_status' => 'nullable|in:active,on_leave,no_pay_leave,temporary_transfer,permanent_transfer,secondment,deputation,acting,interdicted,suspended,resigned,retired,deceased',
            'reason' => 'required|string|min:10|max:500',
        ]);

        $field = $data['field'];
        $value = $data[$field] ?? null;

        abort_if($value === null, 422, 'Select a replacement value.');

        $count = 0;
        $batchReference = 'Bulk correction '.now()->format('Ymd-His');

        foreach (Employee::whereIn('id', $data['employee_ids'])->get() as $employee) {
            AdministrativeDecisionService::request(
                type: 'employee_bulk_correction',
                employee: $employee,
                payload: [
                    'field' => $field,
                    'value' => $value,
                    'old_value' => $employee->{$field},
                    'reason' => $data['reason'],
                    'batch_reference' => $batchReference,
                ],
                requester: $request->user(),
                summary: 'Bulk correction proposal: '.$field.' for '.$employee->display_name,
                sourceReference: $batchReference,
            );
            $count++;
        }

        return redirect()->route('administrative-decisions.index', ['status' => 'pending'])->with(
            'success',
            "{$count} governed correction decisions created. No employee record changes until independent check, recommendation and approval."
        );
    }
}
