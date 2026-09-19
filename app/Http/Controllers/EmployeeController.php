<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDisableToggle;
use App\Http\Requests\EmployeeRequest;
use App\Models\Employee;
use App\Models\EmployeeHrAllocation;
use App\Models\EmployeeServicePeriod;
use App\Models\Position;
use App\Models\SubjectCode;
use App\Models\Unit;
use App\Services\AuditLogService;
use App\Services\WorkforceScopeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Employee Profiles — individual staff records per subject code.
 *
 * ACCESS:
 *   Super Admin / Planning Officer — full access, all subject codes.
 *   Subject Officer — own subject codes only (double-enforced here + FormRequest).
 *   Admin Group — read-only via reports; not in this controller's middleware group.
 */
class EmployeeController extends Controller
{
    use HandlesDisableToggle;

    // ── Index ─────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $user           = $request->user();
        $showInactive   = $request->boolean('show_inactive');
        $canSeeInactive = $user->isSuperAdmin() || $user->isPlanningOfficer();

        // Employee visibility is allocation-based. A position may be shared by several
        // Subject Officers, but an individual profile is visible only to its allocated officer.
        $query = WorkforceScopeService::employeeQuery($user)
            ->with(['subjectCode', 'position', 'unit'])
            ->orderBy('name');

        if ($user->isSubjectOfficer() && ! $user->isSuperAdmin() && ! $user->isPlanningOfficer()
            && WorkforceScopeService::allocatedEmployeeIds($user)->isEmpty()) {
            session()->flash('warning', 'No Employee Profiles are currently allocated to your account. Contact the administrator.');
        }

        if (! $showInactive || ! $canSeeInactive) {
            $query->active();
        }

        if ($search = trim((string) $request->query('q'))) {
            // Controlled prefix/exact search only: deliberately avoids unrestricted %term% wildcard scans.
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE LOWER(?)', [$search . '%'])
                  ->orWhere('pay_no', $search)
                  ->orWhere('nic_number', $search)
                  ->orWhere('wop_number', $search);
            });
        }

        // Custom filters — hidden-by-default panel, kept separate from the
        // always-visible search box.
        $positionId    = $request->query('position_id');
        $unitId        = $request->query('unit_id');
        $gender        = $request->query('gender');
        $filterSubjectCodeId = $request->query('subject_code_id');

        if ($positionId)         $query->where('position_id', $positionId);
        if ($unitId)              $query->where('unit_id', $unitId);
        if ($gender)               $query->where('gender', $gender);
        if ($filterSubjectCodeId)  $query->where('subject_code_id', $filterSubjectCodeId);

        $employees = $query->paginate(25)->withQueryString();

        // Filter dropdown OPTIONS are scoped to what this user can actually
        // see — a Subject Officer shouldn't be offered the full 58-position/
        // 116-unit hospital-wide catalog when only a handful ever appear
        // among the people they're actually looking at. One query for the
        // visible employee set, all four option lists derived from it,
        // rather than four separate near-identical queries.
        $visibleForFilters = WorkforceScopeService::employeeQuery($user)
            ->with(['position:id,title', 'unit:id,name', 'subjectCode:id,code'])
            ->get(['id', 'position_id', 'unit_id', 'gender', 'subject_code_id']);

        $filterPositions = $visibleForFilters->pluck('position')->filter()->unique('id')->sortBy('title')->values();
        $filterUnits      = $visibleForFilters->pluck('unit')->filter()->unique('id')->sortBy('name')->values();
        $filterSubjectCodes = $visibleForFilters->pluck('subjectCode')->filter()->unique('id')->sortBy('code')->values();
        $filterGenders    = $visibleForFilters->pluck('gender')->filter()->unique()->sort()->values();

        $savedViews = $user->savedEmployeeViews()->orderBy('name')->get();

        return view('employees.index', compact(
            'employees', 'showInactive', 'canSeeInactive',
            'filterPositions', 'positionId',
            'filterUnits', 'unitId',
            'filterGenders', 'gender',
            'filterSubjectCodes', 'filterSubjectCodeId', 'savedViews'
        ));
    }

    // ── Create / Store ────────────────────────────────────────────────────

    public function guidedCreate(Request $request)
    {
        $assignedCodes = $this->assignableCodes($request->user());
        if ($assignedCodes->isEmpty()) {
            return redirect()->route('employees.index')->with('error', 'No HR position/subject responsibility is assigned to your account.');
        }
        return view('employees.guided-create', [
            'employee' => new Employee(),
            'assignedCodes' => $assignedCodes,
            'positions' => $this->assignablePositions($request->user()),
            'units' => Unit::active()->with('unitType')->orderBy('name')->get(['id','name','code','unit_type_id']),
            'salaryScales' => \App\Models\SalaryScale::active()->orderBy('code')->get(['id','code','name']),
        ]);
    }

    public function create(Request $request)
    {
        $assignedCodes = $this->assignableCodes($request->user());

        if ($assignedCodes->isEmpty()) {
            return redirect()->route('employees.index')
                ->with('error', 'No subject code is assigned to your account. Contact the system administrator.');
        }

        $positions = $this->assignablePositions($request->user());
        $units = Unit::active()->with('unitType')->orderBy('name')->get(['id', 'name', 'code', 'unit_type_id']);
        $salaryScales = \App\Models\SalaryScale::active()->orderBy('code')->get(['id', 'code', 'name']);

        return view('employees.form', [
            'employee'      => new Employee(),
            'assignedCodes' => $assignedCodes,
            'positions'     => $positions,
            'units'         => $units,
            'salaryScales'  => $salaryScales,
        ]);
    }

    public function store(EmployeeRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active']   = true;
        $data['created_by']  = $request->user()->id;
        $data['updated_by']  = $request->user()->id;

        $employee = Employee::create($data);

        if ($employee->date_reported_for_duty || $employee->date_joined_institution) {
            EmployeeServicePeriod::create([
                'employee_id' => $employee->id,
                'period_kind' => 'posting',
                'sector' => 'central_government',
                'service_name' => $employee->current_service_name ?: $employee->combined_service_name,
                'institution_name' => 'Teaching Hospital Peradeniya',
                'ministry_department' => 'Ministry of Health',
                'position_id' => $employee->position_id,
                'start_date' => ($employee->date_reported_for_duty ?? $employee->date_joined_institution)->toDateString(),
                'movement_type' => 'appointment',
                'verification_status' => 'document_pending',
                'is_current' => true,
                'is_active' => true,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);
        }

        if ($request->user()->isSubjectOfficer() && ! $request->user()->isSuperAdmin() && ! $request->user()->isPlanningOfficer()) {
            EmployeeHrAllocation::create([
                'employee_id' => $employee->id,
                'user_id' => $request->user()->id,
                'position_id' => $employee->position_id,
                'starts_on' => today()->toDateString(),
                'assigned_by' => $request->user()->id,
                'reason' => 'Automatically allocated to the Subject Officer who created the profile.',
            ]);
        }

        AuditLogService::created(
            $employee,
            "Created employee profile: {$employee->display_name} "
            . "(Pay No: {$employee->pay_no}) "
            . "— {$employee->position?->title} / {$employee->subjectCode?->code}"
        );

        return redirect()->route('employees.index')
            ->with('success', "Profile for {$employee->display_name} created successfully.");
    }

    // ── Edit / Update ─────────────────────────────────────────────────────

    public function edit(Request $request, Employee $employee)
    {
        $this->authorizeOwnEmployee($request->user(), $employee);

        $assignedCodes = $this->assignableCodes($request->user());
        $positions     = $this->assignablePositions($request->user());
        $units         = Unit::active()->with("unitType")->orderBy("name")->get(["id","name","code","unit_type_id"]);
        $salaryScales  = \App\Models\SalaryScale::active()->orderBy('code')->get(['id', 'code', 'name']);

        return view('employees.form', compact('employee', 'assignedCodes', 'positions', 'units', 'salaryScales'));
    }

    public function update(EmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $this->authorizeOwnEmployee($request->user(), $employee);

        $old  = $employee->getOriginal();
        $data = $request->validated();
        $data['updated_by'] = $request->user()->id;

        $employee->update($data);

        if ($employee->date_reported_for_duty || $employee->date_joined_institution) {
            $period = EmployeeServicePeriod::firstOrNew(['employee_id'=>$employee->id,'is_current'=>true]);
            $period->fill([
                'period_kind'=>'posting','sector'=>'central_government',
                'service_name'=>$employee->current_service_name ?: $employee->combined_service_name,
                'institution_name'=>'Teaching Hospital Peradeniya','ministry_department'=>'Ministry of Health',
                'position_id'=>$employee->position_id,
                'start_date'=>($employee->date_reported_for_duty ?? $employee->date_joined_institution)->toDateString(),
                'verification_status'=>$period->verification_status ?: 'document_pending','is_active'=>true,
                'updated_by'=>$request->user()->id,
            ]);
            if (!$period->exists) $period->created_by=$request->user()->id;
            $period->save();
        }

        if ($request->user()->isSubjectOfficer() && ! $request->user()->isSuperAdmin() && ! $request->user()->isPlanningOfficer()) {
            EmployeeHrAllocation::query()->effective()
                ->where('employee_id', $employee->id)
                ->where('user_id', $request->user()->id)
                ->update(['position_id' => $employee->position_id, 'updated_at' => now()]);
        }

        AuditLogService::updated(
            $employee,
            $old,
            "Updated employee profile: {$employee->display_name} (Pay No: {$employee->pay_no})"
        );

        return redirect()->route('employees.index')
            ->with('success', "Profile for {$employee->display_name} updated.");
    }

    // ── Toggle (disable / re-enable) ──────────────────────────────────────

    public function toggle(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorizeOwnEmployee($request->user(), $employee);

        return $this->performToggle(
            request:       $request,
            model:         $employee,
            label:         "Employee profile for \"{$employee->display_name}\"",
            requireReason: true,
        );
    }

    // ── Private helpers ───────────────────────────────────────────────────

    /**
     * Ensure the user may modify this specific employee record.
     * Super Admin and Planning Officer have unrestricted access.
     * Subject Officers may only access employees under their own subject codes.
     */
    private function authorizeOwnEmployee(\App\Models\User $user, Employee $employee): void
    {
        WorkforceScopeService::authorizeEmployee($user, $employee);
    }

    /**
     * Subject codes the user may assign employees to.
     * Super Admin / Planning Officer → all active codes.
     * Subject Officer → their own codes plus any currently-effective
     * acting Subject Officer assignment.
     */
    private function assignableCodes(\App\Models\User $user)
    {
        if ($user->isSuperAdmin() || $user->isPlanningOfficer()) {
            return SubjectCode::active()
                ->with('positions:id,title')
                ->orderBy('code')
                ->get();
        }

        return SubjectCode::active()
            ->whereIn('id', $user->effectiveHrSubjectCodeIds())
            ->with('positions:id,title')
            ->orderBy('code')
            ->get();
    }

    /** Positions this user may manage as HR owner. */
    private function assignablePositions(\App\Models\User $user)
    {
        if ($user->isSuperAdmin() || $user->isPlanningOfficer()) {
            return Position::active()->orderBy('title')->get(['id','title']);
        }

        return Position::active()
            ->whereIn('id', $user->effectiveHrPositionIds())
            ->orderBy('title')
            ->get(['id','title']);
    }
}
