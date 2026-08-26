<?php

namespace App\Http\Controllers;

use App\Models\CarderMonthlyEntry;
use App\Models\UserCategory;
use App\Models\SystemSetting;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

/** Handles both the amendment workflow (Feature 2) and verification layer (Feature 10). */
class EntryAmendmentController extends Controller
{
    // ── Amendment Workflow (Feature 2) ───────────────────────────────────

    /** Officer requests amendment on a previously submitted entry. */
    public function request(Request $request, CarderMonthlyEntry $entry): \Illuminate\Http\RedirectResponse
    {
        // Security: officer may only request amendment for their own entries
        if (! $request->user()->isSuperAdmin() && ! $request->user()->isPlanningOfficer()) {
            if (! $request->user()->hasSubjectCode($entry->subject_code_id)) {
                abort(403, 'You may only request amendments for entries under your own subject codes.');
            }
        }

        // Only verified (locked) entries need the amendment workflow.
        // Submitted entries can be edited directly.
        if (! $entry->is_locked && $entry->status === CarderMonthlyEntry::STATUS_SUBMITTED) {
            return back()->with('info',
                'This entry has not been locked yet — you can edit it directly.'
            );
        }

        if ($entry->status === CarderMonthlyEntry::STATUS_AMENDMENT_REQUESTED) {
            return back()->with('warning',
                'An amendment request is already pending for this entry. '
                . 'Please wait for the supervisor to approve or reject it.'
            );
        }

        if ($entry->status === CarderMonthlyEntry::STATUS_CANCELLED) {
            return back()->with('error', 'Cancelled entries cannot be amended.');
        }

        $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ], [
            'reason.required' => 'Please state why you need to amend this entry.',
            'reason.min'      => 'Reason must be at least 10 characters.',
        ]);

        $old = $entry->getOriginal();
        $entry->update([
            'status'                 => CarderMonthlyEntry::STATUS_AMENDMENT_REQUESTED,
            'amendment_requested_by' => $request->user()->id,
            'amendment_requested_at' => now(),
            'amendment_reason'       => trim($request->input('reason')),
        ]);

        AuditLogService::updated(
            $entry, $old,
            "Amendment requested by {$request->user()->name}: {$request->input('reason')}"
        );

        return back()->with('success',
            'Amendment request submitted. Your supervisor will review it shortly.'
        );
    }

    /** Supervisor approves amendment — entry goes back to editable state. */
    public function approve(Request $request, CarderMonthlyEntry $entry)
    {
        $old = $entry->getOriginal();
        $entry->update([
            'status'                 => CarderMonthlyEntry::STATUS_SUBMITTED,
            'amendment_requested_at' => null,
            'amendment_reason'       => null,
            'is_locked'              => false,
            'verified_by'            => null,
            'verified_at'            => null,
        ]);

        AuditLogService::updated($entry, $old, 'Amendment approved — entry unlocked for resubmission.');

        $monthLabel = \DateTime::createFromFormat('!m', $entry->month)->format('M');

        \App\Services\NotificationService::send(
            $entry->amendmentRequestedBy,
            type:  'amendment_approved',
            title: 'Amendment approved',
            body:  "Your amendment request for the {$monthLabel} {$entry->year} entry ({$entry->subjectCode->code}) was approved — you may now resubmit.",
            link:  route('carder-entries.edit', $entry),
        );

        return back()->with('success', 'Amendment approved. The officer may now resubmit.');
    }

    /** Supervisor rejects amendment request — entry stays as-is. */
    public function reject(Request $request, CarderMonthlyEntry $entry)
    {
        $request->validate(['rejection_reason' => ['required','string','max:500']]);

        $old = $entry->getOriginal();
        $entry->update([
            'status'                 => CarderMonthlyEntry::STATUS_SUBMITTED,
            'amendment_requested_at' => null,
            'amendment_reason'       => "REJECTED: {$request->input('rejection_reason')}",
        ]);

        AuditLogService::updated($entry, $old, 'Amendment rejected.');

        $monthLabel = \DateTime::createFromFormat('!m', $entry->month)->format('M');

        \App\Services\NotificationService::send(
            $entry->amendmentRequestedBy,
            type:  'amendment_rejected',
            title: 'Amendment request rejected',
            body:  "Your amendment request for the {$monthLabel} {$entry->year} entry ({$entry->subjectCode->code}) was rejected: "
                 . \Illuminate\Support\Str::limit($request->input('rejection_reason'), 80),
            link:  route('carder-entries.index'),
        );

        return back()->with('success', 'Amendment request rejected.');
    }

    public function index(Request $request)
    {
        $amendments = CarderMonthlyEntry::amendmentRequested()
            ->with(['subjectCode', 'position', 'amendmentRequestedBy'])
            ->orderByDesc('amendment_requested_at')
            ->paginate(20);

        $pending = CarderMonthlyEntry::pendingVerification()->count();

        return view('carder-entries.amendments', compact('amendments', 'pending'));
    }

    // ── Verification Layer (Feature 10) ──────────────────────────────────

    public function pendingVerification(Request $request)
    {
        if (! SystemSetting::getBool('entry_verification_required', false)) {
            return redirect()->route('carder-entries.index')
                ->with('info', 'Entry verification is not enabled. Enable it in Admin → Settings.');
        }

        $entries = CarderMonthlyEntry::pendingVerification()
            ->with(['subjectCode', 'position', 'submittedBy'])
            ->orderByDesc('submitted_at')
            ->paginate(25);

        return view('carder-entries.pending-verification', compact('entries'));
    }

    public function verify(Request $request, CarderMonthlyEntry $entry): \Illuminate\Http\RedirectResponse
    {
        $this->authorizeVerifier($request);

        if ($entry->status === CarderMonthlyEntry::STATUS_VERIFIED) {
            return redirect()->route('carder-entries.pending-verification')
                ->with('info', 'This entry was already verified.');
        }

        $old = $entry->getOriginal();
        $entry->update([
            'status'      => CarderMonthlyEntry::STATUS_VERIFIED,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
            'is_locked'   => true,
        ]);

        AuditLogService::updated(
            $entry, $old,
            "Entry verified and locked by {$request->user()->name}"
        );

        // Always redirect to the pending list — back() can point to a detail
        // page rather than the list, leaving the verified entry still visible.
        $monthLabel = \DateTime::createFromFormat('!m', $entry->month)->format('M');

        \App\Services\NotificationService::send(
            $entry->submittedBy,
            type:  'entry_verified',
            title: 'Entry verified',
            body:  "Your {$monthLabel} {$entry->year} entry for {$entry->subjectCode->code} was verified.",
            link:  route('carder-entries.index'),
        );

        return redirect()->route('carder-entries.pending-verification')
            ->with('success',
                "Entry for {$entry->subjectCode->code} ({$monthLabel} {$entry->year}) "
                . "verified ✓ and locked."
            );
    }

    public function rejectVerification(Request $request, CarderMonthlyEntry $entry): \Illuminate\Http\RedirectResponse
    {
        $this->authorizeVerifier($request);
        $request->validate(['rejection_reason' => ['required', 'string', 'min:10', 'max:500']]);

        $old = $entry->getOriginal();
        $entry->update([
            'status'           => CarderMonthlyEntry::STATUS_AMENDMENT_REQUESTED,
            'amendment_reason' => 'Verification rejected: ' . $request->input('rejection_reason'),
        ]);

        AuditLogService::updated($entry, $old, 'Verification rejected — sent back for correction.');

        $monthLabel = \DateTime::createFromFormat('!m', $entry->month)->format('M');

        \App\Services\NotificationService::send(
            $entry->submittedBy,
            type:  'entry_rejected',
            title: 'Entry sent back for correction',
            body:  "Your {$monthLabel} {$entry->year} entry for {$entry->subjectCode->code} needs correction: "
                 . \Illuminate\Support\Str::limit($request->input('rejection_reason'), 80),
            link:  route('carder-entries.index'),
        );

        return redirect()->route('carder-entries.pending-verification')
            ->with('warning',
                "Entry for {$entry->subjectCode->code} ({$monthLabel} {$entry->year}) "
                . "sent back for correction."
            );
    }

    /**
     * Gate that enforces who may verify or reject a monthly entry.
     *
     * Three possible configurations (set via Admin → Settings):
     *   'planning_officer' — any user holding the planning_officer role
     *   'super_admin'      — only Super Admin
     *   'category'         — any active Admin Group user whose category_id
     *                        matches the configured entry_verifier_category_id
     *
     * Super Admin bypasses the check regardless of configuration (they can
     * always act as verifier for operational continuity).
     *
     * Security note: each branch checks the user's actual DB attributes via
     * model methods — not request input — so this cannot be spoofed.
     */
    private function authorizeVerifier(Request $request): void
    {
        $user = $request->user();

        // Super Admin always allowed — bypass all other checks.
        if ($user->isSuperAdmin()) {
            return;
        }

        $configuredRole = SystemSetting::get('entry_verifier_role', 'planning_officer');

        match ($configuredRole) {

            'super_admin' => abort(
                403,
                'Entry verification is restricted to Super Admin. Contact the system administrator.'
            ),

            'planning_officer' => $user->isPlanningOfficer()
                ? null   // allowed — fall through
                : abort(403, 'Only Planning Officers or Super Admin may verify entries.'),

            'category' => $this->authorizeByCategory($user),

            default => abort(403, 'Verification role is not configured correctly. Contact the system administrator.'),
        };
    }

    /**
     * Category-based verifier gate — supports multiple allowed categories.
     *
     * Reads `entry_verifier_category_ids` (JSON array of UserCategory IDs).
     * The requesting user must be:
     *   1. An Admin Group member (role check)
     *   2. Assigned to one of the configured verifier categories
     *
     * Both conditions are verified against the user's actual DB record, not
     * request input, so they cannot be spoofed.
     */
    private function authorizeByCategory(\App\Models\User $user): void
    {
        $rawIds = \App\Models\SystemSetting::getJson('entry_verifier_category_ids', []);
        $categoryIds = array_filter(array_map('intval', (array) $rawIds));

        if (empty($categoryIds)) {
            abort(403,
                'No verifier categories are configured. ' .
                'Ask the Super Admin to select at least one category in Admin → Settings.'
            );
        }

        if (! $user->isAdminGroup()) {
            abort(403,
                'Entry verification is restricted to Admin Group members ' .
                'assigned to an authorised category.'
            );
        }

        if (! in_array((int) $user->category_id, $categoryIds, true)) {
            // Resolve category names for the error message (informative but
            // doesn't leak existence of other categories to non-admin users)
            $names = \App\Models\UserCategory::whereIn('id', $categoryIds)
                ->where('is_active', true)
                ->pluck('name')
                ->join(', ');

            abort(403,
                "Your account category is not authorised to verify entries. " .
                "Authorised categories: {$names}."
            );
        }
    }
}
