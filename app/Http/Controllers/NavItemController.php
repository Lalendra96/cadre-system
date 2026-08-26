<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\NavItem;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Super-Admin-only management of the navigation bar — see nav_items
 * migration docblock for the three-layer visibility model this configures.
 */
class NavItemController extends Controller
{
    private const ROLE_OPTIONS = ['super_admin', 'admin_group', 'planning_officer', 'subject_officer'];

    public function index(Request $request)
    {
        $this->authorizeSuperAdmin($request);

        $items = NavItem::orderBy('section')->orderBy('sort_order')->get()->groupBy(fn ($i) => $i->section ?? '(No section)');

        return view('nav-items.index', compact('items'));
    }

    public function create(Request $request)
    {
        $this->authorizeSuperAdmin($request);

        return view('nav-items.form', [
            'item'        => new NavItem(),
            'roleOptions' => self::ROLE_OPTIONS,
            'checkLabels' => NavItem::customCheckLabels(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $data = $this->validateItem($request);
        $data['created_by'] = $request->user()->id;
        $data['is_active']  = true;

        $item = NavItem::create($data);

        AuditLogService::created($item, "Created nav item \"{$item->label}\"");

        return redirect()->route('nav-items.index')->with('success', "Nav item \"{$item->label}\" created.");
    }

    public function edit(Request $request, NavItem $navItem)
    {
        $this->authorizeSuperAdmin($request);

        return view('nav-items.form', [
            'item'        => $navItem,
            'roleOptions' => self::ROLE_OPTIONS,
            'checkLabels' => NavItem::customCheckLabels(),
        ]);
    }

    public function update(Request $request, NavItem $navItem): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $data = $this->validateItem($request);
        $old  = $navItem->getOriginal();
        $navItem->update($data);

        AuditLogService::updated($navItem, $old, "Updated nav item \"{$navItem->label}\"");

        return redirect()->route('nav-items.index')->with('success', "Nav item \"{$navItem->label}\" updated.");
    }

    public function toggle(Request $request, NavItem $navItem): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $navItem->update(['is_active' => ! $navItem->is_active]);
        AuditLogService::updated($navItem, [], ($navItem->is_active ? 'Enabled' : 'Disabled') . " nav item \"{$navItem->label}\"");

        return redirect()->route('nav-items.index')
            ->with('success', "\"{$navItem->label}\" " . ($navItem->is_active ? 'enabled' : 'disabled') . '.');
    }

    /**
     * Preview — shows exactly what a given role would see, using the SAME
     * isVisibleTo() the real nav uses, so this can never drift from actual
     * behaviour. Super Admin picks a role to impersonate for preview
     * purposes only — this does not change who they actually are.
     */
    public function preview(Request $request)
    {
        $this->authorizeSuperAdmin($request);

        $role = $request->query('role', 'subject_officer');
        abort_unless(in_array($role, self::ROLE_OPTIONS, true), 404);

        // A lightweight stand-in user with just enough shape for
        // isVisibleTo() to evaluate role and boolean-flag checks. Custom
        // checks that need real account state (e.g. canVerifyEntries(),
        // which reads a SystemSetting) are evaluated using a fresh,
        // unsaved User instance of the chosen role — see the note in the
        // view about checks that may read as "no" here even though a real
        // account of that role could pass them once configured.
        $previewUser = new User(['can_view_employees' => true, 'can_view_letters' => true]);
        $previewUser->setRelation('userRoles', collect([(object) ['role' => $role]]));

        $items = NavItem::active()->orderBy('section')->orderBy('sort_order')->get()
            ->filter(fn ($i) => $i->isVisibleTo($previewUser))
            ->groupBy(fn ($i) => $i->section ?? '(No section)');

        return view('nav-items.preview', compact('items', 'role'));
    }

    private function validateItem(Request $request): array
    {
        $validated = $request->validate([
            'section'          => ['nullable', 'string', 'max:100'],
            'label'            => ['required', 'string', 'max:150'],
            'route_name'       => ['required', 'string', 'max:150'],
            'open_in_new_tab'  => ['boolean'],
            'allowed_roles'    => ['nullable', 'array'],
            'allowed_roles.*'  => [Rule::in(self::ROLE_OPTIONS)],
            'custom_checks'    => ['nullable', 'array'],
            'custom_checks.*'  => [Rule::in(array_keys(NavItem::customChecks()))],
            'sort_order'       => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $validated['open_in_new_tab'] = $request->boolean('open_in_new_tab');
        $validated['sort_order']      = $validated['sort_order'] ?? 0;
        $validated['allowed_roles']   = $validated['allowed_roles'] ?? null;
        $validated['custom_checks']   = $validated['custom_checks'] ?? null;

        return $validated;
    }

    private function authorizeSuperAdmin(Request $request): void
    {
        abort_unless($request->user()->isSuperAdmin(), 403, 'Only Super Admin may configure navigation.');
    }
}
