<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDisableToggle;
use App\Http\Requests\EmployeeRequest;
use App\Models\Employee;
use App\Models\Position;
use App\Models\SubjectCode;
use App\Models\Unit;
use App\Services\AuditLogService;
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

        $query = Employee::with(['subjectCode', 'position', 'unit'])
            ->orderBy('name');

        // Scope Subject Officers to their own assigned codes (permanent + acting)
        if ($user->isSubjectOfficer() && ! $user->isSuperAdmin() && ! $user->isPlanningOfficer()) {
            $assignedIds = $user->effectiveSubjectCodeIds();

            if ($assignedIds->isEmpty()) {
                return view('employees.index', [
                    'employees'           => collect(),
                    'showInactive'        => $showInactive,
                    'canSeeInactive'      => $canSeeInactive,
                    'filterPositions'     => collect(),
                    'positionId'          => $request->query('position_id'),
                    'filterUnits'         => collect(),
                    'unitId'              => $request->query('unit_id'),
                    'filterGenders'       => collect(),
                    'gender'              => $request->query('gender'),
                    'filterSubjectCodes'  => collect(),
                    'filterSubjectCodeId' => $request->query('subject_code_id'),
                ])->with('warning', 'No subject code is assigned to your account. Contact the administrator.');
            }

            $query->whereIn('subject_code_id', $assignedIds);
        }

        if (! $showInactive || ! $canSeeInactive) {
            $query->active();
        }

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('name ILIKE ?', ["%{$search}%"])
                  ->orWhereRaw('pay_no ILIKE ?', ["%{$search}%"]);
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
        $visibleForFilters = Employee::query()
            ->when($user->isSubjectOfficer() && ! $user->isSuperAdmin() && ! $user->isPlanningOfficer(), function ($q) use ($user) {
                $q->whereIn('subject_code_id', $user->effectiveSubjectCodeIds());
            })
            ->with(['position:id,title', 'unit:id,name', 'subjectCode:id,code'])
            ->get(['id', 'position_id', 'unit_id', 'gender', 'subject_code_id']);

        $filterPositions = $visibleForFilters->pluck('position')->filter()->unique('id')->sortBy('title')->values();
        $filterUnits      = $visibleForFilters->pluck('unit')->filter()->unique('id')->sortBy('name')->values();
        $filterSubjectCodes = $visibleForFilters->pluck('subjectCode')->filter()->unique('id')->sortBy('code')->values();
        $filterGenders    = $visibleForFilters->pluck('gender')->filter()->unique()->sort()->values();

        return view('employees.index', compact(
            'employees', 'showInactive', 'canSeeInactive',
            'filterPositions', 'positionId',
            'filterUnits', 'unitId',
            'filterGenders', 'gender',
            'filterSubjectCodes', 'filterSubjectCodeId'
        ));
    }

    // ── Create / Store ────────────────────────────────────────────────────

    public function create(Request $request)
    {
        $assignedCodes = $this->assignableCodes($request->user());

        if ($assignedCodes->isEmpty()) {
            return redirect()->route('employees.index')
                ->with('error', 'No subject code is assigned to your account. Contact the system administrator.');
        }

        $positions = Position::active()->orderBy('title')->get(['id', 'title']);
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
        $positions     = Position::active()->orderBy('title')->get(['id', 'title']);
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
        if ($user->isSuperAdmin() || $user->isPlanningOfficer()) {
            return;
        }

        if (! $user->isSubjectOfficer()) {
            abort(403, 'You do not have permission to modify employee profiles.');
        }

        // Includes both permanent assignments and any currently-effective
        // acting Subject Officer grant (see User::effectiveSubjectCodeIds()).
        if (! $user->hasEffectiveSubjectCode($employee->subject_code_id)) {
            abort(403,
                'You may only edit employee profiles under subject codes assigned to your account. '
                . 'This employee belongs to a different subject code.'
            );
        }
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
            ->whereIn('id', $user->effectiveSubjectCodeIds())
            ->with('positions:id,title')
            ->orderBy('code')
            ->get();
    }
}
