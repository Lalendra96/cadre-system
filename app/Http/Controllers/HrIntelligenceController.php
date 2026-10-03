<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\HrReassignmentCase;
use App\Models\Position;
use App\Models\User;
use App\Services\HrIntelligenceService;
use App\Services\HrResponsibilityService;
use App\Services\HrWorkloadService;
use App\Services\WorkforceScopeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HrIntelligenceController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $manager = HrIntelligenceService::isManager($user);
        abort_unless($user->is_active && ($manager || $user->isSubjectOfficer()), 403);
        $filters = $request->validate([
            'status' => ['nullable', 'in:open,accepted,superseded,all'],
            'employee_id' => ['nullable', 'integer', 'min:1'],
        ]);
        $status = $filters['status'] ?? 'open';
        $cases = HrReassignmentCase::with(['employee.position', 'proposedOfficer', 'acceptedOfficer'])
            ->when(
                ! $manager,
                fn ($query) => $query->whereHas(
                    'employee',
                    fn ($query) => $query
                        ->whereNull('employees.deleted_at')
                        ->whereIn('id', WorkforceScopeService::allocatedEmployeeIds($user)),
                ),
            )
            ->when(isset($filters['employee_id']), fn ($query) => $query->where('employee_id', $filters['employee_id']))
            ->when($status === 'open', fn ($query) => $query->whereIn('status', HrReassignmentCase::OPEN_STATUSES))
            ->when(in_array($status, ['accepted', 'superseded'], true), fn ($query) => $query->where('status', $status))
            ->orderBy('created_at')
            ->paginate(20)
            ->withQueryString();
        $eligible = HrResponsibilityService::recipientMap();
        $workloads = HrWorkloadService::rows($user);
        $alerts = $manager
            ? DB::table('hr_coverage_alerts')
                ->join('positions', 'positions.id', '=', 'hr_coverage_alerts.position_id')
                ->where('is_open', true)
                ->select('hr_coverage_alerts.*', 'positions.title')
                ->orderBy('opened_at')
                ->paginate(20, ['*'], 'alerts_page')
            : null;
        $lastChecked = DB::table('hr_intelligence_status')->where('id', 1)->value('last_completed_at');

        return view(
            'hr-intelligence.index',
            compact('cases', 'eligible', 'workloads', 'alerts', 'manager', 'status', 'lastChecked'),
        );
    }

    public function refresh(Request $request): RedirectResponse
    {
        abort_unless(HrIntelligenceService::isManager($request->user()), 403);
        $result = HrIntelligenceService::synchronize($request->user());

        return back()->with(
            'success',
            $result['changed'].' employee ownership changes/baselines recorded. Coverage alerts refreshed.',
        );
    }

    public function propose(Request $request, HrReassignmentCase $case): RedirectResponse
    {
        abort_unless(HrIntelligenceService::isManager($request->user()), 403);
        $data = $request->validate([
            'officer_id' => ['required', 'integer', 'exists:users,id'],
            'note' => ['required', 'string', 'max:2000'],
        ]);
        HrIntelligenceService::propose($request->user(), $case, (int) $data['officer_id'], $data['note']);

        return back()->with('success', 'Responsible officer proposed. The officer must accept the pending work.');
    }

    public function accept(Request $request, HrReassignmentCase $case): RedirectResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:2000']]);
        HrIntelligenceService::accept($request->user(), $case, $data['note']);

        return back()->with(
            'success',
            'Open data-quality, increment and retirement work reassigned. Completed work is preserved.',
        );
    }

    public function history(Request $request, Employee $employee): View
    {
        $user = $request->user();
        abort_unless(
            $user->is_active &&
                (HrIntelligenceService::isManager($user) ||
                    ($user->isSubjectOfficer() && WorkforceScopeService::isEmployeeAllocatedTo($user, $employee))),
            403,
        );
        $events = DB::table('hr_ownership_events')
            ->where('employee_id', $employee->id)
            ->orderByDesc('id')
            ->paginate(30);
        $userIds = collect();
        $positionIds = collect();
        foreach ($events as $event) {
            $event->previous_state = $event->previous_state
                ? json_decode($event->previous_state, true, 512, JSON_THROW_ON_ERROR)
                : null;
            $event->current_state = json_decode($event->current_state, true, 512, JSON_THROW_ON_ERROR);
            foreach ([$event->previous_state, $event->current_state] as $state) {
                $userIds = $userIds->merge($state['owner_ids'] ?? []);
                if (isset($state['position_id'])) {
                    $positionIds->push($state['position_id']);
                }
            }
        }
        $names = User::whereIn('id', $userIds->unique())->pluck('name', 'id');
        $positions = Position::whereIn('id', $positionIds->unique())->pluck('title', 'id');
        $cases = HrReassignmentCase::where('employee_id', $employee->id)
            ->with(['proposedOfficer', 'acceptedOfficer'])
            ->latest()
            ->paginate(15, ['*'], 'case_page');

        return view('hr-intelligence.history', compact('employee', 'events', 'names', 'positions', 'cases'));
    }
}
