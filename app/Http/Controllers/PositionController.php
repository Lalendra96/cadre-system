<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDisableToggle;
use App\Http\Requests\PositionRequest;
use App\Models\Position;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    use HandlesDisableToggle;

    public function index(Request $request)
    {
        $showInactive   = $request->boolean('show_inactive');
        $canSeeInactive = $request->user()->isSuperAdmin() || $request->user()->isPlanningOfficer();

        $query = Position::with('subjectCodes')->orderBy('title');

        if (! $showInactive || ! $canSeeInactive) {
            $query->active();
        }

        if ($search = $request->query('q')) {
            $query->search($search);
        }

        $positions = $query->paginate(25)->withQueryString();

        return view('positions.index', compact('positions', 'showInactive', 'canSeeInactive'));
    }

    public function create(): mixed
    {
        return view('positions.form', ['position' => new Position()]);
    }

    public function store(PositionRequest $request): RedirectResponse
    {
        $position = Position::create(array_merge(
            $request->validated(),
            ['is_active' => true]
        ));

        AuditLogService::created($position, "Created position: {$position->title}");

        return redirect()->route('positions.index')
            ->with('success', "Position \"{$position->title}\" created.");
    }

    public function edit(Position $position): mixed
    {
        return view('positions.form', compact('position'));
    }

    public function update(PositionRequest $request, Position $position): RedirectResponse
    {
        $old = $position->getOriginal();
        $position->update($request->validated());
        AuditLogService::updated($position, $old, "Updated position: {$position->title}");

        return redirect()->route('positions.index')
            ->with('success', "Position \"{$position->title}\" updated.");
    }

    public function toggle(Request $request, Position $position): RedirectResponse
    {
        return $this->performToggle(
            request:       $request,
            model:         $position,
            label:         "Position \"{$position->title}\"",
            requireReason: true,
        );
    }
}
