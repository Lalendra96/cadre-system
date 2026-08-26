<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDisableToggle;
use App\Http\Requests\UnitRequest;
use App\Models\Unit;
use App\Models\UnitType;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    use HandlesDisableToggle;

    public function index(Request $request)
    {
        $showInactive   = $request->boolean('show_inactive');
        $canSeeInactive = $request->user()->isSuperAdmin() || $request->user()->isPlanningOfficer();

        $query = Unit::with('unitType')->orderBy('name');

        if (! $showInactive || ! $canSeeInactive) {
            $query->active();
        }

        if ($q = $request->query('q')) {
            $query->search($q);
        }

        $units     = $query->paginate(25)->withQueryString();
        $unitTypes = UnitType::active()->ordered()->get(['id', 'name']);

        return view('units.index', compact('units', 'unitTypes', 'showInactive', 'canSeeInactive'));
    }

    public function create()
    {
        $unitTypes = UnitType::active()->ordered()->get(['id', 'name']);
        return view('units.form', ['unit' => new Unit(), 'unitTypes' => $unitTypes]);
    }

    public function store(UnitRequest $request): RedirectResponse
    {
        $data = array_merge($request->validated(), ['is_active' => true]);
        $unit = Unit::create($data);
        AuditLogService::created($unit, "Created unit: {$unit->name}");

        return redirect()->route('units.index')
            ->with('success', "Unit \"{$unit->name}\" created.");
    }

    public function edit(Unit $unit)
    {
        $unitTypes = UnitType::active()->ordered()->get(['id', 'name']);
        return view('units.form', compact('unit', 'unitTypes'));
    }

    public function update(UnitRequest $request, Unit $unit): RedirectResponse
    {
        $old = $unit->getOriginal();
        $unit->update($request->validated());
        AuditLogService::updated($unit, $old, "Updated unit: {$unit->name}");

        return redirect()->route('units.index')
            ->with('success', "Unit \"{$unit->name}\" updated.");
    }

    public function toggle(Request $request, Unit $unit): RedirectResponse
    {
        return $this->performToggle(
            request:       $request,
            model:         $unit,
            label:         "Unit \"{$unit->name}\"",
            requireReason: true,
        );
    }
}
