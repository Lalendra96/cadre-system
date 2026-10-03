<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\RosterAcknowledgement;
use App\Models\RosterAssignment;
use App\Models\RosterOpenShift;
use App\Models\RosterOpenShiftClaim;
use App\Models\RosterShiftSwapRequest;
use App\Models\User;
use App\Services\RosterAuditService;
use App\Services\RosterComplianceService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RosterEmployeeController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        abort_unless($user->employee_id, 403, 'This account is not linked to an employee profile.');
        $employeeId = (int) $user->employee_id;

        $assignments = RosterAssignment::with(['dutyUnit','plan'])
            ->where('employee_id', $employeeId)
            ->whereDate('duty_date', '>=', today())
            ->whereNotIn('status', ['cancelled'])
            ->whereHas('plan', fn ($q) => $q->whereIn('status', ['approved','active']))
            ->orderBy('duty_date')->orderBy('start_time')->limit(60)->get();

        $openShifts = RosterOpenShift::with(['plan','unit'])
            ->where('status', 'open')->whereDate('duty_date', '>=', today())
            ->where(function ($q) use ($user) {
                $q->whereNull('position_id')
                    ->orWhere('position_id', Employee::whereKey($user->employee_id)->value('position_id'));
            })
            ->orderBy('duty_date')->limit(60)->get();

        $claims = RosterOpenShiftClaim::with(['shift.unit'])->where('employee_id', $employeeId)->latest()->limit(30)->get();
        $incomingSwaps = RosterShiftSwapRequest::with(['assignment','requester'])
            ->where('target_employee_id', $employeeId)->where('status', 'pending_peer')->latest()->get();
        $outgoingSwaps = RosterShiftSwapRequest::with(['assignment','target'])
            ->where('requested_by_employee_id', $employeeId)->latest()->limit(30)->get();
        $potentialAssignments = RosterAssignment::with(['employee','dutyUnit'])
            ->where('employee_id','<>',$employeeId)->whereDate('duty_date','>=',today())
            ->whereNotIn('status',['cancelled'])->whereHas('plan', fn ($q) => $q->whereIn('status',['approved','active']))->orderBy('duty_date')->orderBy('start_time')->limit(150)->get();

        return view('roster.employee.index', compact('assignments','openShifts','claims','incomingSwaps','outgoingSwaps','potentialAssignments'));
    }

    public function claim(Request $request, RosterOpenShift $shift, RosterComplianceService $compliance)
    {
        $user = auth()->user();
        abort_unless($user->employee_id, 403);
        abort_unless($shift->status === 'open', 422, 'This shift is no longer open.');
        $employee = Employee::findOrFail($user->employee_id);
        abort_if($shift->position_id && (int)$shift->position_id !== (int)$employee->position_id, 422, 'Your current position does not match this open shift.');

        $check = $compliance->evaluateEmployee(
            (int)$employee->id,
            $shift->duty_date->format('Y-m-d'),
            $shift->start_time,
            $shift->end_time,
            null,
            (int)$shift->duty_unit_id,
            $shift->position_id ? (int)$shift->position_id : (int)$employee->position_id
        );
        abort_if(!$check['allowed'], 422, implode(' ', $check['hard_stops']));

        $claim = RosterOpenShiftClaim::updateOrCreate(
            ['roster_open_shift_id'=>$shift->id,'employee_id'=>$employee->id],
            ['requested_by'=>$user->id,'note'=>$request->validate(['note'=>'nullable|string|max:500'])['note'] ?? null,'status'=>'pending_manager','compliance_snapshot'=>$check,'reviewed_by'=>null,'reviewed_at'=>null,'review_note'=>null]
        );
        RosterAuditService::log('created', RosterOpenShiftClaim::class, $claim->id, 'Employee claimed an open roster shift pending manager approval', null, $claim->toArray());
        NotificationService::send(User::find($shift->plan?->created_by), 'roster_open_shift_claim', 'Open shift claimed', 'An employee has claimed an open roster shift. Manager review is required before the published roster can change.', route('roster.operations.index'));
        return back()->with('success', 'Your open-shift claim was submitted for manager approval.');
    }

    public function requestSwap(Request $request, RosterAssignment $assignment)
    {
        $user = auth()->user();
        abort_unless($user->employee_id && (int)$assignment->employee_id === (int)$user->employee_id, 403);
        abort_unless(in_array($assignment->plan?->status, ['approved','active'], true), 422, 'Only published roster duties can be swapped through employee self-service.');
        $data = $request->validate([
            'swap_type'=>'required|in:swap,give_away',
            'replacement_assignment_id'=>'nullable|exists:roster_assignments,id',
            'reason'=>'required|string|max:1000',
        ]);
        $targetEmployeeId = null;
        if ($data['swap_type'] === 'swap') {
            abort_unless(!empty($data['replacement_assignment_id']), 422, 'Choose the duty you want to swap with.');
            $replacement = RosterAssignment::with('employee')->findOrFail($data['replacement_assignment_id']);
            abort_unless((int)$replacement->roster_plan_id === (int)$assignment->roster_plan_id, 422, 'Two-way swaps must stay within the same roster plan.');
            abort_if((int)$replacement->employee_id === (int)$assignment->employee_id, 422, "Choose another employee's duty.");
            $targetEmployeeId = $replacement->employee_id;
        }
        $swap = RosterShiftSwapRequest::create([
            'assignment_id'=>$assignment->id,'requested_by_employee_id'=>$assignment->employee_id,
            'target_employee_id'=>$targetEmployeeId,'replacement_assignment_id'=>$data['replacement_assignment_id'] ?? null,
            'swap_type'=>$data['swap_type'],'reason'=>$data['reason'],
            'status'=>$targetEmployeeId ? 'pending_peer' : 'pending_manager',
        ]);
        RosterAuditService::log('created', RosterShiftSwapRequest::class, $swap->id, 'Employee submitted roster shift change request', null, $swap->toArray());
        if ($targetEmployeeId) {
            NotificationService::send(User::where('employee_id',$targetEmployeeId)->where('is_active',true)->first(), 'roster_swap_request', 'Roster swap request', 'Another employee requested a two-way roster swap with one of your duties. Your response is required before manager review.', route('roster.employee.index'));
        } else {
            NotificationService::send(User::find($assignment->plan?->created_by), 'roster_replacement_request', 'Roster replacement requested', 'An employee requested a replacement for a published duty. Manager review is required.', route('roster.operations.index'));
        }
        return back()->with('success', $targetEmployeeId ? 'Swap request sent to the other employee for acceptance.' : 'Replacement request sent to your manager.');
    }

    public function respondSwap(Request $request, RosterShiftSwapRequest $swap, RosterComplianceService $compliance)
    {
        $user = auth()->user();
        abort_unless($user->employee_id && (int)$swap->target_employee_id === (int)$user->employee_id, 403);
        abort_unless($swap->status === 'pending_peer', 422, 'This request is no longer awaiting your response.');
        $data = $request->validate(['action'=>'required|in:accept,reject','note'=>'nullable|string|max:700']);

        if ($data['action'] === 'reject') {
            $old=$swap->toArray();
            $swap->update(['status'=>'peer_rejected','peer_responded_at'=>now(),'peer_response_note'=>$data['note'] ?? null]);
            RosterAuditService::log('updated', RosterShiftSwapRequest::class, $swap->id, 'Target employee declined roster swap', $old, $swap->fresh()->toArray());
            NotificationService::send(User::where('employee_id',$swap->requested_by_employee_id)->where('is_active',true)->first(), 'roster_swap_declined', 'Roster swap declined', 'The employee you invited declined the roster swap request.', route('roster.employee.index'));
            return back()->with('success', 'Shift swap declined.');
        }

        $assignment = $swap->assignment;
        $check = $compliance->evaluateEmployee(
            (int)$user->employee_id,
            $assignment->duty_date->format('Y-m-d'),
            $assignment->start_time,
            $assignment->end_time,
            $swap->swap_type === 'swap' && $swap->replacement_assignment_id ? [$assignment->id,(int)$swap->replacement_assignment_id] : $assignment->id,
            (int)$assignment->duty_unit_id,
            $assignment->position_id ? (int)$assignment->position_id : null
        );
        abort_if(!$check['allowed'], 422, implode(' ', $check['hard_stops']));
        if ($swap->swap_type === 'swap' && $swap->replacement_assignment_id) {
            $replacement = RosterAssignment::findOrFail($swap->replacement_assignment_id);
            $second = $compliance->evaluateEmployee(
                (int)$swap->requested_by_employee_id,
                $replacement->duty_date->format('Y-m-d'),
                $replacement->start_time,
                $replacement->end_time,
                [$assignment->id,$replacement->id],
                (int)$replacement->duty_unit_id,
                $replacement->position_id ? (int)$replacement->position_id : null
            );
            abort_if(!$second['allowed'], 422, 'The original employee cannot safely take your duty: '.implode(' ', $second['hard_stops']));
        }
        $old=$swap->toArray();
        $swap->update([
            'status'=>'pending_manager','peer_accepted_by'=>$user->id,'peer_accepted_at'=>now(),
            'peer_responded_at'=>now(),'peer_response_note'=>$data['note'] ?? null,
        ]);
        RosterAuditService::log('updated', RosterShiftSwapRequest::class, $swap->id, 'Target employee accepted roster swap; manager approval required', $old, $swap->fresh()->toArray());
        NotificationService::send(User::find($assignment->plan?->created_by), 'roster_swap_peer_accepted', 'Roster swap ready for manager review', 'Both employees have agreed to a roster swap. Manager review is required before any roster change.', route('roster.operations.index'));
        return back()->with('success', 'Swap accepted and forwarded for manager approval.');
    }

    public function acknowledge(Request $request, RosterAssignment $assignment)
    {
        $user = auth()->user();
        abort_unless($user->employee_id && (int)$assignment->employee_id === (int)$user->employee_id, 403);
        abort_unless(in_array($assignment->plan?->status, ['approved','active'], true), 422, 'Only published roster duties can be acknowledged.');
        abort_if($assignment->status === 'cancelled', 422, 'Cancelled duties cannot be acknowledged.');
        DB::transaction(function () use ($request,$assignment,$user) {
            $assignment->update(['acknowledged_at'=>now()]);
            RosterAcknowledgement::updateOrCreate(
                ['roster_assignment_id'=>$assignment->id,'employee_id'=>$user->employee_id],
                ['acknowledged_by'=>$user->id,'acknowledged_at'=>now(),'source_ip'=>$request->ip(),'user_agent'=>substr((string)$request->userAgent(),0,255)]
            );
        });
        RosterAuditService::log('updated', RosterAssignment::class, $assignment->id, 'Employee acknowledged roster duty');
        return back()->with('success', 'Duty acknowledgement recorded.');
    }
}
