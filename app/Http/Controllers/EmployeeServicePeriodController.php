<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeeServicePeriod;
use App\Models\Position;
use App\Models\PositionGrade;
use App\Services\AuditLogService;
use App\Services\WorkforceScopeService;
use Illuminate\Http\Request;

class EmployeeServicePeriodController extends Controller
{
    public function overview(Request $request)
    {
        $user = $request->user();
        $employees = WorkforceScopeService::employeeQuery($user)->active()->with(['position','servicePeriods'=>fn($q)=>$q->active()->orderByDesc('start_date')])->orderBy('name')->get();
        $incomplete = $employees->filter(fn($e) => !$e->date_joined_public_service || $e->servicePeriods->isEmpty());
        return view('employee-service-periods.overview', compact('employees','incomplete'));
    }

    public function index(Request $request, Employee $employee)
    {
        WorkforceScopeService::authorizeEmployee($request->user(), $employee);
        $periods = $employee->servicePeriods()->active()->with(['position','positionGrade','sourceDocument','verifiedBy'])->orderByDesc('start_date')->get();
        return view('employee-service-periods.index', compact('employee','periods'));
    }

    public function create(Request $request, Employee $employee)
    {
        WorkforceScopeService::authorizeEmployee($request->user(), $employee);
        return view('employee-service-periods.form', $this->formData($employee, new EmployeeServicePeriod()));
    }

    public function store(Request $request, Employee $employee)
    {
        WorkforceScopeService::authorizeEmployee($request->user(), $employee);
        $data = $this->validated($request);
        $data['employee_id']=$employee->id; $data['created_by']=$request->user()->id; $data['updated_by']=$request->user()->id; $data['is_active']=true;
        if ($request->boolean('is_current')) {
            $employee->servicePeriods()->where('is_current',true)->update(['is_current'=>false,'updated_at'=>now()]);
            $data['is_current']=true; $data['end_date']=null;
        }
        if ($data['verification_status']==='verified_official_record') { $data['verified_by']=$request->user()->id; $data['verified_at']=now(); }
        $period=EmployeeServicePeriod::create($data);
        AuditLogService::created($period, 'Recorded employee service period: '.$period->institution_name);
        return redirect()->route('employee-service-periods.index',$employee)->with('success','Service period recorded. Previous service is preserved as part of the career history.');
    }

    public function edit(Request $request, Employee $employee, EmployeeServicePeriod $period)
    {
        WorkforceScopeService::authorizeEmployee($request->user(), $employee); abort_unless($period->employee_id===$employee->id,404);
        return view('employee-service-periods.form', $this->formData($employee,$period));
    }

    public function update(Request $request, Employee $employee, EmployeeServicePeriod $period)
    {
        WorkforceScopeService::authorizeEmployee($request->user(), $employee); abort_unless($period->employee_id===$employee->id,404);
        $data=$this->validated($request); $data['updated_by']=$request->user()->id;
        if ($request->boolean('is_current')) { $employee->servicePeriods()->where('id','!=',$period->id)->where('is_current',true)->update(['is_current'=>false,'updated_at'=>now()]); $data['is_current']=true; $data['end_date']=null; }
        if ($data['verification_status']==='verified_official_record' && !$period->verified_at) { $data['verified_by']=$request->user()->id; $data['verified_at']=now(); }
        $period->update($data);
        return redirect()->route('employee-service-periods.index',$employee)->with('success','Service period updated.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'period_kind'=>['required','in:career,posting,attachment,secondment,other'], 'sector'=>['nullable','string','max:60'],
            'service_name'=>['nullable','string','max:180'], 'service_category'=>['nullable','string','max:80'], 'institution_name'=>['required','string','max:200'],
            'ministry_department'=>['nullable','string','max:200'], 'province'=>['nullable','string','max:100'], 'position_id'=>['nullable','integer','exists:positions,id'],
            'position_text'=>['nullable','string','max:180'], 'position_grade_id'=>['nullable','integer','exists:position_grades,id'], 'grade_text'=>['nullable','string','max:120'],
            'appointment_type'=>['nullable','string','max:80'], 'start_date'=>['required','date'], 'end_date'=>['nullable','date','after_or_equal:start_date'],
            'movement_type'=>['nullable','string','max:80'], 'movement_reference'=>['nullable','string','max:120'], 'movement_reference_date'=>['nullable','date'],
            'from_institution'=>['nullable','string','max:200'], 'to_institution'=>['nullable','string','max:200'], 'salary_scale_text'=>['nullable','string','max:120'],
            'confirmed_status'=>['nullable','boolean'], 'counts_for_service'=>['nullable','boolean'], 'counts_for_grade_service'=>['nullable','boolean'], 'counts_for_pension'=>['nullable','boolean'],
            'source_document_id'=>['nullable','integer','exists:employee_documents,id'], 'verification_status'=>['required','in:verified_official_record,document_pending,imported,employee_declared'],
            'remarks'=>['nullable','string','max:3000'], 'is_current'=>['nullable','boolean'],
        ]);
    }

    private function formData(Employee $employee, EmployeeServicePeriod $period): array
    {
        return ['employee'=>$employee,'period'=>$period,'positions'=>Position::active()->orderBy('title')->get(['id','title']),
            'grades'=>PositionGrade::active()->with('position:id,title')->ordered()->get(),
            'documents'=>EmployeeDocument::where('employee_id',$employee->id)->orderByDesc('document_date')->get(['id','title','document_date'])];
    }
}
