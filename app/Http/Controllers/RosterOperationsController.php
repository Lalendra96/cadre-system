<?php

namespace App\Http\Controllers;

use App\Models\Competency;
use App\Models\Employee;
use App\Models\Position;
use App\Models\RosterAmendmentRequest;
use App\Models\RosterAssignment;
use App\Models\RosterAvailability;
use App\Models\RosterOpenShift;
use App\Models\RosterOpenShiftClaim;
use App\Models\RosterPlan;
use App\Models\RosterShiftSwapRequest;
use App\Models\RosterStaffingRule;
use App\Models\Unit;
use App\Services\RosterAuditService;
use App\Services\RosterComplianceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RosterOperationsController extends Controller
{
    public function operations()
    {
        $openShifts = RosterOpenShift::with(['plan','unit','claims.employee'])->where('status','open')->orderBy('duty_date')->limit(50)->get();
        $openShiftClaims = RosterOpenShiftClaim::with(['shift.unit','employee'])->where('status','pending_manager')->latest()->limit(50)->get();
        $swaps = RosterShiftSwapRequest::with(['assignment.employee','requester','target'])->latest()->limit(50)->get();
        $availability = RosterAvailability::with('employee')->whereDate('available_date','>=',today())->orderBy('available_date')->limit(80)->get();
        $employees = Employee::query()->where('is_active', true)->get(['id','name','salutation','unit_id','position_id'])->sortBy(fn($e) => mb_strtolower($e->display_name, 'UTF-8'))->values();
        $plans = RosterPlan::query()->whereIn('status',['draft','approved','active'])->orderByDesc('start_date')->limit(50)->get();
        $units = Unit::cachedActive();
        $positions = Position::cachedActive();
        $competencies = Competency::query()->where('is_active', true)->orderBy('name')->get();
        $staffingRules = RosterStaffingRule::with(['position','competency'])->where('is_active',true)->latest()->limit(50)->get();
        $fairness = RosterAssignment::query()->with('employee')->whereDate('duty_date','>=',now()->subDays(30)->toDateString())->whereNotIn('status',['cancelled'])->get()->groupBy('employee_id')->map(function($rows){$employee=$rows->first()->employee;$night=$rows->filter(fn($a)=>$a->start_time>='18:00:00'||$a->start_time<'06:00:00')->count();$weekend=$rows->filter(fn($a)=>in_array($a->duty_date->dayOfWeek,[0,6],true))->count();$cross=$rows->where('coverage_type','cross_unit')->count();return ['employee'=>$employee,'duties'=>$rows->count(),'night'=>$night,'weekend'=>$weekend,'cross'=>$cross];})->sortByDesc('duties')->values();
        return view('roster.operations.index', compact('openShifts','openShiftClaims','swaps','availability','employees','plans','units','positions','competencies','staffingRules','fairness'));
    }

    public function saveAvailability(Request $request)
    {
        $data = $request->validate([
            'employee_id'=>'required|exists:employees,id','available_date'=>'required|date',
            'available_from'=>'nullable','available_to'=>'nullable','availability_type'=>'required|in:available,preferred,unavailable',
            'preferred_shift'=>'nullable|in:morning,evening,night,on_call','note'=>'nullable|string|max:500'
        ]);
        $user = auth()->user();
        if ($user->employee_id && !$user->hasAnyRole(['super_admin','admin_group','planning_officer','unit_manager'])) {
            $data['employee_id'] = $user->employee_id;
        }
        $data['created_by'] = auth()->id();
        $availability=RosterAvailability::updateOrCreate(['employee_id'=>$data['employee_id'],'available_date'=>$data['available_date']],$data);
        RosterAuditService::log('updated',RosterAvailability::class,$availability->id,'Roster availability/preference updated',null,$availability->toArray());
        return back()->with('success','Availability updated.');
    }

    public function createOpenShift(Request $request)
    {
        $data=$request->validate(['roster_plan_id'=>'required|exists:roster_plans,id','duty_date'=>'required|date','start_time'=>'required','end_time'=>'required','duty_unit_id'=>'required|exists:units,id','position_id'=>'nullable|exists:positions,id','duty_role'=>'nullable|string|max:150','required_staff'=>'required|integer|min:1|max:50','details'=>'nullable|string|max:1000']);
        $plan = RosterPlan::findOrFail($data['roster_plan_id']);
        abort_unless($data['duty_date'] >= $plan->start_date->format('Y-m-d') && $data['duty_date'] <= $plan->end_date->format('Y-m-d'), 422, 'Open shift date must fall inside the roster period.');
        $data['created_by']=auth()->id();
        $shift=RosterOpenShift::create($data);
        RosterAuditService::log('created',RosterOpenShift::class,$shift->id,'Open roster shift created');
        return back()->with('success','Open shift created.');
    }

    public function claimOpenShift(Request $request, RosterOpenShift $shift, RosterComplianceService $compliance)
    {
        $request->validate(['employee_id'=>'required|exists:employees,id']);
        abort_unless($shift->status==='open',422,'Shift is no longer open.');
        $employee=Employee::findOrFail($request->employee_id);
        $check=$compliance->evaluateEmployee($employee->id,$shift->duty_date->format('Y-m-d'),$shift->start_time,$shift->end_time,null,(int)$shift->duty_unit_id,$shift->position_id ? (int)$shift->position_id : (int)$employee->position_id);
        abort_if(!$check['allowed'],422,implode(' ', $check['hard_stops']));
        $plan = $shift->plan()->firstOrFail();

        if (in_array($plan->status, ['approved','active'], true)) {
            $amendment = RosterAmendmentRequest::create([
                'roster_plan_id'=>$plan->id,'action'=>'add','source_type'=>'manager_open_shift','source_id'=>$shift->id,
                'is_emergency'=>false,'proposed_values'=>[
                    'employee_id'=>$employee->id,'duty_date'=>$shift->duty_date->format('Y-m-d'),'start_time'=>$shift->start_time,'end_time'=>$shift->end_time,
                    'duty_unit_id'=>$shift->duty_unit_id,'duty_role'=>$shift->duty_role,'details'=>$shift->details,
                ],'reason'=>'Manager selected employee for open shift on an already published roster.','status'=>'pending','requested_by'=>auth()->id(),'requested_at'=>now(),
            ]);
            RosterAuditService::log('created',RosterAmendmentRequest::class,$amendment->id,'Open shift assignment routed through published-roster amendment workflow');
            return back()->with('success','The roster is already approved/active. An amendment request was created instead of changing the published roster directly.');
        }

        DB::transaction(function () use ($shift,$employee) {
            $locked = RosterOpenShift::query()->lockForUpdate()->findOrFail($shift->id);
            abort_unless($locked->status==='open',422,'Shift is no longer open.');
            RosterAssignment::create(['roster_plan_id'=>$locked->roster_plan_id,'employee_id'=>$employee->id,'duty_date'=>$locked->duty_date,'start_time'=>$locked->start_time,'end_time'=>$locked->end_time,'home_unit_id'=>$employee->unit_id,'duty_unit_id'=>$locked->duty_unit_id,'position_id'=>$locked->position_id ?: $employee->position_id,'duty_role'=>$locked->duty_role,'coverage_type'=>(int)$employee->unit_id===(int)$locked->duty_unit_id?'home_unit':'cross_unit','details'=>$locked->details,'status'=>'planned','assigned_by'=>auth()->id()]);
            $locked->increment('filled_staff'); $locked->refresh();
            if ($locked->filled_staff >= $locked->required_staff) $locked->update(['status'=>'filled']);
        });
        return back()->with('success','Open shift filled after roster compliance validation.');
    }

    public function requestSwap(Request $request, RosterAssignment $assignment)
    {
        $data=$request->validate(['target_employee_id'=>'nullable|exists:employees,id','replacement_assignment_id'=>'nullable|exists:roster_assignments,id','swap_type'=>'required|in:swap,give_away','reason'=>'required|string|max:1000']);
        $user=auth()->user();
        abort_unless(!$user->employee_id || (int)$user->employee_id===(int)$assignment->employee_id || $user->hasAnyRole(['super_admin','admin_group','planning_officer','unit_manager']),403);
        abort_if(!empty($data['target_employee_id']) && (int)$data['target_employee_id']===(int)$assignment->employee_id,422,'Target employee must be different from the currently assigned employee.');
        $data['assignment_id']=$assignment->id; $data['requested_by_employee_id']=$assignment->employee_id;
        $data['status']=$data['target_employee_id']?'pending_peer':'pending_manager';
        $swap=RosterShiftSwapRequest::create($data);
        RosterAuditService::log('created',RosterShiftSwapRequest::class,$swap->id,'Roster shift change request submitted',null,$swap->toArray());
        return back()->with('success','Shift change request submitted.');
    }

    public function approveSwap(Request $request, RosterShiftSwapRequest $swap, RosterComplianceService $compliance)
    {
        $data=$request->validate(['action'=>'required|in:approve,reject','target_employee_id'=>'nullable|exists:employees,id','review_note'=>'nullable|string|max:1000']);
        if ($data['action']==='reject') {
            $old=$swap->toArray();
            $swap->update(['status'=>'rejected','reviewed_by'=>auth()->id(),'reviewed_at'=>now(),'review_note'=>$data['review_note']??null]);
            RosterAuditService::log('updated',RosterShiftSwapRequest::class,$swap->id,'Roster swap rejected by manager',$old,$swap->fresh()->toArray());
            return back()->with('success','Swap rejected.');
        }

        if (!empty($data['target_employee_id']) && (int)$swap->target_employee_id !== (int)$data['target_employee_id']) {
            abort_if((int)$swap->requested_by_employee_id === (int)$data['target_employee_id'],422,'Replacement employee must be different from the current employee.');
            $swap->update(['target_employee_id'=>$data['target_employee_id'],'status'=>'pending_peer','peer_accepted_by'=>null,'peer_accepted_at'=>null,'peer_responded_at'=>null,'peer_response_note'=>null]);
            return back()->with('success','Replacement employee proposed. The request is now waiting for that employee to accept before manager approval can continue.');
        }

        abort_unless($swap->target_employee_id,422,'A replacement employee must be selected before approval.');
        abort_unless($swap->status === 'pending_manager', 422, 'The target employee must accept the swap before manager approval.');

        $assignment=$swap->assignment()->with('plan')->firstOrFail();
        $target=Employee::findOrFail($swap->target_employee_id);
        $check=$compliance->evaluateEmployee($target->id,$assignment->duty_date->format('Y-m-d'),$assignment->start_time,$assignment->end_time,$assignment->id,(int)$assignment->duty_unit_id,$assignment->position_id ? (int)$assignment->position_id : (int)$target->position_id);
        abort_if(!$check['allowed'],422,implode(' ', $check['hard_stops']));

        $replacementAssignment = null;
        if ($swap->swap_type === 'swap') {
            abort_unless($swap->replacement_assignment_id, 422, 'Two-way swap is missing the replacement duty.');
            $replacementAssignment = RosterAssignment::with('plan')->findOrFail($swap->replacement_assignment_id);
            abort_unless((int)$replacementAssignment->roster_plan_id === (int)$assignment->roster_plan_id, 422, 'Two-way swap duties must belong to the same roster plan.');
            abort_unless((int)$replacementAssignment->employee_id === (int)$target->id, 422, 'The selected swap duty no longer belongs to the accepting employee.');
            $check=$compliance->evaluateEmployee($target->id,$assignment->duty_date->format('Y-m-d'),$assignment->start_time,$assignment->end_time,[$assignment->id,$replacementAssignment->id],(int)$assignment->duty_unit_id,$assignment->position_id ? (int)$assignment->position_id : (int)$target->position_id);
            abort_if(!$check['allowed'],422,implode(' ', $check['hard_stops']));
            $second=$compliance->evaluateEmployee((int)$swap->requested_by_employee_id,$replacementAssignment->duty_date->format('Y-m-d'),$replacementAssignment->start_time,$replacementAssignment->end_time,[$assignment->id,$replacementAssignment->id],(int)$replacementAssignment->duty_unit_id,$replacementAssignment->position_id ? (int)$replacementAssignment->position_id : null);
            abort_if(!$second['allowed'],422,'Original employee cannot safely take the other duty: '.implode(' ', $second['hard_stops']));
        }

        if (in_array($assignment->plan->status, ['approved','active'], true)) {
            $amendment=RosterAmendmentRequest::create([
                'roster_plan_id'=>$assignment->roster_plan_id,'roster_assignment_id'=>$assignment->id,
                'action'=>$swap->swap_type === 'swap' ? 'swap' : 'replace','source_type'=>'shift_swap','source_id'=>$swap->id,
                'is_emergency'=>false,
                'proposed_values'=>$swap->swap_type === 'swap'
                    ? ['employee_id'=>$target->id,'replacement_assignment_id'=>$replacementAssignment?->id]
                    : ['employee_id'=>$target->id],
                'reason'=>'Peer-accepted shift change: '.$swap->reason,'status'=>'pending','requested_by'=>auth()->id(),'requested_at'=>now(),
            ]);
            $swap->update(['status'=>'pending_amendment','reviewed_by'=>auth()->id(),'reviewed_at'=>now(),'review_note'=>$data['review_note']??null]);
            RosterAuditService::log('created',RosterAmendmentRequest::class,$amendment->id,'Peer-accepted swap routed through published-roster amendment workflow');
            return back()->with('success','Peer acceptance and manager review are complete. Because the roster is already published, the change is awaiting governed amendment approval.');
        }

        DB::transaction(function () use ($swap,$assignment,$target,$replacementAssignment,$data) {
            if ($swap->swap_type === 'swap' && $replacementAssignment) {
                $originalEmployee = Employee::findOrFail($assignment->employee_id);
                $assignment->update([
                    'employee_id'=>$target->id,'home_unit_id'=>$target->unit_id,
                    'position_id'=>$target->position_id ?: $assignment->position_id,
                    'coverage_type'=>(int)$target->unit_id===(int)$assignment->duty_unit_id?'home_unit':'cross_unit','acknowledged_at'=>null,
                ]);
                $replacementAssignment->update([
                    'employee_id'=>$originalEmployee->id,'home_unit_id'=>$originalEmployee->unit_id,
                    'position_id'=>$originalEmployee->position_id ?: $replacementAssignment->position_id,
                    'coverage_type'=>(int)$originalEmployee->unit_id===(int)$replacementAssignment->duty_unit_id?'home_unit':'cross_unit','acknowledged_at'=>null,
                ]);
            } else {
                $assignment->update(['employee_id'=>$target->id,'home_unit_id'=>$target->unit_id,'position_id'=>$target->position_id ?: $assignment->position_id,'coverage_type'=>(int)$target->unit_id===(int)$assignment->duty_unit_id?'home_unit':'cross_unit','acknowledged_at'=>null]);
            }
            $swap->update(['status'=>'approved','reviewed_by'=>auth()->id(),'reviewed_at'=>now(),'review_note'=>$data['review_note']??null]);
        });
        return back()->with('success', $swap->swap_type === 'swap' ? 'Two-way swap approved and draft roster updated.' : 'Shift change approved and draft roster updated.');
    }

    public function reviewOpenShiftClaim(Request $request, RosterOpenShiftClaim $claim, RosterComplianceService $compliance)
    {
        $data = $request->validate(['action'=>'required|in:approve,reject','review_note'=>'nullable|string|max:1000']);
        abort_unless($claim->status === 'pending_manager', 422, 'This claim has already been reviewed.');
        if ($data['action'] === 'reject') {
            $claim->update(['status'=>'rejected','reviewed_by'=>auth()->id(),'reviewed_at'=>now(),'review_note'=>$data['review_note'] ?? null]);
            return back()->with('success','Open-shift claim rejected.');
        }
        $shift = $claim->shift()->with('plan')->firstOrFail();
        abort_unless($shift->status === 'open', 422, 'The shift is no longer open.');
        $employee = Employee::findOrFail($claim->employee_id);
        $check = $compliance->evaluateEmployee($employee->id,$shift->duty_date->format('Y-m-d'),$shift->start_time,$shift->end_time,null,(int)$shift->duty_unit_id,$shift->position_id ? (int)$shift->position_id : (int)$employee->position_id);
        abort_if(!$check['allowed'],422,implode(' ', $check['hard_stops']));

        if (in_array($shift->plan->status, ['approved','active'], true)) {
            $amendment=RosterAmendmentRequest::create([
                'roster_plan_id'=>$shift->roster_plan_id,'action'=>'add','source_type'=>'open_shift_claim','source_id'=>$claim->id,
                'is_emergency'=>false,'proposed_values'=>[
                    'employee_id'=>$employee->id,'duty_date'=>$shift->duty_date->format('Y-m-d'),'start_time'=>$shift->start_time,'end_time'=>$shift->end_time,
                    'duty_unit_id'=>$shift->duty_unit_id,'duty_role'=>$shift->duty_role,'details'=>$shift->details,
                ],'reason'=>'Employee open-shift claim approved by manager for an already published roster.','status'=>'pending','requested_by'=>auth()->id(),'requested_at'=>now(),
            ]);
            $claim->update(['status'=>'pending_amendment','reviewed_by'=>auth()->id(),'reviewed_at'=>now(),'review_note'=>$data['review_note'] ?? null,'compliance_snapshot'=>$check]);
            RosterAuditService::log('created',RosterAmendmentRequest::class,$amendment->id,'Open shift claim routed through published-roster amendment workflow');
            return back()->with('success','Claim passed manager review. Because the roster is already published, it is awaiting governed amendment approval.');
        }

        DB::transaction(function () use ($claim,$shift,$employee,$data,$check) {
            $locked = RosterOpenShift::query()->lockForUpdate()->findOrFail($shift->id);
            abort_unless($locked->status==='open',422,'The shift is no longer open.');
            RosterAssignment::create(['roster_plan_id'=>$locked->roster_plan_id,'employee_id'=>$employee->id,'duty_date'=>$locked->duty_date,'start_time'=>$locked->start_time,'end_time'=>$locked->end_time,'home_unit_id'=>$employee->unit_id,'duty_unit_id'=>$locked->duty_unit_id,'position_id'=>$locked->position_id ?: $employee->position_id,'duty_role'=>$locked->duty_role,'coverage_type'=>(int)$employee->unit_id===(int)$locked->duty_unit_id?'home_unit':'cross_unit','details'=>$locked->details,'status'=>'planned','assigned_by'=>auth()->id()]);
            $claim->update(['status'=>'approved','reviewed_by'=>auth()->id(),'reviewed_at'=>now(),'review_note'=>$data['review_note'] ?? null,'compliance_snapshot'=>$check]);
            $locked->increment('filled_staff'); $locked->refresh();
            if ($locked->filled_staff >= $locked->required_staff) {
                $locked->update(['status'=>'filled']);
                RosterOpenShiftClaim::where('roster_open_shift_id',$locked->id)->where('status','pending_manager')->update(['status'=>'closed_unfilled','review_note'=>'Shift filled by another approved claim.']);
            }
        });
        return back()->with('success','Open-shift claim approved and the draft roster assignment was created.');
    }

    public function saveStaffingRule(Request $request)
    {
        $data=$request->validate(['unit_id'=>'required|exists:units,id','day_of_week'=>'nullable|integer|min:0|max:6','start_time'=>'required','end_time'=>'required','position_id'=>'nullable|exists:positions,id','competency_id'=>'nullable|exists:competencies,id','minimum_staff'=>'required|integer|min:1|max:100','is_hard_stop'=>'nullable|boolean']);
        $data['is_hard_stop']=$request->boolean('is_hard_stop'); $data['is_active']=true; $data['updated_by']=auth()->id();
        $rule=RosterStaffingRule::create($data);
        RosterAuditService::log('created',RosterStaffingRule::class,$rule->id,'Safe staffing / skill-mix rule added',null,$rule->toArray());
        return back()->with('success','Safe staffing rule added.');
    }
}
