<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeHrAllocation;
use App\Models\HrHandover;
use App\Models\HrResponsibility;
use App\Models\Position;
use App\Models\User;
use App\Services\HrResponsibilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HrResponsibilityController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->is_active && $user->hasAnyRole(User::OPERATIONAL_ROLES), 403);
        $manager = $user->isSuperAdmin() || $user->isPlanningOfficer() || $user->isHrRecordsManager();
        $assignments = HrResponsibility::with(['user.userRoles', 'position', 'assignedBy'])
            ->when(! $manager, fn ($query) => $query->where('user_id', $user->id))
            ->latest('id')
            ->paginate(25, ['*'], 'assignments_page');
        $handovers = HrHandover::with(['responsibility.user', 'responsibility.position', 'recipient'])
            ->when(
                ! $manager,
                fn ($query) => $query->where(function ($query) use ($user) {
                    $query
                        ->where('to_user_id', $user->id)
                        ->orWhereHas('responsibility', fn ($query) => $query->where('user_id', $user->id));
                }),
            )
            ->latest('id')
            ->paginate(15, ['*'], 'handovers_page');
        $positions = $manager ? Position::orderBy('title')->get() : collect();
        $officers = $user->isSuperAdmin()
            ? User::active()->havingRole(User::ROLE_SUBJECT_OFFICER)->orderBy('name')->get()
            : collect();
        $ongoing = $user->isSuperAdmin()
            ? HrResponsibility::effective()
                ->where('kind', 'permanent')
                ->whereNull('ends_on')
                ->with(['position', 'user'])
                ->get()
            : collect();
        $health = collect();
        $unpositioned = 0;
        if ($manager) {
            $map = HrResponsibilityService::recipientMap();
            $counts = Employee::active()
                ->selectRaw('position_id, count(*) as total')
                ->groupBy('position_id')
                ->pluck('total', 'position_id');
            $allocatedCounts = EmployeeHrAllocation::query()->effective()
                ->selectRaw('position_id, count(distinct employee_id) as total')
                ->groupBy('position_id')
                ->pluck('total', 'position_id');
            $health = $positions->map(function (Position $position) use ($map, $counts, $allocatedCounts) {
                $owners = $map->get($position->id, collect());
                $employees = (int) $counts->get($position->id, 0);
                $allocated = (int) $allocatedCounts->get($position->id, 0);

                return [
                    'position' => $position,
                    'owners' => $owners,
                    'employees' => $employees,
                    'allocated' => $allocated,
                    'unallocated' => max(0, $employees - $allocated),
                ];
            });
            $unpositioned = Employee::active()->whereNull('position_id')->count();
        }
        $effectivePositions = Position::whereIn('id', $user->effectiveHrPositionIds())->orderBy('title')->get();

        $employeeAllocations = collect();
        $allocationEmployees = collect();
        $allocationOfficers = collect();
        if ($user->isSuperAdmin()) {
            $employeeAllocations = EmployeeHrAllocation::query()->effective()
                ->with(['employee.position', 'user', 'position', 'assignedBy'])
                ->orderByDesc('id')->get();
            $allocationEmployees = Employee::active()->with('position:id,title')->orderBy('id')->get(['id', 'name', 'pay_no', 'position_id'])->sortBy(fn ($employee) => mb_strtolower((string) $employee->name, 'UTF-8'))->values();
            $allocationOfficers = User::active()->havingRole(User::ROLE_SUBJECT_OFFICER)->orderBy('name')->get();
        }

        return view(
            'hr-responsibilities.index',
            compact(
                'assignments',
                'handovers',
                'positions',
                'officers',
                'ongoing',
                'health',
                'manager',
                'unpositioned',
                'effectivePositions',
                'employeeAllocations',
                'allocationEmployees',
                'allocationOfficers',
            ),
        );
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $data = $request->validate([
            'position_id' => ['required', 'integer', 'exists:positions,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'kind' => ['required', 'in:permanent,temporary'],
            'starts_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'ends_on' => [
                'nullable',
                'required_if:kind,temporary',
                'prohibited_if:kind,permanent',
                'date_format:Y-m-d',
                'after_or_equal:starts_on',
            ],
            'notes' => ['required', 'string', 'max:4000'],
        ]);
        HrResponsibilityService::assign($request->user(), $data);

        return back()->with('success', 'HR responsibility recorded. Access follows the effective dates.');
    }

    public function allocateEmployees(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['integer', 'distinct', 'exists:employees,id'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $officer = User::active()->havingRole(User::ROLE_SUBJECT_OFFICER)->findOrFail((int) $data['user_id']);
        $allowedPositions = $officer->effectiveHrPositionIds();
        abort_if($allowedPositions->isEmpty(), 422, 'This Subject Officer has no effective HR positions. Assign the position responsibility first.');

        $employees = Employee::active()->whereIn('id', $data['employee_ids'])->get();
        foreach ($employees as $employee) {
            abort_unless($employee->position_id && $allowedPositions->contains((int) $employee->position_id), 422,
                "{$employee->name} is not in a position assigned to {$officer->name}.");
        }

        \DB::transaction(function () use ($request, $data, $officer, $employees) {
            foreach ($employees as $employee) {
                EmployeeHrAllocation::query()->effective()->where('employee_id', $employee->id)->get()->each(function ($current) use ($request, $officer) {
                    if ((int) $current->user_id === (int) $officer->id) {
                        return;
                    }
                    $current->update([
                        'ended_at' => now(),
                        'ended_by' => $request->user()->id,
                        'end_reason' => 'Reallocated to another Subject Officer.',
                    ]);
                });

                $existing = EmployeeHrAllocation::query()->effective()
                    ->where('employee_id', $employee->id)->where('user_id', $officer->id)->first();
                if ($existing) {
                    $existing->update(['position_id' => $employee->position_id, 'reason' => $data['reason']]);

                    continue;
                }

                EmployeeHrAllocation::create([
                    'employee_id' => $employee->id,
                    'user_id' => $officer->id,
                    'position_id' => $employee->position_id,
                    'starts_on' => today()->toDateString(),
                    'assigned_by' => $request->user()->id,
                    'reason' => $data['reason'],
                ]);
            }
        });

        return back()->with('success', count($data['employee_ids']).' Employee Profile(s) allocated to '.$officer->name.'.');
    }

    public function endEmployeeAllocation(Request $request, EmployeeHrAllocation $allocation): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000']]);
        if (! $allocation->ended_at) {
            $allocation->update([
                'ended_at' => now(),
                'ended_by' => $request->user()->id,
                'end_reason' => $data['reason'],
            ]);
        }

        return back()->with('success', 'Employee Profile allocation ended. History retained.');
    }

    public function end(Request $request, HrResponsibility $responsibility): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000']]);
        HrResponsibilityService::end($request->user(), $responsibility, $data['reason']);

        return back()->with('success', 'Responsibility ended. Its history is retained.');
    }

    public function handover(Request $request, HrResponsibility $responsibility): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $data = $request->validate([
            'to_user_id' => ['required', 'integer', 'exists:users,id'],
            'effective_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'notes' => ['required', 'string', 'max:4000'],
            'outstanding_actions' => ['required', 'string', 'max:6000'],
        ]);
        HrResponsibilityService::requestHandover($request->user(), $responsibility, $data);

        return back()->with(
            'success',
            'Handover sent. The receiving officer must accept before responsibility changes.',
        );
    }

    public function accept(Request $request, HrHandover $handover): RedirectResponse
    {
        HrResponsibilityService::accept($request->user(), $handover);

        return back()->with('success', 'Handover accepted. Responsibility changes on the recorded effective date.');
    }

    public function cancel(Request $request, HrHandover $handover): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000']]);
        HrResponsibilityService::cancel($request->user(), $handover, $data['reason']);

        return back()->with('success', 'Handover cancelled. Responsibility remains with the current officer.');
    }
}
