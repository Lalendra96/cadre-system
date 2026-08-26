<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCarderEntryRequest;
use App\Models\ApprovedCarder;
use App\Models\CarderMonthlyEntry;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Subject Officer — submits monthly male/female/transfer/no-pay-leave figures.
 * An officer may be assigned MULTIPLE subject codes (subject_code_user pivot);
 * they only ever see/manage entries for positions reachable through their
 * own assigned codes — never the full system.
 *
 * Access requires can_view_employees = true (same flag as Employee Profiles,
 * since both sections deal with cadre/carder data). The 'feature:employees'
 * middleware on the routes already enforces this; this controller-level check
 * is defence-in-depth in case the controller is ever called directly.
 */
class CarderEntryController extends Controller
{
    private function guardFeatureAccess(Request $request): void
    {
        if ($request->user()?->isSubjectOfficer() && ! $request->user()->can_view_employees) {
            abort(403, 'You do not have access to the carder / employee section. Contact the system administrator.');
        }
    }

    public function index(Request $request)
    {
        $this->guardFeatureAccess($request);

        $user = $request->user();

        // Includes a currently-effective acting Subject Officer assignment,
        // not just permanent ones — see User::effectiveSubjectCodeIds().
        // Fixed alongside the identical bug already found in EmployeeRequest,
        // StoreCarderEntryRequest, and StoreLetterRequest: an acting officer
        // must see their own entries here, not just be allowed to submit new ones.
        $codeIds       = $user->effectiveSubjectCodeIds();
        $assignedCodes = \App\Models\SubjectCode::active()
            ->whereIn('id', $codeIds)
            ->with('positions')
            ->get();

        if ($assignedCodes->isEmpty()) {
            abort(403, 'No subject code has been assigned to your account yet. Contact the system administrator.');
        }

        $year   = (int) $request->query('year', now()->year);
        $status = $request->query('status');

        $query = CarderMonthlyEntry::with(['subjectCode', 'position', 'verifiedBy'])
            ->whereIn('subject_code_id', $assignedCodes->pluck('id'))
            ->where('year', $year)
            ->orderByDesc('year')
            ->orderByDesc('month');

        if ($status) {
            $query->where('status', $status);
        }

        $entries = $query->paginate(20)->withQueryString();

        // ── Quick-glance panel: approved vs actual vs vacancy, per position,
        // for every position under this officer's assigned subject codes.
        //
        // "Actual" is counted from live Employee profile records, NOT
        // CarderMonthlyEntry — matching UnitBreakdownCalculator's approach
        // elsewhere in this system. That assumption turned out to be wrong
        // for how this hospital is actually using the system: reported
        // screenshots showed real, non-zero headcounts recorded via
        // monthly entry submissions (17 male + 9 female for Medical
        // Consultant) while Employee Profile records for that same
        // position+subject-code were empty — Quick Glance was showing 0
        // because it had switched to a source nobody was populating.
        //
        // FIX: hybrid, evaluated PER POSITION (not a single hospital-wide
        // choice) — prefer the Employee Profile count where it's actually
        // populated (>0) for that specific position+subject-code, since
        // it's the more detailed/current source when maintained; fall
        // back to the Monthly Entry figure (with carry-forward) where no
        // profiles exist for that position, so subject codes that only
        // ever submit aggregate monthly numbers still show correct
        // figures instead of a false zero.
        $glanceYear  = now()->year;
        $glanceMonth = now()->month;

        $positionIds = $assignedCodes->flatMap(fn ($sc) => $sc->positions->pluck('id'))->unique()->values();

        $approved = \App\Models\ApprovedCarder::forYear($glanceYear)
            ->whereIn('position_id', $positionIds)
            ->get()
            ->groupBy('position_id')
            ->map(fn ($rows) => (int) $rows->sum('approved_amount'));

        $profileCounts = \App\Models\Employee::active()
            ->whereIn('subject_code_id', $assignedCodes->pluck('id'))
            ->whereIn('position_id', $positionIds)
            ->get()
            ->groupBy('position_id')
            ->map(fn ($rows) => $rows->count());

        $entryCounts = \App\Models\CarderMonthlyEntry::forPeriodWithCarryForward($glanceYear, $glanceMonth)
            ->whereIn('subject_code_id', $assignedCodes->pluck('id'))
            ->whereIn('position_id', $positionIds)
            ->groupBy('position_id')
            ->map(fn ($rows) => $rows->sum('males') + $rows->sum('females') + $rows->sum('no_pay_leave'));

        $actuals = $positionIds->mapWithKeys(function ($posId) use ($profileCounts, $entryCounts) {
            $profileCount = $profileCounts->get($posId, 0);
            $actual = $profileCount > 0 ? $profileCount : $entryCounts->get($posId, 0);
            return [$posId => $actual];
        });

        $glancePositions = \App\Models\Position::whereIn('id', $positionIds)->orderBy('title')->get();
        $quickGlance = $glancePositions->map(function ($pos) use ($approved, $actuals) {
            $approvedAmt = $approved->get($pos->id, 0);
            $actual      = $actuals->get($pos->id, 0);
            $vacancy     = max($approvedAmt - $actual, 0);
            return (object) [
                'title'    => $pos->title,
                'approved' => $approvedAmt,
                'actual'   => $actual,
                'vacancy'  => $vacancy,
                'fillPct'  => $approvedAmt > 0 ? (int) round($actual / $approvedAmt * 100) : 0,
            ];
        })->filter(fn ($r) => $r->approved > 0 || $r->actual > 0)->values();

        return view('carder-entries.index', compact('entries', 'assignedCodes', 'quickGlance', 'year', 'glanceYear', 'glanceMonth'));
    }

    public function create(Request $request)
    {
        $this->guardFeatureAccess($request);

        $user = $request->user();
        $assignedCodes = $user->subjectCodes()->with('positions')->get();

        if ($assignedCodes->isEmpty()) {
            abort(403, 'No subject code has been assigned to your account yet. Contact the system administrator.');
        }

        return view('carder-entries.form', [
            'entry' => new CarderMonthlyEntry(),
            'assignedCodes' => $assignedCodes,
        ]);
    }

    public function store(StoreCarderEntryRequest $request)
    {
        $this->guardFeatureAccess($request);
        $data = $request->validated();

        // Compute in_position = males + females + no_pay_leave (derived, not submitted from form)
        $data['in_position'] = ($data['males'] ?? 0) + ($data['females'] ?? 0) + ($data['no_pay_leave'] ?? 0);

        // Workflow state
        $data['status']       = CarderMonthlyEntry::STATUS_SUBMITTED;
        $data['submitted_by'] = $request->user()->id;
        $data['submitted_at'] = now();
        $data['is_locked']    = false;

        // Snapshot the current approved amount for this position/year for reference
        $data['approved_amount'] = ApprovedCarder::forYear($data['year'])
            ->where('position_id', $data['position_id'])
            ->value('approved_amount') ?? 0;

        try {
            $entry = DB::transaction(fn () => CarderMonthlyEntry::create($data));
        } catch (\Illuminate\Database\QueryException $e) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'month' => 'An entry already exists for this subject code, position, month and year. Edit the existing entry instead.',
            ]);
        }

        AuditLogService::created(
            $entry,
            "Submitted {$data['year']}-{$data['month']} entry for subject code #{$data['subject_code_id']}"
        );

        return redirect()->route('carder-entries.index')
            ->with('success', 'Monthly figures submitted successfully.');
    }

    public function edit(Request $request, CarderMonthlyEntry $carderEntry)
    {
        $this->guardFeatureAccess($request);
        $this->authorizeOwnEntry($request, $carderEntry);

        // Load the subject code with its positions so the form can render
        // the position selector correctly even on the edit path.
        $carderEntry->load('subjectCode.positions', 'position');

        return view('carder-entries.form', [
            'entry'         => $carderEntry,
            'assignedCodes' => collect([$carderEntry->subjectCode]),
        ]);
    }

    public function update(StoreCarderEntryRequest $request, CarderMonthlyEntry $carderEntry)
    {
        $this->guardFeatureAccess($request);
        $this->authorizeOwnEntry($request, $carderEntry);

        $old  = $carderEntry->getOriginal();
        $data = $request->validated();

        // Recompute in_position from the submitted males + females + no_pay_leave
        $data['in_position']  = ($data['males'] ?? 0) + ($data['females'] ?? 0) + ($data['no_pay_leave'] ?? 0);
        $data['submitted_at'] = now();
        $data['status']       = CarderMonthlyEntry::STATUS_SUBMITTED;
        $data['is_locked']    = false;

        $carderEntry->update($data);

        AuditLogService::updated(
            $carderEntry, $old,
            "Updated {$carderEntry->year}-{$carderEntry->month} entry for subject code #{$carderEntry->subject_code_id}"
        );

        return redirect()->route('carder-entries.index')
            ->with('success', 'Monthly figures updated and resubmitted.');
    }

    /** Defence-in-depth: an officer may only ever touch entries for codes assigned to them. */
    private function authorizeOwnEntry(Request $request, CarderMonthlyEntry $carderEntry): void
    {
        if (! $request->user()->hasSubjectCode($carderEntry->subject_code_id)) {
            abort(403, 'You may only manage entries for subject codes assigned to your account.');
        }
    }

    // ── Feature 1: Deadline enforcement ──────────────────────────────────

    /** JSON endpoint — returns deadline status for the requested period. */
    public function deadlineStatus(Request $request)
    {
        $year  = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        return response()->json([
            'locked'   => \App\Models\CarderMonthlyEntry::isDeadlinePassed($year, $month),
            'deadline' => \App\Models\CarderMonthlyEntry::deadlineFor($year, $month)->toDateTimeString(),
        ]);
    }

    // ── Feature 9: Bulk copy from previous month ──────────────────────────

    /** AJAX — returns the previous month's entry as JSON for pre-filling the form. */
    public function copyPrevious(Request $request)
    {
        $year        = (int) $request->query('year', now()->year);
        $month       = (int) $request->query('month', now()->month);
        $subjectCodeId = (int) $request->query('subject_code_id');
        $positionId    = (int) $request->query('position_id');

        $user = $request->user();
        if (! $user->hasEffectiveSubjectCode($subjectCodeId)) {
            return response()->json(['error' => 'Unauthorised subject code.'], 403);
        }

        // Find the most recent prior entry for this code + position
        $prev = \App\Models\CarderMonthlyEntry::where('subject_code_id', $subjectCodeId)
            ->where('position_id', $positionId)
            ->whereRaw('(year * 12 + month) < ?', [$year * 12 + $month])
            ->orderByDesc('year')->orderByDesc('month')
            ->first(['males','females','transferred_in','transferred_out','no_pay_leave','year','month']);

        if (! $prev) {
            return response()->json(['error' => 'No previous entry found for this subject code and position.'], 404);
        }

        return response()->json($prev);
    }

    /** Cancel a submitted/unlocked entry — replaces hard-delete. */
    public function cancel(Request $request, CarderMonthlyEntry $carderEntry)
    {
        if ($carderEntry->status === \App\Models\CarderMonthlyEntry::STATUS_VERIFIED) {
            abort(403, 'Verified entries cannot be cancelled. Request an amendment instead.');
        }

        $request->validate([
            'cancel_reason' => ['required','string','min:10','max:500'],
        ]);

        $carderEntry->cancel($request->user()->id, $request->input('cancel_reason'));

        \App\Services\AuditLogService::updated(
            $carderEntry,
            ['status' => $carderEntry->getOriginal('status')],
            'Entry cancelled: ' . $request->input('cancel_reason')
        );

        return back()->with('success', 'Entry cancelled and locked.');
    }
}
