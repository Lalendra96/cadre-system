<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreApprovedCarderRequest;
use App\Models\ApprovedCarder;
use App\Models\Position;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Approved Carder management.
 *
 * ACCESS MODEL
 * ────────────
 * View (index, print): Super Admin · Planning Officer · Admin Group
 *
 * Write (create, edit, disable/enable):
 *   Super Admin — unrestricted
 *   Admin Group — Director category ONLY
 *   Planning Officer — VIEW only, all write actions are blocked at request
 *                      authorisation and controller gate level
 *
 * Why Director-only?
 *   Approved carder figures are the official Ministry-issued headcount.
 *   They should only be recorded or amended by the officer responsible for
 *   institutional governance, not by the Planning Officer who monitors them.
 *
 * DISABLE vs DELETE
 * ─────────────────
 * Hard delete is intentionally removed. Disabling a record:
 *   - Removes it from all reports and carry-forward calculations
 *   - Preserves the full audit trail (who set what, when)
 *   - Can be reversed by the Director at any time
 *   - Requires a reason, enforcing accountability
 */
class ApprovedCarderController extends Controller
{
    // ── Authorization gate ────────────────────────────────────────────────

    /**
     * Gate check for any write operation on approved carder records.
     *
     * Authorised: Super Admin · Planning Officer · Admin Group Director /
     * Deputy Director General / Deputy Director / Medical Officer Planning.
     *
     * @see User::canManageApprovedCarder()
     */
    private function authorizeWrite(): void
    {
        if (! auth()->user()->canManageApprovedCarder()) {
            abort(403,
                'You do not have permission to create or modify approved carder records. '
                . 'Access is granted to: Super Admin, Planning Officer, Director, '
                . 'Deputy Director General, Deputy Director, and Medical Officer Planning.'
            );
        }
    }

    // ── Index ─────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $year         = (int) $request->query('year', now()->year);
        $showInactive = $request->boolean('show_inactive', false);

        // Non-Director Admin Group and Planning Officers see active only.
        // Super Admin and Director can toggle inactive records on.
        $canToggleActive = auth()->user()->canManageApprovedCarder();

        $query = ApprovedCarder::with('position')
            ->forYearAll($year)
            ->when(! $showInactive || ! $canToggleActive, fn ($q) => $q->where('is_active', true))
            ->orderBy('position_id');

        $carders = $query->paginate(25)->withQueryString();
        $years   = ApprovedCarder::select('year')->distinct()->orderByDesc('year')->pluck('year');

        // Totals for the header strip — active only
        $activeCarders = ApprovedCarder::forYear($year)->get();
        $totalApproved = $activeCarders->sum('approved_amount');
        $positionCount = $activeCarders->count();

        return view('approved-carders.index', compact(
            'carders', 'year', 'years',
            'showInactive', 'canToggleActive',
            'totalApproved', 'positionCount'
        ));
    }

    // ── Create / Store ────────────────────────────────────────────────────

    public function create()
    {
        $this->authorizeWrite();

        $positions = Position::active()->orderBy('title')->get();

        return view('approved-carders.form', [
            'carder'    => new ApprovedCarder(),
            'positions' => $positions,
        ]);
    }

    public function store(StoreApprovedCarderRequest $request)
    {
        // Double-gate: FormRequest authorise() AND controller gate
        $this->authorizeWrite();

        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $data['is_active']  = true;

        try {
            $carder = DB::transaction(fn () => ApprovedCarder::create($data));
        } catch (\Throwable $e) {
            Log::error('[ApprovedCarder::store failed] ' . $e->getMessage(), [
                'exception' => $e, 'user' => $request->user()->id,
            ]);
            return back()->with('error', 'A database error occurred. Please try again.');
        }

        AuditLogService::created(
            $carder,
            "Director set approved carder: Position #{$data['position_id']}, Year {$data['year']}, Amount {$data['approved_amount']}"
        );

        return redirect()
            ->route('approved-carders.index', ['year' => $data['year']])
            ->with('success', 'Approved carder figures recorded.');
    }

    // ── Edit / Update ─────────────────────────────────────────────────────

    public function edit(ApprovedCarder $approvedCarder)
    {
        $this->authorizeWrite();

        if (! $approvedCarder->is_active) {
            return redirect()
                ->route('approved-carders.index', ['year' => $approvedCarder->year])
                ->with('error', 'Cannot edit a disabled record. Re-enable it first.');
        }

        $positions = Position::active()->orderBy('title')->get();

        return view('approved-carders.form', [
            'carder'    => $approvedCarder,
            'positions' => $positions,
        ]);
    }

    public function update(StoreApprovedCarderRequest $request, ApprovedCarder $approvedCarder)
    {
        $this->authorizeWrite();

        if (! $approvedCarder->is_active) {
            return back()->with('error', 'Cannot update a disabled record. Re-enable it first.');
        }

        $old = $approvedCarder->getOriginal();
        $approvedCarder->update($request->validated());

        AuditLogService::updated(
            $approvedCarder, $old,
            "Director updated approved carder: Position #{$approvedCarder->position_id}, Year {$approvedCarder->year}"
        );

        return redirect()
            ->route('approved-carders.index', ['year' => $approvedCarder->year])
            ->with('success', 'Approved carder figures updated.');
    }

    // ── Toggle (disable / re-enable) — replaces destroy() ────────────────

    /**
     * Disabling removes the record from all reports while preserving the
     * full audit trail. Re-enabling restores it. Hard deletion is not
     * permitted — these are Ministry reference documents.
     */
    public function toggle(Request $request, ApprovedCarder $approvedCarder)
    {
        $this->authorizeWrite();

        if ($approvedCarder->is_active) {
            // Disabling — require a reason
            $request->validate([
                'disable_reason' => ['required', 'string', 'min:10', 'max:500'],
            ], [
                'disable_reason.required' => 'A reason is required before disabling an approved carder record.',
                'disable_reason.min'      => 'Please provide at least 10 characters explaining why this record is being disabled.',
            ]);

            $old = $approvedCarder->getOriginal();
            $approvedCarder->update([
                'is_active'      => false,
                'disabled_by'    => $request->user()->id,
                'disabled_at'    => now(),
                'disable_reason' => $request->input('disable_reason'),
            ]);

            AuditLogService::updated(
                $approvedCarder, $old,
                "Approved carder DISABLED by {$request->user()->name}: {$request->input('disable_reason')}"
            );

            return back()->with('success',
                "Record for {$approvedCarder->position->title} ({$approvedCarder->year}) has been disabled " .
                "and will no longer appear in reports."
            );
        }

        // Re-enabling
        $old = $approvedCarder->getOriginal();
        $approvedCarder->update([
            'is_active'      => true,
            'disabled_by'    => null,
            'disabled_at'    => null,
            'disable_reason' => null,
        ]);

        AuditLogService::updated(
            $approvedCarder, $old,
            "Approved carder RE-ENABLED by {$request->user()->name} — Position #{$approvedCarder->position_id}"
        );

        return back()->with('success',
            "Record for {$approvedCarder->position->title} ({$approvedCarder->year}) has been re-enabled."
        );
    }

    // ── Print ─────────────────────────────────────────────────────────────

    public function print(Request $request)
    {
        $year = (int) $request->query('year', now()->year);

        // Print always shows active records only
        $carders = ApprovedCarder::with('position')
            ->forYear($year)   // scopeForYear includes is_active=true filter
            ->get()
            ->sortBy(fn ($c) => $c->position->title ?? '');

        $totalApproved = $carders->sum('approved_amount');

        return view('approved-carders.print', compact('carders', 'year', 'totalApproved'));
    }
}
