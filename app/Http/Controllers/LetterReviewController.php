<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateLetterRecipientRequest;
use App\Models\Letter;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

/**
 * Director / Deputy Director / Administrative Officer side of Letter
 * Sharing — see letters routed to them, open (which marks their copy read,
 * surfaced back on the Subject Officer's dashboard), and record a decision.
 * Super Admin gets read-only oversight of every letter; only the actual
 * recipient may update their own status.
 */
class LetterReviewController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            $letters = Letter::with(['createdBy', 'recipients.user.category', 'attachments'])
                ->orderByDesc('created_at')->paginate(15);
        } else {
            $letters = Letter::with(['createdBy', 'recipients.user.category', 'attachments'])
                ->whereHas('recipients', fn ($q) => $q->where('user_id', $user->id))
                ->orderByDesc('created_at')->paginate(15);
        }

        return view('letter-reviews.index', compact('letters'));
    }

    public function show(Request $request, Letter $letter)
    {
        $user = $request->user();
        $letter->load(['createdBy', 'recipients.user.category', 'attachments']);

        $myRecipientRow = $letter->recipients->firstWhere('user_id', $user->id);

        if (! $myRecipientRow && ! $user->isSuperAdmin()) {
            abort(403, 'This letter was not addressed to you.');
        }

        // Opening the letter marks it read — this is what flips the status
        // visible on the Subject Officer's dashboard.
        $myRecipientRow?->markRead();

        return view('letter-reviews.show', compact('letter', 'myRecipientRow'));
    }

    public function update(UpdateLetterRecipientRequest $request, Letter $letter)
    {
        $user = $request->user();
        $myRecipientRow = $letter->recipients()->where('user_id', $user->id)->first();

        if (! $myRecipientRow) {
            abort(403, 'This letter was not addressed to you.');
        }

        $old = $myRecipientRow->getOriginal();
        $myRecipientRow->markRead();
        $myRecipientRow->update($request->validated());

        AuditLogService::updated(
            $myRecipientRow,
            $old,
            "{$user->name} marked letter \"{$letter->title}\" as {$myRecipientRow->status}"
        );

        return redirect()->route('letter-reviews.show', $letter)->with('success', 'Your review has been recorded.');
    }
}
