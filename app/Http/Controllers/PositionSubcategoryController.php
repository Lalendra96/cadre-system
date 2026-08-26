<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDisableToggle;
use App\Models\Position;
use App\Models\PositionSubcategory;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Super-Admin-only configuration of Position Subcategories — e.g.
 * "Medical Consultant" (parent Position) can have subcategories like
 * "Health Information Consultant", "Emergency Physician", "PGIM Trainee".
 *
 * counts_toward_parent_total is the field that decides whether a
 * subcategory's headcount rolls into its parent Position's total on the
 * Unit-wise Breakdown — see PositionSubcategory model docblock and
 * UnitBreakdownCalculator for how that's applied.
 */
class PositionSubcategoryController extends Controller
{
    use HandlesDisableToggle;

    public function index(Request $request)
    {
        $this->authorizeSuperAdmin($request);

        $positions = Position::active()
            ->with(['subcategories' => fn ($q) => $q->orderBy('sort_order')])
            ->orderBy('title')
            ->get();

        return view('position-subcategories.index', compact('positions'));
    }

    public function create(Request $request, Position $position)
    {
        $this->authorizeSuperAdmin($request);

        return view('position-subcategories.form', [
            'position'    => $position,
            'subcategory' => new PositionSubcategory(),
        ]);
    }

    public function store(Request $request, Position $position): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $data = $this->validateSubcategory($request, $position);
        $data['parent_position_id'] = $position->id;
        $data['created_by']         = $request->user()->id;
        $data['is_active']          = true;

        $subcategory = PositionSubcategory::create($data);

        AuditLogService::created(
            $subcategory,
            "Created subcategory \"{$subcategory->name}\" under {$position->title}"
            . ($subcategory->counts_toward_parent_total ? '' : ' (excluded from parent total)')
        );

        return redirect()->route('position-subcategories.index')
            ->with('success', "Subcategory \"{$subcategory->name}\" added under {$position->title}.");
    }

    public function edit(Request $request, Position $position, PositionSubcategory $subcategory)
    {
        $this->authorizeSuperAdmin($request);
        abort_unless($subcategory->parent_position_id === $position->id, 404);

        return view('position-subcategories.form', compact('position', 'subcategory'));
    }

    public function update(Request $request, Position $position, PositionSubcategory $subcategory): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);
        abort_unless($subcategory->parent_position_id === $position->id, 404);

        $data = $this->validateSubcategory($request, $position, $subcategory->id);
        $old  = $subcategory->getOriginal();
        $subcategory->update($data);

        AuditLogService::updated($subcategory, $old, "Updated subcategory \"{$subcategory->name}\" under {$position->title}");

        return redirect()->route('position-subcategories.index')
            ->with('success', "Subcategory \"{$subcategory->name}\" updated.");
    }

    public function toggle(Request $request, Position $position, PositionSubcategory $subcategory): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);
        abort_unless($subcategory->parent_position_id === $position->id, 404);

        return $this->performToggle(
            request:       $request,
            model:         $subcategory,
            label:         "Subcategory \"{$subcategory->name}\" ({$position->title})",
            requireReason: false,
        );
    }

    private function validateSubcategory(Request $request, Position $position, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'name'                        => ['required', 'string', 'max:150',
                                                Rule::unique('position_subcategories', 'name')
                                                    ->where('parent_position_id', $position->id)
                                                    ->ignore($ignoreId)],
            'code'                        => ['nullable', 'string', 'max:30'],
            'description'                 => ['nullable', 'string', 'max:300'],
            'counts_toward_parent_total'  => ['boolean'],
            'sort_order'                  => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $validated['counts_toward_parent_total'] = $request->boolean('counts_toward_parent_total');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        return $validated;
    }

    private function authorizeSuperAdmin(Request $request): void
    {
        abort_unless($request->user()->isSuperAdmin(), 403, 'Only Super Admin may configure position subcategories.');
    }
}
