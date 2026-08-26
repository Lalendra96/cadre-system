<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDisableToggle;
use App\Models\Position;
use App\Models\PositionGrade;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Flexible grading-criteria configuration for a Position's grading ladder.
 * `criteria` is stored as free-form JSON — the form accepts repeatable
 * key/value rows so an authorised officer can define whatever criteria a
 * position's grade needs without any code change (e.g. `min_years_in_grade`,
 * which App\Console\Commands\NotifyGradePromotionEligibility acts on
 * automatically — see PositionGrade model docblock).
 *
 * ACCESS: Super Admin / Planning Officer (any position, hospital-wide) OR
 * a Subject Officer configuring grades for a position under their own
 * (effective) subject codes — e.g. a Subject Officer responsible for
 * Medical Officers may configure "Grade II requires 2 years in Grade III"
 * for that position, without needing Super Admin involvement each time.
 *
 * Every method here — including index()/edit(), which previously had NO
 * authorization check at all and would have let ANY authenticated user
 * reach ANY position's grading page the moment route middleware admitted
 * a broader role — now goes through authorizeConfigure(), so viewing and
 * writing are equally protected.
 */
class PositionGradeController extends Controller
{
    use HandlesDisableToggle;

    public function index(Request $request, Position $position)
    {
        $this->authorizeConfigure($request, $position);

        $grades = $position->grades()->ordered()->withCount('gradeRecords')->get();

        return view('position-grades.index', compact('position', 'grades'));
    }

    public function create(Request $request, Position $position)
    {
        $this->authorizeConfigure($request, $position);

        $nextOrder = ((int) $position->grades()->max('sort_order') ?? 0) + 10;

        return view('position-grades.form', [
            'position'     => $position,
            'positionGrade'=> new PositionGrade(),
            'nextOrder'    => $nextOrder,
        ]);
    }

    public function store(Request $request, Position $position): RedirectResponse
    {
        $this->authorizeConfigure($request, $position);

        $data = $this->validateGrade($request, $position);
        $data['position_id'] = $position->id;
        $data['is_active']   = true;

        $grade = PositionGrade::create($data);
        AuditLogService::created($grade, "Created grade \"{$grade->name}\" for position {$position->title}");

        return redirect()->route('position-grades.index', $position)
            ->with('success', "Grade \"{$grade->name}\" added to {$position->title}.");
    }

    public function edit(Request $request, Position $position, PositionGrade $positionGrade)
    {
        $this->authorizeConfigure($request, $position);
        abort_unless($positionGrade->position_id === $position->id, 404);

        return view('position-grades.form', compact('position', 'positionGrade'));
    }

    public function update(Request $request, Position $position, PositionGrade $positionGrade): RedirectResponse
    {
        $this->authorizeConfigure($request, $position);
        abort_unless($positionGrade->position_id === $position->id, 404);

        $data = $this->validateGrade($request, $position, $positionGrade->id);
        $old  = $positionGrade->getOriginal();
        $positionGrade->update($data);

        AuditLogService::updated($positionGrade, $old, "Updated grade \"{$positionGrade->name}\" for {$position->title}");

        return redirect()->route('position-grades.index', $position)
            ->with('success', "Grade \"{$positionGrade->name}\" updated.");
    }

    public function toggle(Request $request, Position $position, PositionGrade $positionGrade): RedirectResponse
    {
        $this->authorizeConfigure($request, $position);
        abort_unless($positionGrade->position_id === $position->id, 404);

        return $this->performToggle(
            request:       $request,
            model:         $positionGrade,
            label:         "Grade \"{$positionGrade->name}\" ({$position->title})",
            requireReason: false,
        );
    }

    /**
     * Defense-in-depth: route middleware admits super_admin, planning_officer,
     * and subject_officer broadly, but every method here re-checks
     * independently — the same double-enforcement pattern used throughout
     * this codebase (see ApprovedCarderController::authorizeWrite() for the
     * precedent).
     *
     * Super Admin / Planning Officer: any position, no further check.
     * Subject Officer: only a position linked to one of their own
     * effective (permanent + acting) subject codes — mirrors exactly how
     * Employee/Transfer/Acting-Appointment access is scoped elsewhere in
     * this system, so a Subject Officer can't configure grading criteria
     * for a position they have no operational connection to.
     */
    private function authorizeConfigure(Request $request, Position $position): void
    {
        $user = $request->user();

        if ($user->isSuperAdmin() || $user->isPlanningOfficer()) {
            return;
        }

        if ($user->isSubjectOfficer()) {
            $linkedPositionIds = \App\Models\SubjectCode::active()
                ->whereIn('id', $user->effectiveSubjectCodeIds())
                ->with('positions:id')
                ->get()
                ->flatMap(fn ($sc) => $sc->positions->pluck('id'));

            abort_unless(
                $linkedPositionIds->contains($position->id),
                403,
                "You may only configure grading criteria for positions under your assigned subject codes. "
                . "\"{$position->title}\" is not linked to any subject code assigned to your account."
            );
            return;
        }

        abort(403, 'Only Super Admin, Planning Officer, or a Subject Officer for this position may configure grading criteria.');
    }

    /**
     * Shared validation for store/update. Criteria arrives from the form
     * as parallel criteria_key[]/criteria_value[] arrays (repeatable rows)
     * and is assembled into the flexible JSON structure here — the model
     * itself does not care what keys are present.
     */
    private function validateGrade(Request $request, Position $position, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'name'                => ['required', 'string', 'max:100',
                                       Rule::unique('position_grades', 'name')
                                           ->where('position_id', $position->id)
                                           ->ignore($ignoreId)],
            'sort_order'          => ['required', 'integer', 'min:0', 'max:9999'],
            'description'         => ['nullable', 'string', 'max:500'],
            'criteria_key'        => ['nullable', 'array'],
            'criteria_key.*'      => ['nullable', 'string', 'max:60'],
            'criteria_value'      => ['nullable', 'array'],
            'criteria_value.*'    => ['nullable', 'string', 'max:200'],
        ]);

        $criteria = [];
        $keys   = $request->input('criteria_key', []);
        $values = $request->input('criteria_value', []);
        foreach ($keys as $i => $key) {
            $key = trim((string) $key);
            if ($key === '') {
                continue;
            }
            $slug  = str_replace(' ', '_', strtolower($key));
            $value = trim((string) ($values[$i] ?? ''));
            // Interpret common boolean phrasing so criteriaSummary() renders cleanly.
            $criteria[$slug] = match (strtolower($value)) {
                'yes', 'true'  => true,
                'no', 'false', '' => false,
                default        => $value,
            };
        }

        return [
            'name'        => $validated['name'],
            'sort_order'  => $validated['sort_order'],
            'description' => $validated['description'] ?? null,
            'criteria'    => $criteria ?: null,
        ];
    }
}
