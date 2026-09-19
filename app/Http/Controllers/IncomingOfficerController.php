<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeGradeRecord;
use App\Models\EmployeeHrAllocation;
use App\Models\EmployeeServicePeriod;
use App\Models\Position;
use App\Models\PositionGrade;
use App\Models\SubjectCode;
use App\Models\TransferRecord;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class IncomingOfficerController extends Controller
{
    public function create(Request $request)
    {
        $user=$request->user();
        $codes=$user->isSuperAdmin()?SubjectCode::active()->orderBy('code')->get():SubjectCode::active()->whereIn('id',$user->effectiveHrSubjectCodeIds())->orderBy('code')->get();
        $positions=$user->isSuperAdmin()?Position::active()->orderBy('title')->get():Position::active()->whereIn('id',$user->effectiveHrPositionIds())->orderBy('title')->get();
        $units=Unit::active()->orderBy('name')->get();
        $grades=PositionGrade::active()->with('position')->ordered()->get();
        return view('incoming-officers.create',compact('codes','positions','units','grades'));
    }

    public function store(Request $request)
    {
        $user=$request->user();
        $allowedPositions=$user->isSuperAdmin()?Position::active()->pluck('id'):collect($user->effectiveHrPositionIds());
        $allowedCodes=$user->isSuperAdmin()?SubjectCode::active()->pluck('id'):collect($user->effectiveHrSubjectCodeIds());
        $data=$request->validate([
            'name'=>['required','string','max:150'],'salutation'=>['nullable','string','max:20'],'nic_number'=>['nullable','string','max:12','regex:/^(\d{9}[VvXx]|\d{12})$/','unique:employees,nic_number'],
            'pay_no'=>['nullable','string','max:50','unique:employees,pay_no'],'service_file_no'=>['nullable','string','max:60'],'gender'=>['required','in:M,F,O'],
            'subject_code_id'=>['required',Rule::in($allowedCodes->all())],'position_id'=>['required',Rule::in($allowedPositions->all())],'unit_id'=>['required','exists:units,id'],
            'current_service_name'=>['nullable','string','max:180'],'combined_service_name'=>['nullable','string','max:180'],'date_joined_public_service'=>['required','date'],
            'date_joined_combined_service'=>['nullable','date'],'date_reported_for_duty'=>['required','date'],'position_grade_id'=>['nullable','exists:position_grades,id'],'current_grade_start_date'=>['nullable','date'],
            'previous_institution'=>['required','string','max:200'],'previous_ministry_department'=>['nullable','string','max:200'],'previous_position_text'=>['nullable','string','max:180'],
            'previous_grade_text'=>['nullable','string','max:120'],'previous_start_date'=>['nullable','date'],'released_date'=>['nullable','date'],
            'transfer_order_no'=>['nullable','string','max:120'],'transfer_order_date'=>['nullable','date'],
        ]);

        $employee=DB::transaction(function() use($data,$user){
            $employee=Employee::create([
                'salutation'=>$data['salutation']??null,'name'=>$data['name'],'nic_number'=>$data['nic_number']??null,'pay_no'=>$data['pay_no']??null,'service_file_no'=>$data['service_file_no']??null,
                'gender'=>$data['gender'],'subject_code_id'=>$data['subject_code_id'],'position_id'=>$data['position_id'],'unit_id'=>$data['unit_id'],
                'current_service_name'=>$data['current_service_name']??null,'combined_service_name'=>$data['combined_service_name']??null,'date_joined_public_service'=>$data['date_joined_public_service'],
                'date_joined_combined_service'=>$data['date_joined_combined_service']??null,'date_reported_for_duty'=>$data['date_reported_for_duty'],'date_joined_institution'=>$data['date_reported_for_duty'],
                'date_current_grade'=>$data['current_grade_start_date']??null,'employment_status'=>'active','is_active'=>true,'created_by'=>$user->id,'updated_by'=>$user->id,
            ]);
            if(!empty($data['previous_start_date'])) EmployeeServicePeriod::create([
                'employee_id'=>$employee->id,'period_kind'=>'posting','sector'=>'central_government','service_name'=>$data['combined_service_name']??$data['current_service_name']??null,
                'institution_name'=>$data['previous_institution'],'ministry_department'=>$data['previous_ministry_department']??null,'position_text'=>$data['previous_position_text']??null,
                'grade_text'=>$data['previous_grade_text']??null,'start_date'=>$data['previous_start_date'],'end_date'=>$data['released_date']??$data['date_reported_for_duty'],
                'movement_type'=>'incoming_external_transfer','movement_reference'=>$data['transfer_order_no']??null,'movement_reference_date'=>$data['transfer_order_date']??null,
                'to_institution'=>'Teaching Hospital Peradeniya','verification_status'=>'document_pending','is_current'=>false,'is_active'=>true,'created_by'=>$user->id,'updated_by'=>$user->id,
            ]);
            EmployeeServicePeriod::create([
                'employee_id'=>$employee->id,'period_kind'=>'posting','sector'=>'central_government','service_name'=>$data['combined_service_name']??$data['current_service_name']??null,
                'institution_name'=>'Teaching Hospital Peradeniya','ministry_department'=>'Ministry of Health','position_id'=>$data['position_id'],'position_grade_id'=>$data['position_grade_id']??null,
                'start_date'=>$data['date_reported_for_duty'],'movement_type'=>'incoming_external_transfer','movement_reference'=>$data['transfer_order_no']??null,'movement_reference_date'=>$data['transfer_order_date']??null,
                'from_institution'=>$data['previous_institution'],'to_institution'=>'Teaching Hospital Peradeniya','verification_status'=>'document_pending','is_current'=>true,'is_active'=>true,'created_by'=>$user->id,'updated_by'=>$user->id,
            ]);
            if(!empty($data['position_grade_id']) && !empty($data['current_grade_start_date'])) EmployeeGradeRecord::create([
                'employee_id'=>$employee->id,'position_grade_id'=>$data['position_grade_id'],'effective_date'=>$data['current_grade_start_date'],'reference_no'=>$data['transfer_order_no']??null,'notes'=>'Grade service start date carried forward from previous institution.','recorded_by'=>$user->id,'is_active'=>true,
            ]);
            TransferRecord::create(['employee_id'=>$employee->id,'employee_name'=>$employee->name,'designation'=>$employee->position?->title,'direction'=>'in','transfer_type'=>'incoming_external_transfer',
                'from_location'=>$data['previous_institution'],'to_location'=>'Teaching Hospital Peradeniya','effective_date'=>$data['date_reported_for_duty'],'transfer_board_ref_no'=>$data['transfer_order_no']??null,'transfer_board_decision_date'=>$data['transfer_order_date']??null,'recorded_by'=>$user->id,'is_active'=>true]);
            if($user->isSubjectOfficer()&&!$user->isSuperAdmin()) EmployeeHrAllocation::create(['employee_id'=>$employee->id,'user_id'=>$user->id,'position_id'=>$employee->position_id,'starts_on'=>$data['date_reported_for_duty'],'assigned_by'=>$user->id,'reason'=>'Incoming officer registered by allocated Subject Officer.']);
            return $employee;
        });
        return redirect()->route('employees.show',$employee)->with('success','Incoming officer registered. Previous service, current hospital posting and grade-service continuity were preserved separately.');
    }
}
