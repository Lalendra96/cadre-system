<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use App\Models\LetterComment;
use App\Models\LetterPresence;
use App\Models\LetterRevision;
use App\Models\ServiceLetterLetterhead;
use App\Services\AuditLogService;
use App\Services\FeatureToggleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LetterLiveWorkspaceController extends Controller
{
    public function workspace(Request $request, Letter $letter): View
    {
        $this->authorizeViewer($request, $letter);
        abort_unless($letter->live_edit_enabled, 404);
        $letter->load(['createdBy', 'approvedBy', 'letterhead', 'eSignature', 'recipients.user.category', 'comments.user', 'revisions.author', 'attachments']);
        $row = $letter->recipients->firstWhere('user_id', $request->user()->id);
        $canEdit = $this->canEdit($request, $letter, $row) && FeatureToggleService::enabled('live_letter_editing');
        $canComment = ($request->user()->id === $letter->created_by || $row?->canComment() || $request->user()->isSuperAdmin())
            && $letter->workflow_status !== Letter::STATUS_ARCHIVED;
        $letterheads = ServiceLetterLetterhead::active()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('letters.workspace', compact('letter', 'row', 'canEdit', 'canComment', 'letterheads'));
    }

    public function autosave(Request $request, Letter $letter): JsonResponse
    {
        abort_unless(FeatureToggleService::enabled('live_letter_editing'), 403, 'Live Letter Editing is disabled by the Super Administrator.');
        $this->authorizeEditor($request, $letter);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'], 'live_content' => ['required', 'string', 'max:200000'],
            'reference_no' => ['nullable', 'string', 'max:100'], 'letterhead_id' => ['nullable', 'integer', Rule::exists('service_letter_letterheads', 'id')->where(fn ($q) => $q->where('is_active', true))], 'document_classification' => ['required', 'in:internal,confidential,restricted'],
            'contains_personal_data' => ['required', 'boolean'], 'retention_category' => ['required', 'in:official_correspondence,hr_record,administrative_record,temporary_working_record'],
            'access_note' => ['nullable', 'string', 'max:1000'], 'review_due_date' => ['nullable', 'date'], 'editor_lock_version' => ['required', 'integer', 'min:1'],
        ]);

        $result = DB::transaction(function () use ($request, $letter, $data) {
            $locked = Letter::query()->lockForUpdate()->findOrFail($letter->id);
            abort_unless($locked->isContentEditable(), 423, 'This letter is locked because it is under review or has been approved/issued.');
            if ((int) $locked->editor_lock_version !== (int) $data['editor_lock_version']) {
                return ['conflict' => true, 'version' => (int) $locked->editor_lock_version, 'updated_at' => $locked->updated_at?->toIso8601String()];
            }
            $oldHash = $locked->contentHash();
            $locked->fill([
                'title' => $data['title'], 'live_content' => $data['live_content'], 'reference_no' => $data['reference_no'] ?? null,
                'letterhead_id' => $data['letterhead_id'] ?? null,
                'document_classification' => $data['document_classification'], 'contains_personal_data' => (bool) $data['contains_personal_data'],
                'retention_category' => $data['retention_category'], 'access_note' => $data['access_note'] ?? null,
                'review_due_date' => $data['review_due_date'] ?? null,
                'workflow_status' => Letter::STATUS_EDITING, 'editor_lock_version' => (int) $locked->editor_lock_version + 1,
            ]);
            $newHash = $locked->contentHash();
            if ($newHash !== $oldHash) {
                $locked->save();
                $this->createRevision($locked, (int) $request->user()->id, 'autosave');
                AuditLogService::event($locked, 'letter_live_autosave', 'Live Letter Workspace saved a governed revision.');
            } else {
                $locked->editor_lock_version = (int) $data['editor_lock_version'];
            }

            return ['conflict' => false, 'version' => (int) $locked->editor_lock_version, 'saved_at' => now()->toIso8601String(), 'hash' => $newHash];
        });

        if ($result['conflict']) {
            return response()->json([
                'message' => "A newer version exists. Reload before continuing so another officer's changes are not overwritten.",
                'editor_lock_version' => $result['version'], 'updated_at' => $result['updated_at'],
            ], 409);
        }

        return response()->json($result);
    }

    public function heartbeat(Request $request, Letter $letter): JsonResponse
    {
        $this->authorizeViewer($request, $letter);
        LetterPresence::updateOrCreate(['letter_id' => $letter->id, 'user_id' => $request->user()->id], ['last_seen_at' => now()]);
        LetterPresence::where('last_seen_at', '<', now()->subMinutes(10))->delete();
        $people = LetterPresence::with('user:id,name')->where('letter_id', $letter->id)->where('last_seen_at', '>=', now()->subMinutes(2))->get()
            ->map(fn ($p) => ['id' => $p->user_id, 'name' => $p->user?->name ?? 'User', 'last_seen_at' => $p->last_seen_at?->toIso8601String()]);

        return response()->json(['people' => $people]);
    }

    public function addComment(Request $request, Letter $letter): JsonResponse
    {
        $this->authorizeViewer($request, $letter);
        $row = $letter->recipients()->where('user_id', $request->user()->id)->first();
        abort_unless($request->user()->id === $letter->created_by || $row?->canComment() || $request->user()->isSuperAdmin(), 403);
        $data = $request->validate([
            'body' => ['required', 'string', 'max:3000'],
            'comment_type' => ['nullable', 'in:comment,suggestion'],
            'quoted_text' => ['nullable', 'string', 'max:3000'],
        ]);
        $comment = $letter->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
            'comment_type' => $data['comment_type'] ?? 'comment',
            'quoted_text' => $data['quoted_text'] ?? null,
            'is_resolved' => false,
        ]);
        AuditLogService::event($letter, 'letter_comment_added', 'A governance/review comment was added to the shared letter.');

        return response()->json([
            'id' => $comment->id, 'body' => $comment->body, 'type' => $comment->comment_type,
            'quoted_text' => $comment->quoted_text, 'user' => $request->user()->name,
            'created_at' => $comment->created_at->toIso8601String(),
        ], 201);
    }

    public function resolveComment(Request $request, Letter $letter, LetterComment $comment): RedirectResponse
    {
        $this->authorizeViewer($request, $letter);
        abort_unless($comment->letter_id === $letter->id, 404);
        abort_unless(
            $request->user()->id === $letter->created_by ||
            $request->user()->id === $comment->user_id ||
            $request->user()->isSuperAdmin(),
            403
        );

        $comment->update([
            'is_resolved' => true,
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
        ]);
        AuditLogService::event($letter, 'letter_comment_resolved', 'A live-letter comment or suggestion was resolved.');

        return back()->with('success', 'Comment resolved. The discussion remains in the audit history.');
    }

    public function submitForReview(Request $request, Letter $letter): RedirectResponse
    {
        abort_unless(FeatureToggleService::enabled('live_letter_editing'), 403);
        abort_unless($request->user()->id === $letter->created_by, 403);
        abort_unless($letter->isContentEditable(), 423);
        $reviewerCount = $letter->recipients()->where('access_level', 'reviewer')->count();
        abort_if($reviewerCount < 1, 422, 'Assign at least one Reviewer before submitting this letter for formal review.');
        DB::transaction(function () use ($letter) {
            $letter->update(['workflow_status' => Letter::STATUS_IN_REVIEW]);
            $letter->recipients()->where('access_level', 'reviewer')->update(['status' => 'pending', 'remarks' => null]);
        });
        AuditLogService::event($letter, 'letter_submitted_for_review', 'Live shared letter was locked and submitted for formal review.');

        return back()->with('success', 'Letter submitted for review. Content is now locked until a reviewer returns it or approval is completed.');
    }

    public function reopen(Request $request, Letter $letter): RedirectResponse
    {
        abort_unless(FeatureToggleService::enabled('live_letter_editing'), 403);
        abort_unless($request->user()->id === $letter->created_by, 403);
        abort_unless($letter->workflow_status === Letter::STATUS_RETURNED, 422);
        $letter->update(['workflow_status' => Letter::STATUS_EDITING, 'editor_lock_version' => (int) $letter->editor_lock_version + 1]);
        AuditLogService::event($letter, 'letter_reopened', 'Returned letter reopened as a governed editable revision.');

        return back()->with('success', 'Returned letter reopened for correction. Previous versions remain preserved.');
    }

    public function issue(Request $request, Letter $letter): RedirectResponse
    {
        abort_unless($request->user()->id === $letter->created_by || $request->user()->isSuperAdmin(), 403);
        abort_unless($letter->workflow_status === Letter::STATUS_APPROVED, 422, 'Only an approved letter can be issued.');
        $letter->update(['workflow_status' => Letter::STATUS_ISSUED, 'issued_at' => now()]);
        AuditLogService::event($letter, 'letter_issued', 'Approved live letter marked as issued and retained as an immutable official record.');

        return back()->with('success', 'Letter issued. The approved content remains immutable.');
    }

    public function restoreRevision(Request $request, Letter $letter, LetterRevision $revision): RedirectResponse
    {
        abort_unless(FeatureToggleService::enabled('live_letter_editing'), 403);
        $this->authorizeEditor($request, $letter);
        abort_unless($revision->letter_id === $letter->id, 404);
        abort_unless($letter->isContentEditable(), 423);
        $letter->update([
            'title' => $revision->title, 'live_content' => $revision->live_content, 'reference_no' => $revision->reference_no,
            'letterhead_id' => $revision->letterhead_id,
            'document_classification' => $revision->document_classification, 'contains_personal_data' => $revision->contains_personal_data,
            'retention_category' => $revision->retention_category ?? $letter->retention_category,
            'access_note' => $revision->access_note ?? $letter->access_note,
            'review_due_date' => $revision->review_due_date ?? $letter->review_due_date,
            'editor_lock_version' => (int) $letter->editor_lock_version + 1, 'workflow_status' => Letter::STATUS_EDITING,
        ]);
        $this->createRevision($letter, (int) $request->user()->id, 'restored_v'.$revision->version_number);
        AuditLogService::event($letter, 'letter_revision_restored', 'A prior live-letter revision was restored as a new revision.');

        return back()->with('success', 'Revision restored as a new version. History has not been overwritten.');
    }

    private function createRevision(Letter $letter, int $userId, string $reason): void
    {
        $next = (int) $letter->revisions()->max('version_number') + 1;
        $letter->revisions()->create([
            'version_number' => $next, 'title' => $letter->title, 'live_content' => $letter->live_content, 'reference_no' => $letter->reference_no, 'letterhead_id' => $letter->letterhead_id,
            'document_classification' => $letter->document_classification, 'contains_personal_data' => $letter->contains_personal_data,
            'retention_category' => $letter->retention_category, 'access_note' => $letter->access_note, 'review_due_date' => $letter->review_due_date,
            'snapshot_hash' => $letter->contentHash(), 'change_reason' => $reason, 'created_by' => $userId,
        ]);
    }

    private function authorizeViewer(Request $request, Letter $letter): void
    {
        $u = $request->user();
        $recipient = $letter->recipients()->where('user_id', $u->id)->exists();
        abort_unless($u->id === $letter->created_by || $recipient || $u->isSuperAdmin(), 403, 'You are not authorised to access this shared letter.');
    }

    private function canEdit(Request $request, Letter $letter, $row = null): bool
    {
        return $letter->isContentEditable() && ($request->user()->id === $letter->created_by || ($row && $row->canEdit()));
    }

    private function authorizeEditor(Request $request, Letter $letter): void
    {
        $row = $letter->recipients()->where('user_id', $request->user()->id)->first();
        abort_unless($this->canEdit($request,$letter,$row),403,'You do not have edit permission for this letter.');
    }
}
