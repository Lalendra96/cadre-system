<?php

namespace App\Http\Controllers;

use App\Models\ApprovedCarder;
use App\Models\CadreReviewProposal;
use App\Models\Position;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Annual cadre review workflow (Feature 8). */
class CadreReviewController extends Controller
{
    public function index()
    {
        $proposals = CadreReviewProposal::with('creator')->orderByDesc('created_at')->paginate(15);
        return view('cadre-reviews.index', compact('proposals'));
    }

    public function create()
    {
        $positions    = Position::active()->orderBy('title')->get();
        $currentYear  = now()->year;
        $proposalYear = $currentYear + 1;

        // Build a position_id → approved_amount integer map for the current year.
        // groupBy + sum handles the (rare) case where duplicate rows exist for
        // the same position/year — avoids keyBy silently discarding extras.
        $approved = ApprovedCarder::forYear($currentYear)
            ->get()
            ->groupBy('position_id')
            ->map(fn ($rows) => (int) $rows->sum('approved_amount'));

        return view('cadre-reviews.form', compact('positions', 'proposalYear', 'approved'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'                       => ['required', 'string', 'max:200'],
            'proposal_year'               => ['required', 'integer', 'min:' . now()->year, 'max:' . (now()->year + 5)],
            'justification'               => ['nullable', 'string', 'max:2000'],
            'items'                       => ['required', 'array', 'min:1'],
            'items.*.position_id'         => ['required', 'integer', 'exists:positions,id'],
            'items.*.proposed_amount'     => ['required', 'integer', 'min:0', 'max:9999'],
            'items.*.current_approved'    => ['required', 'integer', 'min:0'],
            'items.*.justification'       => ['nullable', 'string', 'max:500'],
        ]);

        // ── Planning Officer validation rules ─────────────────────────────

        $items    = $request->input('items', []);
        $errors   = [];

        // 1. No duplicate positions in one proposal
        $positionIds  = array_column($items, 'position_id');
        $uniqueIds    = array_unique($positionIds);
        if (count($positionIds) !== count($uniqueIds)) {
            $errors['items'] = 'Each position may appear only once per proposal. Remove duplicate rows.';
        }

        // 2. Significant decreases (> 20% below current) require justification
        foreach ($items as $i => $item) {
            $current  = (int) $item['current_approved'];
            $proposed = (int) $item['proposed_amount'];
            $net      = $proposed - $current;

            if ($current > 0 && $net < 0) {
                $decreasePct = abs($net) / $current * 100;
                if ($decreasePct > 20 && empty(trim($item['justification'] ?? ''))) {
                    $errors["items.{$i}.justification"] =
                        'A justification is required when reducing a position by more than 20% of current approved.';
                }
            }

            // 3. Cannot set proposed to 0 without justification
            if ($proposed === 0 && $current > 0 && empty(trim($item['justification'] ?? ''))) {
                $errors["items.{$i}.justification"] =
                    'A justification is required when proposing zero posts for a currently staffed position.';
            }
        }

        // 4. At least one item must actually change
        $hasChange = collect($items)->contains(fn ($item) =>
            (int) $item['proposed_amount'] !== (int) $item['current_approved']
        );
        if (! $hasChange) {
            $errors['items'] = ($errors['items'] ?? '') .
                ' At least one position must have a proposed change — otherwise no proposal is needed.';
        }

        if (! empty($errors)) {
            return back()->withErrors($errors)->withInput();
        }

        try {
            $proposal = DB::transaction(function () use ($request, $items) {
                $p = CadreReviewProposal::create([
                    'title'         => $request->input('title'),
                    'proposal_year' => $request->input('proposal_year'),
                    'justification' => $request->input('justification'),
                    'status'        => 'draft',
                    'created_by'    => $request->user()->id,
                ]);
    
                foreach ($items as $item) {
                    $net = (int) $item['proposed_amount'] - (int) $item['current_approved'];
                    $p->items()->create([
                        'position_id'      => $item['position_id'],
                        'current_approved' => $item['current_approved'],
                        'proposed_amount'  => $item['proposed_amount'],
                        'change_type'      => $net > 0 ? 'increase' : ($net < 0 ? 'decrease' : 'no_change'),
                        'justification'    => $item['justification'] ?? null,
                    ]);
                }
    
                return $p;
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error(
                '[Transaction failed] ' . $e->getMessage(),
                ['exception' => $e, 'file' => __FILE__, 'line' => __LINE__]
            );
            return back()->with('error', 'A database error occurred. Please try again or contact support.');
        }

        AuditLogService::created($proposal, "Cadre review proposal: {$proposal->title}");
        return redirect()->route('cadre-reviews.show', $proposal)->with('success', 'Proposal created as draft.');
    }

    public function show(CadreReviewProposal $cadreReview)
    {
        $cadreReview->load(['items.position', 'creator', 'approver']);
        return view('cadre-reviews.show', ['proposal' => $cadreReview]);
    }

    public function edit(CadreReviewProposal $cadreReview)
    {
        abort_if($cadreReview->status !== 'draft', 403, 'Only draft proposals can be edited.');
        $cadreReview->load('items.position');
        $positions = Position::active()->orderBy('title')->get();
        $approved  = ApprovedCarder::forYear(now()->year)->get()
            ->groupBy('position_id')
            ->map(fn ($rows) => (int) $rows->sum('approved_amount'));
        return view('cadre-reviews.form', ['proposal'=>$cadreReview, 'positions'=>$positions, 'approved'=>$approved, 'proposalYear'=>$cadreReview->proposal_year]);
    }

    public function update(Request $request, CadreReviewProposal $cadreReview)
    {
        abort_if($cadreReview->status !== 'draft', 403);
        // Similar to store but update existing
        $cadreReview->update($request->only(['title','justification']));
        $cadreReview->items()->delete();
        foreach ($request->input('items', []) as $item) {
            $net = (int)$item['proposed_amount'] - (int)$item['current_approved'];
            $cadreReview->items()->create([
                'position_id'      => $item['position_id'],
                'current_approved' => $item['current_approved'],
                'proposed_amount'  => $item['proposed_amount'],
                'change_type'      => $net > 0 ? 'increase' : ($net < 0 ? 'decrease' : 'no_change'),
                'justification'    => $item['justification'] ?? null,
            ]);
        }
        return redirect()->route('cadre-reviews.show', $cadreReview)->with('success', 'Draft updated.');
    }

    public function submit(Request $request, CadreReviewProposal $cadreReview)
    {
        abort_if($cadreReview->status !== 'draft', 403);
        abort_unless(
            $request->user()->isSuperAdmin() || $cadreReview->created_by === $request->user()->id,
            403,
            'You may only submit a proposal you created yourself.'
        );
        $cadreReview->update(['status'=>'submitted','submitted_by'=>$request->user()->id,'submitted_at'=>now()]);
        return redirect()->route('cadre-reviews.show', $cadreReview)->with('success', 'Proposal submitted for Director approval.');
    }

    public function approve(Request $request, CadreReviewProposal $cadreReview)
    {
        $request->validate(['approval_remarks' => ['nullable','string','max:500']]);

        // Stage-aware authorization — the previous version let ANY Admin
        // Group member (Chief Clerk, Chief Accountant, MO Planning — not
        // just Director) advance a proposal through every stage of this
        // workflow in one sitting, since match() only checked the CURRENT
        // status, never WHO was approving it. Each transition now requires
        // the authority actually responsible for that stage.
        $this->authorizeApprovalStage($request, $cadreReview);

        $nextStatus = match($cadreReview->status) {
            'submitted'         => 'director_approved',
            'director_approved' => 'moh_submitted',
            'moh_submitted'     => 'approved',
            default             => abort(403),
        };
        $cadreReview->update(['status'=>$nextStatus,'approved_by'=>$request->user()->id,'approved_at'=>now(),'approval_remarks'=>$request->input('approval_remarks')]);
        return redirect()->route('cadre-reviews.show', $cadreReview)->with('success', "Status updated to: {$nextStatus}.");
    }

    public function reject(Request $request, CadreReviewProposal $cadreReview)
    {
        $request->validate(['approval_remarks' => ['required','string','max:500']]);
        $this->authorizeApprovalStage($request, $cadreReview);
        $cadreReview->update(['status'=>'rejected','approved_by'=>$request->user()->id,'approved_at'=>now(),'approval_remarks'=>$request->input('approval_remarks')]);
        return redirect()->route('cadre-reviews.show', $cadreReview)->with('success', 'Proposal rejected.');
    }

    /**
     * Super Admin bypasses every stage (administrative oversight). Every
     * other transition requires isDirector() specifically — 'submitted'
     * (needs Director sign-off), 'director_approved' (Director coordinates
     * MOH submission), and 'moh_submitted' (Director records the
     * Ministry's external decision) are all Director-authority actions in
     * this workflow; no stage here is delegated to a broader Admin Group
     * category like Chief Clerk or Chief Accountant.
     */
    private function authorizeApprovalStage(Request $request, CadreReviewProposal $cadreReview): void
    {
        if ($request->user()->isSuperAdmin()) {
            return;
        }
        abort_unless(
            $request->user()->isDirector(),
            403,
            'Only the Director may approve or reject a cadre review proposal at this stage.'
        );
    }

    /** Apply an approved proposal — creates new ApprovedCarder rows for the proposal year. */
    public function apply(Request $request, CadreReviewProposal $cadreReview)
    {
        abort_if($cadreReview->status !== 'approved', 403, 'Only MoH-approved proposals can be applied.');
        $this->authorizeApprovalStage($request, $cadreReview);
        $cadreReview->load('items');

        try {
            DB::transaction(function () use ($cadreReview) {
                foreach ($cadreReview->items as $item) {
                    ApprovedCarder::updateOrCreate(
                        ['position_id' => $item->position_id, 'year' => $cadreReview->proposal_year],
                        ['approved_amount' => $item->proposed_amount]
                    );
                }
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error(
                '[Transaction failed] ' . $e->getMessage(),
                ['exception' => $e, 'file' => __FILE__, 'line' => __LINE__]
            );
            return back()->with('error', 'A database error occurred. Please try again or contact support.');
        }

        AuditLogService::updated($cadreReview, [], "Cadre review proposal {$cadreReview->id} applied to year {$cadreReview->proposal_year}");
        return redirect()->route('cadre-reviews.show', $cadreReview)->with('success', "Approved carder for {$cadreReview->proposal_year} updated from this proposal.");
    }

    /**
     * Cancel a draft proposal — replaces hard-delete.
     * Only drafts can be cancelled. Submitted/approved proposals are permanent.
     */
    public function destroy(Request $request, CadreReviewProposal $cadreReview)
    {
        abort_if($cadreReview->status !== 'draft', 403,
            'Only draft proposals can be cancelled. Submitted or approved proposals are permanent records.'
        );

        $request->validate([
            'cancel_reason' => ['nullable','string','max:500'],
        ]);

        $cadreReview->update([
            'status' => 'cancelled',
            'approval_remarks' => $request->input('cancel_reason', 'Cancelled by creator'),
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        \App\Services\AuditLogService::updated(
            $cadreReview,
            ['status' => 'draft'],
            'Cadre review proposal cancelled: ' . ($request->input('cancel_reason') ?? 'no reason given')
        );

        return redirect()->route('cadre-reviews.index')
            ->with('success', 'Proposal cancelled. It is preserved for audit purposes.');
    }
}
