<?php

namespace App\Http\Controllers;

use App\Models\RosterApproval;
use App\Services\RosterAuditService;
use App\Services\RosterWorkflowService;
use App\Services\NotificationService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class RosterApprovalController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(RosterWorkflowService::canAccessApprovals($request->user()), 403, 'You do not have a roster approval responsibility.');

        $user = $request->user();

        // Fetch pending approvals and expose only those the signed-in user is
        // actually authorised to action. This prevents cross-unit/workflow
        // approval queues leaking to unrelated users.
        $eligible = RosterApproval::with(['plan.unit', 'plan.creator', 'level'])
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->get()
            ->filter(fn (RosterApproval $approval) => RosterWorkflowService::canAct($user, $approval))
            ->values();

        $perPage = 30;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $approvals = new LengthAwarePaginator(
            $eligible->forPage($page, $perPage)->values(),
            $eligible->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('roster.approvals.index', compact('approvals'));
    }

    public function act(Request $request, RosterApproval $approval)
    {
        $request->validate([
            'action' => 'required|in:approve,return',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $approval->load(['level', 'plan']);
        abort_unless(RosterWorkflowService::canAct($request->user(), $approval), 403);

        $priorPending = $approval->plan->approvals()
            ->where('level_no', '<', $approval->level_no)
            ->where('status', '!=', 'approved')
            ->exists();

        abort_if($priorPending, 422, 'A previous approval level is still pending.');

        DB::transaction(function () use ($request, $approval) {
            if ($request->action === 'return') {
                abort_unless($approval->level->allow_return, 422);
                abort_if(blank($request->remarks), 422, 'Remarks are required when returning a roster.');

                $approval->update([
                    'status' => 'returned',
                    'acted_by' => $request->user()->id,
                    'acted_at' => now(),
                    'remarks' => $request->remarks,
                ]);
                $approval->plan->update(['status' => 'returned']);
            } else {
                $approval->update([
                    'status' => 'approved',
                    'acted_by' => $request->user()->id,
                    'acted_at' => now(),
                    'remarks' => $request->remarks,
                ]);

                $remaining = $approval->plan->approvals()->where('status', 'pending')->exists();
                if (! $remaining) {
                    $approval->plan->update(['status' => 'approved', 'approved_at' => now()]);
                    $employeeIds = $approval->plan->assignments()->pluck('employee_id')->unique()->values();
                    $users = User::query()->whereIn('employee_id', $employeeIds)->where('is_active', true)->get();
                    NotificationService::sendToMany(
                        $users,
                        'roster_published',
                        'Roster approved',
                        'A roster containing one or more of your duties has been approved. Review and acknowledge your published duties.',
                        route('roster.employee.index')
                    );
                }
            }

            if ($request->action === 'return') {
                NotificationService::send(
                    $approval->plan->creator,
                    'roster_returned',
                    'Roster returned for correction',
                    'A submitted roster was returned for correction. Review the recorded remarks before resubmission.',
                    route('roster.plans.show', $approval->plan)
                );
            }

            RosterAuditService::log(
                'updated',
                RosterApproval::class,
                $approval->id,
                'Roster approval action: '.$request->action,
                null,
                ['remarks' => $request->remarks, 'plan_id' => $approval->roster_plan_id]
            );
        });

        return back()->with('success', 'Approval action recorded.');
    }
}
