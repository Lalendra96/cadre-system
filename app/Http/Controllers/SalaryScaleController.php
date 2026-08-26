<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDisableToggle;
use App\Models\SalaryScale;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Salary Scale master list — Super Admin / Planning Officer managed.
 * Referenced by Employee::salary_scale_id.
 */
class SalaryScaleController extends Controller
{
    use HandlesDisableToggle;

    public function index(Request $request)
    {
        $showInactive   = $request->boolean('show_inactive');
        $canSeeInactive = $request->user()->isSuperAdmin() || $request->user()->isPlanningOfficer();

        $query = SalaryScale::withCount('employees')->orderBy('code');
        if (! $showInactive || ! $canSeeInactive) {
            $query->active();
        }
        if ($q = $request->query('q')) {
            $query->search($q);
        }

        $salaryScales = $query->paginate(25)->withQueryString();

        return view('salary-scales.index', compact('salaryScales', 'showInactive', 'canSeeInactive'));
    }

    public function create()
    {
        return view('salary-scales.form', ['salaryScale' => new SalaryScale()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeConfigure($request);

        $data = $request->validate([
            'code'              => ['required', 'string', 'max:30', Rule::unique('salary_scales', 'code')],
            'name'              => ['required', 'string', 'max:150'],
            'min_salary'        => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'max_salary'        => ['nullable', 'numeric', 'min:0', 'max:9999999.99', 'gte:min_salary'],
            'increment_amount'  => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'notes'             => ['nullable', 'string', 'max:500'],
        ]);
        $data['is_active'] = true;

        $scale = SalaryScale::create($data);
        AuditLogService::created($scale, "Created salary scale: {$scale->code} — {$scale->name}");

        return redirect()->route('salary-scales.index')->with('success', "Salary scale \"{$scale->code}\" created.");
    }

    public function edit(SalaryScale $salaryScale)
    {
        return view('salary-scales.form', compact('salaryScale'));
    }

    public function update(Request $request, SalaryScale $salaryScale): RedirectResponse
    {
        $this->authorizeConfigure($request);

        $data = $request->validate([
            'code'              => ['required', 'string', 'max:30', Rule::unique('salary_scales', 'code')->ignore($salaryScale->id)],
            'name'              => ['required', 'string', 'max:150'],
            'min_salary'        => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'max_salary'        => ['nullable', 'numeric', 'min:0', 'max:9999999.99', 'gte:min_salary'],
            'increment_amount'  => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'notes'             => ['nullable', 'string', 'max:500'],
        ]);

        $old = $salaryScale->getOriginal();
        $salaryScale->update($data);
        AuditLogService::updated($salaryScale, $old, "Updated salary scale: {$salaryScale->code}");

        return redirect()->route('salary-scales.index')->with('success', "Salary scale \"{$salaryScale->code}\" updated.");
    }

    public function toggle(Request $request, SalaryScale $salaryScale): RedirectResponse
    {
        $this->authorizeConfigure($request);

        return $this->performToggle(
            request:       $request,
            model:         $salaryScale,
            label:         "Salary scale \"{$salaryScale->code}\"",
            requireReason: false,
        );
    }

    /**
     * Defense-in-depth: route middleware already restricts this whole
     * controller to super_admin/planning_officer, but every mutating
     * method here re-checks independently — the same double-enforcement
     * pattern used throughout this codebase (see
     * ApprovedCarderController::authorizeWrite() for the precedent).
     */
    private function authorizeConfigure(Request $request): void
    {
        abort_unless(
            $request->user()->isSuperAdmin() || $request->user()->isPlanningOfficer(),
            403,
            'Only Super Admin or Planning Officer may manage salary scales.'
        );
    }
}
