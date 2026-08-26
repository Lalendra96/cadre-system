<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDisableToggle;
use App\Models\Unit;
use App\Models\UnitType;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UnitTypeController extends Controller
{
    use HandlesDisableToggle;

    public function index(Request $request)
    {
        $showInactive   = $request->boolean('show_inactive');
        $canSeeInactive = $request->user()->isSuperAdmin() || $request->user()->isPlanningOfficer();

        $unitTypes = UnitType::withCount('units');

        if (! $showInactive || ! $canSeeInactive) {
            $unitTypes->active();
        }

        if ($q = $request->query('q')) {
            $unitTypes->where(function ($query) use ($q) {
                $query->whereRaw('name ILIKE ?', ["%{$q}%"])
                      ->orWhereRaw('code ILIKE ?', ["%{$q}%"]);
            });
        }

        $unitTypes = $unitTypes->orderBy('sort_order')->orderBy('name')->paginate(20)->withQueryString();

        return view('unit-types.index', compact('unitTypes', 'showInactive', 'canSeeInactive'));
    }

    public function create()
    {
        $nextOrder = (UnitType::max('sort_order') ?? 0) + 10;
        return view('unit-types.form', ['unitType' => new UnitType(), 'nextOrder' => $nextOrder]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:80', Rule::unique('unit_types', 'name')],
            'code'        => ['nullable', 'string', 'max:20', Rule::unique('unit_types', 'code')],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order'  => ['integer', 'min:0', 'max:9999'],
            'is_active'   => ['boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $unitType = UnitType::create($data);
        AuditLogService::created($unitType, "Created unit type: {$unitType->name}");

        return redirect()->route('unit-types.index')
            ->with('success', "Unit type \"{$unitType->name}\" created.");
    }

    public function edit(UnitType $unitType)
    {
        $unitType->load('units');
        $unassignedUnits = Unit::active()
            ->where(fn ($q) => $q->whereNull('unit_type_id')->orWhere('unit_type_id', '!=', $unitType->id))
            ->orderBy('name')
            ->get();

        return view('unit-types.form', compact('unitType', 'unassignedUnits'));
    }

    public function update(Request $request, UnitType $unitType): RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:80', Rule::unique('unit_types', 'name')->ignore($unitType->id)],
            'code'        => ['nullable', 'string', 'max:20', Rule::unique('unit_types', 'code')->ignore($unitType->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order'  => ['integer', 'min:0', 'max:9999'],
            'is_active'   => ['boolean'],
            'unit_ids'    => ['nullable', 'array'],
            'unit_ids.*'  => ['integer', 'exists:units,id'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $unitIds = (array) ($data['unit_ids'] ?? []);
        unset($data['unit_ids']);

        $old = $unitType->getOriginal();
        $unitType->update($data);

        Unit::where('unit_type_id', $unitType->id)->update(['unit_type_id' => null]);
        if (! empty($unitIds)) {
            Unit::whereIn('id', $unitIds)->update(['unit_type_id' => $unitType->id]);
        }

        AuditLogService::updated($unitType, $old, "Updated unit type: {$unitType->name}");

        return redirect()->route('unit-types.index')
            ->with('success', "Unit type \"{$unitType->name}\" updated.");
    }

    public function toggle(Request $request, UnitType $unitType): RedirectResponse
    {
        return $this->performToggle(
            request:       $request,
            model:         $unitType,
            label:         "Unit type \"{$unitType->name}\"",
            requireReason: false,
        );
    }
}
