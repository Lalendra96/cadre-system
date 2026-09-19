<?php

declare(strict_types=1);
namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\AuditLog;
use App\Services\WorkforceScopeService;
use Illuminate\Http\Request;

class Employee360Controller extends Controller
{
    public function show(Request $request, Employee $employee)
    {
        WorkforceScopeService::authorizeEmployee($request->user(),$employee);
        $employee->load(['position','unit','subjectCode','salaryScale','incrementRecords','gradeRecords.positionGrade','qualifications','examRecords','actingAppointments','transferRecords','leaveRecords','interdictions','serviceLetters','lifecycleEvents.recordedBy','dataQualityIssues','documents.uploader','servicePeriods.position','servicePeriods.positionGrade','servicePeriods.sourceDocument']);
        $timeline=collect();
        if($employee->date_of_appointment) $timeline->push(['date'=>$employee->date_of_appointment,'type'=>'appointment','title'=>'Joined service / appointment','details'=>$employee->position?->title]);
        foreach($employee->gradeRecords as $r) $timeline->push(['date'=>$r->effective_date,'type'=>'grade','title'=>'Grade change','details'=>$r->positionGrade?->name]);
        foreach($employee->servicePeriods->where('is_active', true) as $r) $timeline->push(['date'=>$r->start_date,'type'=>'service_period','title'=>($r->is_current?'Current posting':'Service / posting period'),'details'=>trim(($r->institution_name ?? '').' · '.$r->display_position.($r->display_grade ? ' · '.$r->display_grade : ''))]);
        foreach($employee->incrementRecords as $r) $timeline->push(['date'=>$r->increment_date,'type'=>'increment','title'=>'Increment '.$r->workflow_status,'details'=>$r->reference_no]);
        foreach($employee->transferRecords as $r) $timeline->push(['date'=>$r->effective_date,'type'=>'transfer','title'=>'Transfer '.strtoupper($r->direction),'details'=>trim(($r->from_location??'').' → '.($r->to_location??''))]);
        foreach($employee->actingAppointments as $r) $timeline->push(['date'=>$r->start_date,'type'=>'acting','title'=>'Acting appointment','details'=>$r->remarks]);
        foreach($employee->leaveRecords as $r) $timeline->push(['date'=>$r->start_date,'type'=>'leave','title'=>'Leave / LWOP','details'=>$r->notes]);
        foreach($employee->interdictions as $r) $timeline->push(['date'=>$r->interdiction_date ?? $r->created_at,'type'=>'interdiction','title'=>'Interdiction','details'=>$r->reason ?? $r->notes]);
        foreach($employee->lifecycleEvents as $r) $timeline->push(['date'=>$r->effective_date,'type'=>$r->event_type,'title'=>$r->title,'details'=>$r->details]);
        $timeline=$timeline->filter(fn($x)=>$x['date'])->sortByDesc('date')->values();
        $auditLogs = AuditLog::with('user')->where('auditable_type', Employee::class)->where('auditable_id',$employee->id)->latest('created_at')->limit(50)->get();
        return view('employees.show',compact('employee','timeline','auditLogs')); 
    }
}
