<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Employee;
use App\Models\RosterPlan;
use App\Models\RosterTemplate;
use App\Models\RosterStaffingRule;
use App\Services\RosterAuditService;
use App\Services\RosterWorkflowService;
use App\Services\RosterComplianceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RosterPlanController extends Controller
{
    public function index()
    {
        $plans = RosterPlan::with('unit')->orderByDesc('id')->paginate(25);

        return view('roster.plans.index', compact('plans'));
    }

    public function create()
    {
        $templates = RosterTemplate::with('slots')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $units = DB::table('units')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Do not fetch employees with DB::table(). Employee names are encrypted
        // at rest and are transparently decrypted by the Employee model trait.
        // Convert to a deliberately small plain payload before passing it to JS.
        $employees = Employee::query()
            ->with(['competencies'])
            ->where('is_active', true)
            ->get(['id', 'salutation', 'name', 'unit_id', 'position_id'])
            ->map(static fn (Employee $employee): array => [
                'id' => $employee->id,
                // Both values have passed through the Employee model and are decrypted.
                // `plain_name` intentionally excludes salutation so roster initials are
                // derived from the employee's actual first/surname (Keerthi Tennakoon -> KT).
                'name' => $employee->display_name,
                'plain_name' => trim((string) $employee->name),
                'unit_id' => $employee->unit_id,
                'position_id' => $employee->position_id,
                'competency_ids' => $employee->competencies
                    ->filter(fn ($competency) => ! $competency->expires_on || $competency->expires_on->gte(today()))
                    ->pluck('competency_id')->map(fn ($id) => (int) $id)->values()->all(),
            ])
            ->sortBy(fn (array $employee) => mb_strtolower($employee['name'], 'UTF-8'))
            ->values();

        $staffingRules = RosterStaffingRule::with(['position','competency'])->where('is_active', true)->get()->map(fn($r) => [
            'id'=>$r->id,'unit_id'=>$r->unit_id,'day_of_week'=>$r->day_of_week,'start_time'=>substr((string)$r->start_time,0,5),
            'end_time'=>substr((string)$r->end_time,0,5),'position_id'=>$r->position_id,'position'=>$r->position?->title,
            'competency_id'=>$r->competency_id,'competency'=>$r->competency?->name,'minimum_staff'=>$r->minimum_staff,'is_hard_stop'=>$r->is_hard_stop,
        ])->values();

        return view('roster.plans.form', compact('templates', 'units', 'employees', 'staffingRules'));
    }

    public function store(Request $request, RosterComplianceService $compliance)
    {
        $data = $request->validate([
            'title' => 'required|string|max:180',
            'unit_id' => 'required|exists:units,id',
            'roster_template_id' => 'nullable|exists:roster_templates,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
            'assignments' => 'required|array|min:1',
            'assignments.*.employee_id' => 'required|exists:employees,id',
            'assignments.*.duty_date' => 'required|date',
            'assignments.*.start_time' => 'required',
            'assignments.*.end_time' => 'required',
            'assignments.*.duty_unit_id' => 'required|exists:units,id',
            'assignments.*.duty_role' => 'nullable|string|max:150',
            'assignments.*.location' => 'nullable|string|max:150',
            'assignments.*.details' => 'nullable|string',
        ]);

        foreach ($data['assignments'] as $index => $row) {
            abort_unless($row['duty_date'] >= $data['start_date'] && $row['duty_date'] <= $data['end_date'], 422, 'Assignment '.($index + 1).' is outside the roster period.');
            $employee = Employee::findOrFail($row['employee_id']);
            $check = $compliance->evaluateEmployee((int)$employee->id,$row['duty_date'],$row['start_time'],$row['end_time'],null,(int)$row['duty_unit_id'],$employee->position_id ? (int)$employee->position_id : null);
            abort_if(!$check['allowed'], 422, 'Assignment '.($index + 1).': '.implode(' ', $check['hard_stops']));
        }

        // Validate overlap inside this newly submitted batch as well. The normal
        // compliance query can only see assignments already persisted in the DB.
        // This prevents a single create request from assigning the same employee
        // to two overlapping duties before either row has been inserted.
        $assignmentCount = count($data['assignments']);
        for ($i = 0; $i < $assignmentCount; $i++) {
            $left = $data['assignments'][$i];
            for ($j = $i + 1; $j < $assignmentCount; $j++) {
                $right = $data['assignments'][$j];
                if ((int) $left['employee_id'] !== (int) $right['employee_id']) {
                    continue;
                }

                $leftStart = Carbon::parse($left['duty_date'].' '.$left['start_time']);
                $leftEnd = Carbon::parse($left['duty_date'].' '.$left['end_time']);
                if ($leftEnd->lte($leftStart)) $leftEnd->addDay();
                $rightStart = Carbon::parse($right['duty_date'].' '.$right['start_time']);
                $rightEnd = Carbon::parse($right['duty_date'].' '.$right['end_time']);
                if ($rightEnd->lte($rightStart)) $rightEnd->addDay();

                abort_if(
                    $leftStart->lt($rightEnd) && $leftEnd->gt($rightStart),
                    422,
                    'Assignments '.($i + 1).' and '.($j + 1).' overlap for the same employee.'
                );
            }
        }

        return DB::transaction(function () use ($data) {
            $assignments = $data['assignments'];
            unset($data['assignments']);

            $data['reference_no'] = 'RST-'.now()->format('Ymd').'-'.str_pad(
                (string) ((DB::table('roster_plans')->max('id') ?? 0) + 1),
                5,
                '0',
                STR_PAD_LEFT
            );
            $data['created_by'] = auth()->id();

            $plan = RosterPlan::create($data);

            foreach ($assignments as $row) {
                // Employee model is used intentionally even though only unit and
                // position are needed here. It prevents future roster code from
                // accidentally handling protected Employee fields as raw values.
                $employee = Employee::query()->findOrFail($row['employee_id']);

                $row['home_unit_id'] = $employee->unit_id;
                $row['position_id'] = $employee->position_id;
                $row['coverage_type'] = (int) $employee->unit_id === (int) $row['duty_unit_id']
                    ? 'home_unit'
                    : 'cross_unit';
                $row['assigned_by'] = auth()->id();

                $plan->assignments()->create($row);
            }

            RosterAuditService::log(
                'created',
                RosterPlan::class,
                $plan->id,
                'Created roster plan',
                null,
                $plan->load('assignments')->toArray()
            );

            return redirect()
                ->route('roster.plans.show', $plan)
                ->with('success', 'Roster plan created as draft.');
        });
    }

    public function show(RosterPlan $plan)
    {
        $user = auth()->user();
        $plan->load([
            'unit',
            'template',
            'assignments.employee',
            'assignments.homeUnit',
            'assignments.dutyUnit',
            'approvals.level',
            'amendments.assignment.employee',
            'assignments.acknowledgement',
        ]);

        $canManage = $user->hasAnyRole([
            'super_admin',
            'admin_group',
            'planning_officer',
            'unit_manager',
        ]);

        $canApprove = $plan->approvals->contains(
            fn ($approval) => $approval->status === 'pending'
                && RosterWorkflowService::canAct($user, $approval)
        );
        $canWorkflowView = $plan->approvals->contains(
            fn ($approval) => RosterWorkflowService::canAct($user, $approval)
        );

        abort_unless(
            $canManage || $canApprove || $canWorkflowView,
            403,
            'You are not authorised to view this roster plan.'
        );

        $staffingIssues = collect();
        $cursor = $plan->start_date->copy();
        while ($cursor->lte($plan->end_date)) {
            $date = $cursor->format('Y-m-d');
            $unitIds = $plan->assignments
                ->filter(fn ($assignment) => $assignment->duty_date?->format('Y-m-d') === $date)
                ->pluck('duty_unit_id')
                ->push($plan->unit_id)
                ->filter()->unique()->values();

            $dailyIssues = collect();
            foreach ($unitIds as $unitId) {
                $dailyIssues = $dailyIssues->merge(
                    app(RosterComplianceService::class)->staffingIssues((int) $unitId, $date)
                );
            }
            if ($dailyIssues->isNotEmpty()) $staffingIssues->put($date, $dailyIssues);
            $cursor->addDay();
        }
        $employees = Employee::query()->where('is_active', true)->get(['id','salutation','name','unit_id','position_id'])
            ->sortBy(fn ($employee) => mb_strtolower($employee->display_name, 'UTF-8'))->values();
        $units = \App\Models\Unit::cachedActive();
        $canReviewAmendments = $user->isSuperAdmin() || $plan->approvals->contains(fn ($approval) => RosterWorkflowService::canAct($user, $approval));
        return view('roster.plans.show', compact('plan','staffingIssues','employees','units','canReviewAmendments'));
    }

    public function submit(RosterPlan $plan)
    {
        abort_unless(in_array($plan->status, ['draft', 'returned'], true), 422);

        $plan->loadMissing('assignments');
        $cursor = $plan->start_date->copy();
        while ($cursor->lte($plan->end_date)) {
            $date = $cursor->format('Y-m-d');
            $unitIds = $plan->assignments
                ->filter(fn ($assignment) => $assignment->duty_date?->format('Y-m-d') === $date)
                ->pluck('duty_unit_id')
                ->push($plan->unit_id)
                ->filter()->unique()->values();

            foreach ($unitIds as $unitId) {
                $issues = app(RosterComplianceService::class)->staffingIssues((int) $unitId, $date);
                $hardIssues = $issues->filter(fn ($row) => (bool) $row['rule']->is_hard_stop);
                abort_if(
                    $hardIssues->isNotEmpty(),
                    422,
                    'Roster cannot be submitted: a configured hard safe-staffing/skill-mix rule is below minimum on '.$cursor->format('d M Y').'.'
                );
            }
            $cursor->addDay();
        }

        DB::transaction(function () use ($plan) {
            RosterWorkflowService::initialize($plan);
            $plan->update([
                'status' => 'in_approval',
                'submitted_by' => auth()->id(),
                'submitted_at' => now(),
            ]);
            RosterAuditService::log(
                'submitted',
                RosterPlan::class,
                $plan->id,
                'Submitted roster plan for approval'
            );
        });

        return back()->with('success', 'Roster submitted for approval.');
    }

    public function start(RosterPlan $plan)
    {
        abort_unless($plan->status === 'approved', 422, 'Only approved rosters can be started.');

        $plan->update([
            'status' => 'active',
            'started_by' => auth()->id(),
            'started_at' => now(),
        ]);
        $plan->assignments()->update(['status' => 'active']);

        RosterAuditService::log(
            'updated',
            RosterPlan::class,
            $plan->id,
            'Started roster assignment'
        );

        return back()->with('success', 'Roster assignment started.');
    }

    public function complete(RosterPlan $plan)
    {
        abort_unless($plan->status === 'active', 422);

        $plan->update([
            'status' => 'completed',
            'completed_by' => auth()->id(),
            'completed_at' => now(),
        ]);
        $plan->assignments()->update(['status' => 'completed']);

        RosterAuditService::log(
            'updated',
            RosterPlan::class,
            $plan->id,
            'Completed roster assignment'
        );

        return back()->with('success', 'Roster completed.');
    }

    public function checkConflict(Request $request, RosterComplianceService $compliance)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'duty_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'assignment_id' => 'nullable|exists:roster_assignments,id',
            'duty_unit_id' => 'nullable|exists:units,id',
            'position_id' => 'nullable|exists:positions,id',
        ]);

        $result = $compliance->evaluateEmployee(
            (int) $data['employee_id'],
            $data['duty_date'],
            $data['start_time'],
            $data['end_time'],
            isset($data['assignment_id']) ? (int) $data['assignment_id'] : null,
            isset($data['duty_unit_id']) ? (int) $data['duty_unit_id'] : null,
            isset($data['position_id']) ? (int) $data['position_id'] : null
        );

        return response()->json([
            'conflict' => !$result['allowed'],
            'hard_stops' => $result['hard_stops'],
            'warnings' => $result['warnings'],
        ]);
    }
}
