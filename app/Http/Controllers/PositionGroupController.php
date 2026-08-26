<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDisableToggle;
use App\Models\Position;
use App\Models\PositionGroup;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Position Groups — named collections of positions used as the
 * send-target when dispatching a Circular. Created and managed by
 * Subject Officers themselves (not Super Admin), gated by the
 * can_manage_circular_groups permission flag — see
 * User::canManageCircularGroups() and EnsureFeatureAccess middleware
 * ('circular-groups' feature key).
 *
 * OWNERSHIP MODEL: a Subject Officer only ever sees/edits/toggles the
 * groups THEY created (scoped by created_by) — groups are not shared
 * between officers by default, matching how every other per-officer
 * record in this system works. Super Admin sees and can manage every
 * group hospital-wide, for oversight, but is not the primary creator.
 */
class PositionGroupController extends Controller
{
    use HandlesDisableToggle;

    public function index(Request $request)
    {
        $this->authorizeManage($request);

        $query = PositionGroup::withCount('positions')->with('creator')->orderBy('name');
        if (! $request->user()->isSuperAdmin()) {
            $query->where('created_by', $request->user()->id);
        }

        $groups = $query->paginate(20);

        return view('position-groups.index', compact('groups'));
    }

    public function create(Request $request)
    {
        $this->authorizeManage($request);

        $positions = $this->assignablePositions($request->user());

        return view('position-groups.form', [
            'group'     => new PositionGroup(),
            'positions' => $positions,
            'selectedPositionIds' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);

        $data = $this->validateGroup($request, $request->user());

        $group = PositionGroup::create([
            'name'        => $data['name'],
            'description' => $data['description'],
            'created_by'  => $request->user()->id,
            'is_active'   => true,
        ]);
        $group->positions()->sync($data['position_ids']);

        AuditLogService::created($group, "Created position group \"{$group->name}\" with " . count($data['position_ids']) . ' position(s)');

        return redirect()->route('position-groups.index')->with('success', "Group \"{$group->name}\" created.");
    }

    public function edit(Request $request, PositionGroup $positionGroup)
    {
        $this->authorizeManage($request);
        $this->authorizeOwnGroup($request, $positionGroup);

        $positions = $this->assignablePositions($request->user());
        $selectedPositionIds = $positionGroup->positions()->pluck('positions.id')->all();

        return view('position-groups.form', [
            'group'     => $positionGroup,
            'positions' => $positions,
            'selectedPositionIds' => $selectedPositionIds,
        ]);
    }

    public function update(Request $request, PositionGroup $positionGroup): RedirectResponse
    {
        $this->authorizeManage($request);
        $this->authorizeOwnGroup($request, $positionGroup);

        $data = $this->validateGroup($request, $request->user(), $positionGroup->id);
        $old  = $positionGroup->getOriginal();

        $positionGroup->update([
            'name'        => $data['name'],
            'description' => $data['description'],
        ]);
        $positionGroup->positions()->sync($data['position_ids']);

        AuditLogService::updated($positionGroup, $old, "Updated position group \"{$positionGroup->name}\"");

        return redirect()->route('position-groups.index')->with('success', "Group \"{$positionGroup->name}\" updated.");
    }

    public function toggle(Request $request, PositionGroup $positionGroup): RedirectResponse
    {
        $this->authorizeManage($request);
        $this->authorizeOwnGroup($request, $positionGroup);

        return $this->performToggle(
            request:       $request,
            model:         $positionGroup,
            label:         "Position group \"{$positionGroup->name}\"",
            requireReason: false,
        );
    }

    private function validateGroup(Request $request, \App\Models\User $user, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:150', Rule::unique('position_groups', 'name')->ignore($ignoreId)],
            'description'   => ['nullable', 'string', 'max:500'],
            'position_ids'   => ['required', 'array', 'min:1'],
            'position_ids.*' => ['integer', 'exists:positions,id'],
        ], [
            'position_ids.required' => 'Select at least one position for this group.',
        ]);

        // A Subject Officer may only build a group out of positions under
        // their OWN effective subject codes — never a hospital-wide
        // position list. Super Admin/elevated roles are unrestricted.
        if (! $user->isSuperAdmin() && ! $user->isAdminGroup() && ! $user->isPlanningOfficer()) {
            $allowedIds = $this->assignablePositions($user)->pluck('id')->all();
            $invalid = array_diff($validated['position_ids'], $allowedIds);
            abort_if(! empty($invalid), 403, 'You may only include positions under your own assigned subject codes in a group.');
        }

        return [
            'name'         => $validated['name'],
            'description'  => $validated['description'] ?? null,
            'position_ids' => $validated['position_ids'],
        ];
    }

    /**
     * Positions the officer may draw from when building a group.
     * Elevated roles: every active position. Subject Officer: only
     * positions linked to their own effective subject codes.
     */
    private function assignablePositions(\App\Models\User $user)
    {
        if ($user->isSuperAdmin() || $user->isAdminGroup() || $user->isPlanningOfficer()) {
            return Position::active()->orderBy('title')->get(['id', 'title']);
        }

        $positionIds = \App\Models\SubjectCode::active()
            ->whereIn('id', $user->effectiveSubjectCodeIds())
            ->with('positions:id')
            ->get()
            ->flatMap(fn ($sc) => $sc->positions->pluck('id'))
            ->unique();

        return Position::active()->whereIn('id', $positionIds)->orderBy('title')->get(['id', 'title']);
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless(
            $request->user()->canManageCircularGroups(),
            403,
            'You do not have permission to manage position groups. Contact the system administrator to enable it for your account.'
        );
    }

    private function authorizeOwnGroup(Request $request, PositionGroup $positionGroup): void
    {
        abort_unless(
            $request->user()->isSuperAdmin() || $positionGroup->created_by === $request->user()->id,
            403,
            'You may only manage position groups you created yourself.'
        );
    }
}
