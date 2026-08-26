<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDisableToggle;
use App\Http\Requests\SubjectCodeRequest;
use App\Models\Position;
use App\Models\SubjectCode;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubjectCodeController extends Controller
{
    use HandlesDisableToggle;

    public function index(Request $request)
    {
        $showInactive   = $request->boolean('show_inactive');
        $canSeeInactive = $request->user()->isSuperAdmin() || $request->user()->isPlanningOfficer();

        $query = SubjectCode::with(['positions', 'users'])->orderBy('code');

        if (! $showInactive || ! $canSeeInactive) {
            $query->active();
        }

        if ($q = $request->query('q')) {
            $query->where(function ($sq) use ($q) {
                $sq->whereRaw('code ILIKE ?', ["%{$q}%"])
                   ->orWhereRaw('name ILIKE ?', ["%{$q}%"]);
            });
        }

        $subjectCodes = $query->paginate(25)->withQueryString();

        return view('subject-codes.index', compact('subjectCodes', 'showInactive', 'canSeeInactive'));
    }

    public function create()
    {
        $positions = Position::active()->orderBy('title')->get(['id', 'title']);
        return view('subject-codes.form', ['subjectCode' => new SubjectCode(), 'positions' => $positions]);
    }

    public function store(SubjectCodeRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = true;

        $subjectCode = SubjectCode::create($data);

        if ($positionIds = $request->input('position_ids', [])) {
            $subjectCode->positions()->sync($positionIds);
        }

        AuditLogService::created($subjectCode, "Created subject code: {$subjectCode->code} — {$subjectCode->name}");

        return redirect()->route('subject-codes.index')
            ->with('success', "Subject code \"{$subjectCode->code}\" created.");
    }

    public function edit(SubjectCode $subjectCode)
    {
        $positions = Position::active()->orderBy('title')->get(['id', 'title']);
        return view('subject-codes.form', compact('subjectCode', 'positions'));
    }

    public function update(SubjectCodeRequest $request, SubjectCode $subjectCode): RedirectResponse
    {
        $old = $subjectCode->getOriginal();
        $subjectCode->update($request->validated());

        if ($request->has('position_ids')) {
            $subjectCode->positions()->sync($request->input('position_ids', []));
        }

        AuditLogService::updated($subjectCode, $old, "Updated subject code: {$subjectCode->code}");

        return redirect()->route('subject-codes.index')
            ->with('success', "Subject code \"{$subjectCode->code}\" updated.");
    }

    public function toggle(Request $request, SubjectCode $subjectCode): RedirectResponse
    {
        return $this->performToggle(
            request:       $request,
            model:         $subjectCode,
            label:         "Subject code \"{$subjectCode->code}\"",
            requireReason: true,
        );
    }
}
