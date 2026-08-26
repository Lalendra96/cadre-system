<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Position;
use App\Models\Unit;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Super-Admin-only management of which Positions are bound (relevant/
 * expected) to a given Unit — see unit_position migration docblock for
 * why this is a scoping aid, not a hard filter on existing data.
 */
class UnitPositionBindingController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeSuperAdmin($request);

        $query = Unit::active()->with('unitType')->withCount('boundPositions')->orderBy('name');

        if ($q = $request->query('q')) {
            $query->search($q);
        }

        $units = $query->paginate(30)->withQueryString();

        return view('unit-position-bindings.index', compact('units'));
    }

    public function edit(Request $request, Unit $unit)
    {
        $this->authorizeSuperAdmin($request);

        $positions = Position::active()->orderBy('title')->get(['id', 'title']);
        $boundIds  = $unit->boundPositions()->pluck('positions.id')->all();

        return view('unit-position-bindings.edit', compact('unit', 'positions', 'boundIds'));
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $data = $request->validate([
            'position_ids'   => ['nullable', 'array'],
            'position_ids.*' => ['integer', 'exists:positions,id'],
        ]);

        $positionIds = $data['position_ids'] ?? [];

        $unit->boundPositions()->sync(
            collect($positionIds)->mapWithKeys(fn ($id) => [$id => ['created_by' => $request->user()->id]])
        );

        AuditLogService::updated(
            $unit, [],
            "Set bound positions for \"{$unit->name}\": " . count($positionIds) . ' position(s)'
        );

        return redirect()->route('unit-position-bindings.index')
            ->with('success', "Bound positions for {$unit->name} updated (" . count($positionIds) . ' position(s)).');
    }

    private function authorizeSuperAdmin(Request $request): void
    {
        abort_unless($request->user()->isSuperAdmin(), 403, 'Only Super Admin may manage unit-position bindings.');
    }
}
