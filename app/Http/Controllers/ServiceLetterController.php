<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\ServiceLetter;
use App\Models\ServiceLetterLetterhead;
use App\Models\ServiceLetterTemplate;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use App\Services\ServiceLetterService;
use App\Services\WorkforceScopeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ServiceLetterController extends Controller
{
    public function index(Request $request)
    {
        $user=$request->user();
        $query=ServiceLetter::active()->with(['employee','draftedBy','approvedBy','letterhead']);
        if($user->isAdministrativeOfficer()) $status=$request->query('status','pending_approval');
        elseif($user->isSuperAdmin()||$user->isPlanningOfficer()||$user->isAdminGroup()) $status=$request->query('status');
        else { $query->whereIn('employee_id',WorkforceScopeService::allocatedEmployeeIds($user)); $status=$request->query('status'); }
        if($status) $query->where('status',$status);
        $letters=$query->orderByDesc('created_at')->paginate(20)->withQueryString();
        return view('service-letters.index',compact('letters','status'));
    }

    public function create(Request $request)
    {
        $this->authorizeDrafter($request);
        $employees=$this->assignableEmployees($request->user());
        $templates=ServiceLetterTemplate::active()->orderBy('language')->orderBy('name')->get();
        $letterheads=ServiceLetterLetterhead::active()->orderByDesc('is_default')->orderBy('name')->get();
        $selectedEmployeeId=(int)$request->query('employee_id',0);
        return view('service-letters.form',compact('employees','templates','letterheads','selectedEmployeeId'));
    }


    public function edit(Request $request, ServiceLetter $serviceLetter)
    {
        $this->authorizeDrafter($request);
        $this->authorizeOwnEmployee($request,$serviceLetter->employee);
        abort_unless($serviceLetter->isEditable(),403,'Only draft or returned letters can be edited.');
        $employees=$this->assignableEmployees($request->user());
        $templates=ServiceLetterTemplate::active()->orderBy('language')->orderBy('name')->get();
        $letterheads=ServiceLetterLetterhead::active()->orderByDesc('is_default')->orderBy('name')->get();
        $selectedEmployeeId=$serviceLetter->employee_id;
        return view('service-letters.form',compact('employees','templates','letterheads','selectedEmployeeId','serviceLetter'));
    }

    public function update(Request $request, ServiceLetter $serviceLetter): RedirectResponse
    {
        $this->authorizeDrafter($request);
        $this->authorizeOwnEmployee($request,$serviceLetter->employee);
        abort_unless($serviceLetter->isEditable(),403,'Only draft or returned letters can be edited.');
        $data=$request->validate([
            'employee_id'=>['required','integer','exists:employees,id'],'template_id'=>['nullable','integer','exists:service_letter_templates,id'],
            'letterhead_id'=>['nullable','integer','exists:service_letter_letterheads,id'],'language'=>['required','in:en,si,ta'],'purpose'=>['nullable','string','max:80'],
            'recipient_name'=>['nullable','string','max:180'],'recipient_address'=>['nullable','string','max:300'],'reference_no'=>['nullable','string','max:100'],
            'copy_type'=>['required','in:original,copy,certified_copy,draft,confidential'],'subject'=>['required','string','max:200'],'rendered_body'=>['required','string','max:10000'],
        ]);
        $employee=Employee::findOrFail($data['employee_id']); $this->authorizeOwnEmployee($request,$employee);
        $old=$serviceLetter->getOriginal();
        $serviceLetter->update($data+['status'=>ServiceLetter::STATUS_DRAFT,'rejection_reason'=>null]);
        AuditLogService::updated($serviceLetter,$old,'Service letter draft corrected/updated.');
        return redirect()->route('service-letters.show',$serviceLetter)->with('success','Draft updated. Review and submit when ready.');
    }

    public function show(Request $request,ServiceLetter $serviceLetter)
    {
        $user=$request->user();
        $serviceLetter->load(['employee.position','employee.subjectCode','template','letterhead','draftedBy','approvedBy','eSignature']);
        $canView=$user->isSuperAdmin()||$user->isPlanningOfficer()||$user->isAdminGroup()||($user->isSubjectOfficer()&&WorkforceScopeService::isEmployeeAllocatedTo($user,$serviceLetter->employee));
        abort_unless($canView,403,'You are not authorised to view this service letter.');
        return view('service-letters.show',compact('serviceLetter'));
    }

    public function print(Request $request,ServiceLetter $serviceLetter)
    {
        $this->show($request,$serviceLetter);
        $serviceLetter->loadMissing(['employee.position','letterhead','approvedBy','eSignature']);
        return view('service-letters.print',compact('serviceLetter'));
    }

    public function preview(Request $request)
    {
        $this->authorizeDrafter($request);
        $data=$request->validate(['employee_id'=>['required','integer','exists:employees,id'],'template_id'=>['required','integer','exists:service_letter_templates,id']]);
        $employee=Employee::with(['position','subjectCode','unit','salaryScale','gradeRecords.positionGrade'])->findOrFail($data['employee_id']);
        $this->authorizeOwnEmployee($request,$employee);
        $template=ServiceLetterTemplate::findOrFail($data['template_id']);
        return response()->json(['rendered_body'=>ServiceLetterService::render($template,$employee),'language'=>$template->language]);
    }

    public function store(Request $request):RedirectResponse
    {
        $this->authorizeDrafter($request);
        $data=$request->validate([
            'employee_id'=>['required','integer','exists:employees,id'],'template_id'=>['nullable','integer','exists:service_letter_templates,id'],
            'letterhead_id'=>['nullable','integer','exists:service_letter_letterheads,id'],'language'=>['required','in:en,si,ta'],'purpose'=>['nullable','string','max:80'],
            'recipient_name'=>['nullable','string','max:180'],'recipient_address'=>['nullable','string','max:300'],'reference_no'=>['nullable','string','max:100'],
            'copy_type'=>['required','in:original,copy,certified_copy,draft,confidential'],'subject'=>['required','string','max:200'],'rendered_body'=>['required','string','max:10000'],
        ]);
        $employee=Employee::findOrFail($data['employee_id']); $this->authorizeOwnEmployee($request,$employee);
        $letter=ServiceLetter::create($data+['status'=>ServiceLetter::STATUS_DRAFT,'drafted_by'=>$request->user()->id,'is_active'=>true]);
        AuditLogService::created($letter,"Drafted service letter \"{$letter->subject}\" for {$employee->display_name}");
        return redirect()->route('service-letters.show',$letter)->with('success','Draft saved. Review the letterhead and content, then submit for approval.');
    }

    public function submit(Request $request,ServiceLetter $serviceLetter):RedirectResponse
    {
        $this->authorizeOwnEmployee($request,$serviceLetter->employee); abort_unless($serviceLetter->isEditable(),403,'This letter is no longer editable.');
        abort_if(trim((string)$serviceLetter->rendered_body)==='',422,'The letter body is empty.');
        $old=$serviceLetter->getOriginal(); $serviceLetter->update(['status'=>ServiceLetter::STATUS_PENDING_APPROVAL,'rejection_reason'=>null]);
        AuditLogService::updated($serviceLetter,$old,'Service letter submitted for AO approval.');
        $aoUsers=\App\Models\User::active()->whereHas('category',fn($q)=>$q->where('name','Administrative Officer / Hospital Secretary'))->get();
        NotificationService::sendToMany($aoUsers,type:'service_letter_pending',title:'Service letter awaiting your approval',body:"\"{$serviceLetter->subject}\" for {$serviceLetter->employee->display_name} needs review.",link:route('service-letters.index',['status'=>'pending_approval']));
        return redirect()->route('service-letters.show',$serviceLetter)->with('success','Submitted for AO approval.');
    }

    public function approve(Request $request,ServiceLetter $serviceLetter):RedirectResponse
    {
        $this->authorizeApprover($request); abort_unless($serviceLetter->status===ServiceLetter::STATUS_PENDING_APPROVAL,403,'This letter is not awaiting approval.');
        $signature=$request->user()->eSignature; if(!$signature) return redirect()->route('e-signatures.edit')->with('error','Please register your e-signature before approving service letters.');
        $request->validate(['confirm_signature'=>['accepted']]);
        $old=$serviceLetter->getOriginal(); $serviceLetter->update(['status'=>ServiceLetter::STATUS_APPROVED,'approved_by'=>$request->user()->id,'approved_at'=>now(),'e_signature_id'=>$signature->id]);
        AuditLogService::updated($serviceLetter,$old,"Service letter approved and e-signed by {$request->user()->name}");
        NotificationService::send($serviceLetter->draftedBy,type:'service_letter_approved',title:'Service letter approved',body:"\"{$serviceLetter->subject}\" for {$serviceLetter->employee->display_name} has been approved and signed.",link:route('service-letters.show',$serviceLetter));
        return back()->with('success','Service letter approved and e-signed. It is ready for official printing.');
    }

    public function reject(Request $request,ServiceLetter $serviceLetter):RedirectResponse
    {
        $this->authorizeApprover($request); abort_unless($serviceLetter->status===ServiceLetter::STATUS_PENDING_APPROVAL,403,'This letter is not awaiting approval.');
        $request->validate(['rejection_reason'=>['required','string','min:10','max:500']]);
        $old=$serviceLetter->getOriginal(); $serviceLetter->update(['status'=>ServiceLetter::STATUS_REJECTED,'rejection_reason'=>$request->input('rejection_reason')]);
        AuditLogService::updated($serviceLetter,$old,'Service letter rejected by AO.');
        NotificationService::send($serviceLetter->draftedBy,type:'service_letter_rejected',title:'Service letter returned for correction',body:"\"{$serviceLetter->subject}\" was returned: ".\Illuminate\Support\Str::limit($request->input('rejection_reason'),80),link:route('service-letters.show',$serviceLetter));
        return back()->with('success','Returned to the drafting officer for correction.');
    }

    private function authorizeDrafter(Request $request): void
    {
        $u=$request->user();
        abort_unless($u->isSubjectOfficer() || $u->isPlanningOfficer() || $u->isSuperAdmin(),403,'This role may review service letters but cannot draft employee letters.');
    }

    private function assignableEmployees(\App\Models\User $user)
    {
        if($user->isSuperAdmin()||$user->isPlanningOfficer()) return Employee::active()->with(['subjectCode','position'])->orderBy('name')->get();
        return WorkforceScopeService::employeeQuery($user)->active()->with(['subjectCode','position'])->orderBy('name')->get();
    }
    private function authorizeOwnEmployee(Request $request,Employee $employee):void
    {
        $user=$request->user(); if($user->isSuperAdmin()||$user->isPlanningOfficer()) return;
        if(!$user->isSubjectOfficer()||!WorkforceScopeService::isEmployeeAllocatedTo($user,$employee)) abort(403,'You may only issue service letters for Employee Profiles allocated to your account.');
    }
    private function authorizeApprover(Request $request):void { abort_unless($request->user()->isAdministrativeOfficer()||$request->user()->isSuperAdmin(),403,'Only the Administrative Officer may approve or reject service letters.'); }
}
