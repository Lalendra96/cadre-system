<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDisableToggle;
use App\Http\Requests\UserCategoryRequest;
use App\Models\UserCategory;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserCategoryController extends Controller
{
    use HandlesDisableToggle;

    public function index(Request $request)
    {
        $showInactive   = $request->boolean('show_inactive');
        $canSeeInactive = $request->user()->isSuperAdmin();

        $query = UserCategory::orderBy('sort_order')->orderBy('name');

        if (! $showInactive || ! $canSeeInactive) {
            $query->active();
        }

        $categories = $query->paginate(30);
        return view('categories.index', compact('categories', 'showInactive', 'canSeeInactive'));
    }

    public function create()
    {
        $nextOrder = (UserCategory::max('sort_order') ?? 0) + 10;
        return view('categories.form', ['category' => new UserCategory(), 'nextOrder' => $nextOrder]);
    }

    public function store(UserCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active']           = $request->boolean('is_active', true);
        $data['can_receive_letters'] = $request->boolean('can_receive_letters');

        $category = UserCategory::create($data);
        AuditLogService::created($category, "Created user category: {$category->name}");

        return redirect()->route('categories.index')
            ->with('success', "Category \"{$category->name}\" created.");
    }

    public function edit(UserCategory $category)
    {
        return view('categories.form', compact('category'));
    }

    public function update(UserCategoryRequest $request, UserCategory $category): RedirectResponse
    {
        $old  = $category->getOriginal();
        $data = $request->validated();
        $data['is_active']           = $request->boolean('is_active');
        $data['can_receive_letters'] = $request->boolean('can_receive_letters');

        $category->update($data);
        AuditLogService::updated($category, $old, "Updated user category: {$category->name}");

        return redirect()->route('categories.index')
            ->with('success', "Category \"{$category->name}\" updated.");
    }

    public function toggle(Request $request, UserCategory $category): RedirectResponse
    {
        if ($category->is_active && $category->users()->exists()) {
            return back()->with(
                'error',
                "Cannot disable \"{$category->name}\" — it has active users assigned. "
                . "Reassign or disable those users first."
            );
        }

        return $this->performToggle(
            request:       $request,
            model:         $category,
            label:         "Category \"{$category->name}\"",
            requireReason: false,
        );
    }
}
