<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\RosterAmendmentRequest;
use App\Models\RosterAssignment;
use App\Models\RosterOpenShift;
use App\Models\RosterOpenShiftClaim;
use App\Models\RosterShiftSwapRequest;
use App\Models\RosterPlan;
use App\Models\User;
use App\Services\RosterAuditService;
use App\Services\RosterComplianceService;
use App\Services\RosterWorkflowService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RosterAmendmentController extends Controller
{
    public function store(Request $request, RosterPlan $plan)
    {
        abort_unless(in_array($plan->status, ['approved','active'], true), 422, 'Only approved or active rosters use the governed amendment workflow.');
        $data = $request->validate([
            'action'=>'required|in:add,update,replace,cancel,swap',
            'roster_assignment_id'=>'nullable|exists:roster_assignments,id',
            'replacement_assignment_id'=>'nullable|exists:roster_assignments,id',
            'employee_id'=>'nullable|exists:employees,id','duty_date'=>'nullable|date','start_time'=>'nullable','end_time'=>'nullable',
            'duty_unit_id'=>'nullable|exists:units,id','duty_role'=>'nullable|string|max:150','details'=>'nullable|string|max:1500',
            'reason'=>'required|string|min:5|max:1500','is_emergency'=>'nullable|boolean',
        ]);
        if (in_array($data['action'], ['update','replace','cancel','swap'], true)) {
            abort_unless(!empty($data['roster_assignment_id']), 422, 'Select an existing assignment for this amendment.');
            abort_unless(RosterAssignment::whereKey($data['roster_assignment_id'])->where('roster_plan_id',$plan->id)->exists(), 422, 'Assignment does not belong to this roster.');
        }
        $proposed = collect($data)->only(['employee_id','duty_date','start_time','end_time','duty_unit_id','duty_role','details','replacement_assignment_id'])->filter(fn($v)=>$v!==null)->all();
        $amendment = RosterAmendmentRequest::create([
            'roster_plan_id'=>$plan->id,'roster_assignment_id'=>$data['roster_assignment_id'] ?? null,'action'=>$data['action'],
            'is_emergency'=>$request->boolean('is_emergency'),'proposed_values'=>$proposed,'reason'=>$data['reason'],'status'=>'pending',
            'requested_by'=>auth()->id(),'requested_at'=>now(),
        ]);
        RosterAuditService::log('created', RosterAmendmentRequest::class, $amendment->id, 'Approved/active roster amendment requested');
        return back()->with('success', 'Roster amendment submitted for approval. The published roster has not been changed.');
    }

    public function review(Request $request, RosterAmendmentRequest $amendment, RosterComplianceService $compliance)
    {
        $user = auth()->user();
        $plan = $amendment->plan()->with('approvals.level')->firstOrFail();
        $workflowAuthorised = $plan->approvals->contains(fn ($approval) => RosterWorkflowService::canAct($user, $approval));
        abort_unless($user->isSuperAdmin() || $workflowAuthorised, 403, 'You are not an authorised roster amendment approver.');
        abort_if((int)$amendment->requested_by === (int)$user->id && ! $user->isSuperAdmin(), 403, 'Segregation of duties prevents the requester from approving the same roster amendment.');
        abort_unless($amendment->status === 'pending', 422, 'This amendment has already been decided.');
        $data = $request->validate(['action'=>'required|in:approve,reject','review_note'=>'nullable|string|max:1200']);
        if ($data['action'] === 'reject') {
            $amendment->update(['status'=>'rejected','reviewed_by'=>auth()->id(),'reviewed_at'=>now(),'review_note'=>$data['review_note'] ?? null]);
            if ($amendment->source_type === 'open_shift_claim' && $amendment->source_id) {
                RosterOpenShiftClaim::whereKey($amendment->source_id)->update(['status'=>'rejected','reviewed_by'=>auth()->id(),'reviewed_at'=>now(),'review_note'=>'Published-roster amendment rejected. '.($data['review_note'] ?? '')]);
            } elseif ($amendment->source_type === 'shift_swap' && $amendment->source_id) {
                RosterShiftSwapRequest::whereKey($amendment->source_id)->update(['status'=>'rejected','reviewed_by'=>auth()->id(),'reviewed_at'=>now(),'review_note'=>'Published-roster amendment rejected. '.($data['review_note'] ?? '')]);
            }
            RosterAuditService::log('rejected', RosterAmendmentRequest::class, $amendment->id, 'Roster amendment rejected; published roster unchanged');
            NotificationService::send(
                User::find($amendment->requested_by),
                'roster_amendment_rejected',
                'Roster amendment rejected',
                'Your roster amendment request was rejected. The published roster remains unchanged.',
                route('roster.plans.show', $plan)
            );
            return back()->with('success', 'Roster amendment rejected; published roster unchanged.');
        }

        DB::transaction(function () use ($amendment,$data,$compliance) {
            $plan = $amendment->plan()->lockForUpdate()->firstOrFail();
            $snapshot = $plan->load('assignments')->toArray();
            DB::table('roster_revisions')->updateOrInsert(
                ['roster_plan_id'=>$plan->id,'revision_no'=>$plan->revision_no],
                ['snapshot'=>json_encode($snapshot),'reason'=>'Snapshot before approved amendment #'.$amendment->id,'created_by'=>auth()->id(),'created_at'=>now()]
            );
            $values = $amendment->proposed_values ?: [];
            $affectedDates = collect();
            $affectedContexts = collect();

            if ($amendment->action === 'cancel') {
                $assignment = $amendment->assignment()->lockForUpdate()->firstOrFail();
                $affectedDates->push($assignment->duty_date->format('Y-m-d'));
                $affectedContexts->push(['date'=>$assignment->duty_date->format('Y-m-d'),'unit_id'=>(int)$assignment->duty_unit_id]);
                $assignment->update(['status'=>'cancelled']);
            } elseif ($amendment->action === 'replace') {
                abort_unless(isset($values['employee_id']), 422, 'Replacement employee is required.');
                $assignment = $amendment->assignment()->lockForUpdate()->firstOrFail();
                $affectedDates->push($assignment->duty_date->format('Y-m-d'));
                $affectedContexts->push(['date'=>$assignment->duty_date->format('Y-m-d'),'unit_id'=>(int)$assignment->duty_unit_id]);
                $employee = Employee::findOrFail($values['employee_id'] ?? 0);
                $check = $compliance->evaluateEmployee($employee->id,$assignment->duty_date->format('Y-m-d'),$assignment->start_time,$assignment->end_time,$assignment->id,(int)$assignment->duty_unit_id,$assignment->position_id ? (int)$assignment->position_id : $employee->position_id);
                abort_if(!$check['allowed'],422,implode(' ', $check['hard_stops']));
                $assignment->update(['employee_id'=>$employee->id,'home_unit_id'=>$employee->unit_id,'position_id'=>$employee->position_id ?: $assignment->position_id,'coverage_type'=>(int)$employee->unit_id===(int)$assignment->duty_unit_id?'home_unit':'cross_unit','acknowledged_at'=>null]);
            } elseif ($amendment->action === 'update') {
                $assignment = $amendment->assignment()->lockForUpdate()->firstOrFail();
                $employee = Employee::findOrFail($values['employee_id'] ?? $assignment->employee_id);
                $dutyDate = $values['duty_date'] ?? $assignment->duty_date->format('Y-m-d');
                $start = $values['start_time'] ?? $assignment->start_time;
                $end = $values['end_time'] ?? $assignment->end_time;
                $unit = (int)($values['duty_unit_id'] ?? $assignment->duty_unit_id);
                abort_unless($dutyDate >= $plan->start_date->format('Y-m-d') && $dutyDate <= $plan->end_date->format('Y-m-d'), 422, 'Amended duty date must remain within the roster period.');
                $affectedDates->push($assignment->duty_date->format('Y-m-d'));
                $affectedDates->push($dutyDate);
                $affectedContexts->push(['date'=>$assignment->duty_date->format('Y-m-d'),'unit_id'=>(int)$assignment->duty_unit_id]);
                $affectedContexts->push(['date'=>$dutyDate,'unit_id'=>$unit]);
                $check = $compliance->evaluateEmployee($employee->id,$dutyDate,$start,$end,$assignment->id,$unit,$employee->position_id);
                abort_if(!$check['allowed'],422,implode(' ', $check['hard_stops']));
                $assignment->update(array_merge($values,['home_unit_id'=>$employee->unit_id,'position_id'=>$employee->position_id ?: $assignment->position_id,'coverage_type'=>(int)$employee->unit_id===$unit?'home_unit':'cross_unit','acknowledged_at'=>null]));
            } elseif ($amendment->action === 'swap') {
                abort_unless(isset($values['replacement_assignment_id']), 422, 'Second duty is required for a two-way swap.');
                $first = $amendment->assignment()->lockForUpdate()->firstOrFail();
                $secondAssignment = RosterAssignment::query()->lockForUpdate()->findOrFail($values['replacement_assignment_id']);
                abort_unless((int)$secondAssignment->roster_plan_id === (int)$plan->id, 422, 'Swap duties must belong to the same roster plan.');
                $affectedDates->push($first->duty_date->format('Y-m-d'));
                $affectedDates->push($secondAssignment->duty_date->format('Y-m-d'));
                $affectedContexts->push(['date'=>$first->duty_date->format('Y-m-d'),'unit_id'=>(int)$first->duty_unit_id]);
                $affectedContexts->push(['date'=>$secondAssignment->duty_date->format('Y-m-d'),'unit_id'=>(int)$secondAssignment->duty_unit_id]);
                $firstEmployee = Employee::findOrFail($first->employee_id);
                $secondEmployee = Employee::findOrFail($secondAssignment->employee_id);
                $firstCheck = $compliance->evaluateEmployee($secondEmployee->id,$first->duty_date->format('Y-m-d'),$first->start_time,$first->end_time,[$first->id,$secondAssignment->id],(int)$first->duty_unit_id,$first->position_id ? (int)$first->position_id : (int)$secondEmployee->position_id);
                $secondCheck = $compliance->evaluateEmployee($firstEmployee->id,$secondAssignment->duty_date->format('Y-m-d'),$secondAssignment->start_time,$secondAssignment->end_time,[$first->id,$secondAssignment->id],(int)$secondAssignment->duty_unit_id,$secondAssignment->position_id ? (int)$secondAssignment->position_id : (int)$firstEmployee->position_id);
                abort_if(!$firstCheck['allowed'],422,implode(' ', $firstCheck['hard_stops']));
                abort_if(!$secondCheck['allowed'],422,implode(' ', $secondCheck['hard_stops']));
                $first->update(['employee_id'=>$secondEmployee->id,'home_unit_id'=>$secondEmployee->unit_id,'position_id'=>$secondEmployee->position_id ?: $first->position_id,'coverage_type'=>(int)$secondEmployee->unit_id===(int)$first->duty_unit_id?'home_unit':'cross_unit','acknowledged_at'=>null]);
                $secondAssignment->update(['employee_id'=>$firstEmployee->id,'home_unit_id'=>$firstEmployee->unit_id,'position_id'=>$firstEmployee->position_id ?: $secondAssignment->position_id,'coverage_type'=>(int)$firstEmployee->unit_id===(int)$secondAssignment->duty_unit_id?'home_unit':'cross_unit','acknowledged_at'=>null]);
            } elseif ($amendment->action === 'add') {
                foreach (['employee_id','duty_date','start_time','end_time','duty_unit_id'] as $required) abort_unless(isset($values[$required]),422,'Missing '.$required.' for add-duty amendment.');
                abort_unless($values['duty_date'] >= $plan->start_date->format('Y-m-d') && $values['duty_date'] <= $plan->end_date->format('Y-m-d'), 422, 'Added duty date must remain within the roster period.');
                $affectedDates->push($values['duty_date']);
                $affectedContexts->push(['date'=>$values['duty_date'],'unit_id'=>(int)$values['duty_unit_id']]);
                $employee = Employee::findOrFail($values['employee_id']);
                $check = $compliance->evaluateEmployee($employee->id,$values['duty_date'],$values['start_time'],$values['end_time'],null,(int)$values['duty_unit_id'],$employee->position_id);
                abort_if(!$check['allowed'],422,implode(' ', $check['hard_stops']));
                $plan->assignments()->create(array_merge($values,['home_unit_id'=>$employee->unit_id,'position_id'=>$employee->position_id,'coverage_type'=>(int)$employee->unit_id===(int)$values['duty_unit_id']?'home_unit':'cross_unit','status'=>$plan->status==='active'?'active':'planned','assigned_by'=>auth()->id()]));
            }

            foreach ($affectedContexts->unique(fn ($ctx) => $ctx['date'].'|'.$ctx['unit_id']) as $ctx) {
                $hardIssues = $compliance->staffingIssues((int)$ctx['unit_id'], (string)$ctx['date'])
                    ->filter(fn ($row) => (bool)$row['rule']->is_hard_stop);
                abort_if($hardIssues->isNotEmpty(), 422, 'Amendment would leave a configured hard safe-staffing/skill-mix shortfall on '.\Carbon\Carbon::parse($ctx['date'])->format('d M Y').'.');
            }

            $newRevision = $plan->revision_no + 1;
            $plan->update(['revision_no'=>$newRevision]);
            $amendment->update(['status'=>'approved','reviewed_by'=>auth()->id(),'reviewed_at'=>now(),'review_note'=>$data['review_note'] ?? null,'applied_revision_no'=>$newRevision]);

            if ($amendment->source_type === 'shift_swap' && $amendment->source_id) {
                RosterShiftSwapRequest::whereKey($amendment->source_id)->update(['status'=>'approved','reviewed_by'=>auth()->id(),'reviewed_at'=>now(),'review_note'=>'Applied through roster revision '.$newRevision.'.']);
            } elseif ($amendment->source_type === 'open_shift_claim' && $amendment->source_id) {
                $claim = RosterOpenShiftClaim::find($amendment->source_id);
                if ($claim) {
                    $claim->update(['status'=>'approved','reviewed_by'=>auth()->id(),'reviewed_at'=>now(),'review_note'=>'Applied through roster revision '.$newRevision.'.']);
                    $shift = RosterOpenShift::query()->lockForUpdate()->find($claim->roster_open_shift_id);
                    if ($shift) {
                        $shift->increment('filled_staff'); $shift->refresh();
                        if ($shift->filled_staff >= $shift->required_staff) {
                            $shift->update(['status'=>'filled']);
                            RosterOpenShiftClaim::where('roster_open_shift_id',$shift->id)->where('status','pending_manager')->update(['status'=>'closed_unfilled','review_note'=>'Shift filled by another approved claim.']);
                        }
                    }
                }
            } elseif ($amendment->source_type === 'manager_open_shift' && $amendment->source_id) {
                $shift = RosterOpenShift::query()->lockForUpdate()->find($amendment->source_id);
                if ($shift) {
                    $shift->increment('filled_staff'); $shift->refresh();
                    if ($shift->filled_staff >= $shift->required_staff) $shift->update(['status'=>'filled']);
                }
            }

            RosterAuditService::log('updated', RosterPlan::class, $plan->id, 'Applied governed roster amendment; revision '.$newRevision);
            NotificationService::send(
                User::find($amendment->requested_by),
                'roster_amendment_approved',
                'Roster amendment approved',
                'Your roster amendment was approved and applied as revision '.$newRevision.'.',
                route('roster.plans.show', $plan)
            );
        });
        return back()->with('success', 'Amendment approved and a new roster revision was created.');
    }
}
