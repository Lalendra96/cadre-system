<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLetterRequest;
use App\Models\Letter;
use App\Models\LetterAttachment;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Subject Officer side of Letter Sharing — upload one or more documents for
 * review by SPECIFIC, individually-selected people (never the whole Admin
 * Group by default). Gated by the 'feature:letters' middleware (Super Admin
 * can switch this off per officer).
 */
class LetterController extends Controller
{
    public function index(Request $request)
    {
        $letters = Letter::with('recipients.user.category', 'attachments')
            ->where('created_by', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('letters.index', compact('letters'));
    }

    public function create(Request $request)
    {
        $assignedCodes = $request->user()->subjectCodes()->get();

        $eligibleRecipients = User::active()
            ->havingRole(User::ROLE_ADMIN_GROUP)
            ->with('category')
            ->get()
            ->filter(fn ($u) => $u->isLetterRecipientCategory())
            ->groupBy(fn ($u) => $u->category->name)
            ->sortBy(fn ($group) => optional($group->first()->category)->sort_order ?? 999);

        return view('letters.create', compact('assignedCodes', 'eligibleRecipients'));
    }

    public function store(StoreLetterRequest $request)
    {
        $data = $request->validated();
        $files = $request->file('attachments', []);
        $recipientIds = $data['recipient_user_ids'];

        try {
            $letter = DB::transaction(function () use ($data, $files, $recipientIds, $request) {
                $letter = Letter::create([
                    'created_by'      => $request->user()->id,
                    'subject_code_id' => $data['subject_code_id'] ?? null,
                    'title'           => $data['title'],
                    'description'     => $data['description'] ?? null,
                ]);
    
                foreach ($files as $file) {
                    $path = $file->store('letters', 'local');
    
                    LetterAttachment::create([
                        'letter_id'         => $letter->id,
                        'file_path'         => $path,
                        'original_filename' => $file->getClientOriginalName(),
                        'file_size'         => $file->getSize(),
                    ]);
                }
    
                // Only the specifically selected recipients get a row — never the
                // whole Admin Group automatically.
                foreach ($recipientIds as $userId) {
                    $letter->recipients()->create(['user_id' => $userId]);
                }
    
                return $letter;
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error(
                '[Transaction failed] ' . $e->getMessage(),
                ['exception' => $e, 'file' => __FILE__, 'line' => __LINE__]
            );
            return back()->with('error', 'A database error occurred. Please try again or contact support.');
        }

        AuditLogService::created($letter, "Shared letter \"{$letter->title}\" with " . count($recipientIds) . ' recipient(s)');

        // Notify each recipient — body deliberately generic (title + who sent
        // it), since the recipient hasn't opened the letter yet and shouldn't
        // see its content in a notification list other people might glance at
        // on a shared screen.
        \App\Services\NotificationService::sendToMany(
            User::whereIn('id', $recipientIds)->get(),
            type:  'letter_received',
            title: 'New letter for review',
            body:  "\"{$letter->title}\" from {$request->user()->name}",
            link:  route('letters.show', $letter),
        );

        return redirect()->route('letters.index')->with('success', 'Letter shared for review.');
    }

    public function show(Request $request, Letter $letter)
    {
        $this->authorizeOwner($request, $letter);

        $letter->load('recipients.user.category', 'attachments');

        return view('letters.show', compact('letter'));
    }

    /** Per-attachment download — creator, an assigned recipient, or Super Admin. */
    public function downloadAttachment(Request $request, Letter $letter, LetterAttachment $attachment)
    {
        $this->gateFileAccess($request, $letter, $attachment);
        // Security 18: audit the download
        \App\Services\AuditLogService::logExport(
            $request->user()->id,
            'letter_attachment',
            $attachment->original_filename,
            'Letter', $letter->id,
            file_exists(Storage::disk('local')->path($attachment->file_path)) ? filesize(Storage::disk('local')->path($attachment->file_path)) : null,
            $request->ip(),
            $request->userAgent()
        );

        return Storage::disk('local')->download($attachment->file_path, $attachment->original_filename);
    }

    /**
     * Inline preview — same auth rules as download but served with
     * Content-Disposition: inline so the browser renders it in an iframe.
     * Only PDF and image files are served inline; Word docs are not
     * renderable by the browser and fall back to the download link.
     */
    public function previewAttachment(Request $request, Letter $letter, LetterAttachment $attachment)
    {
        $this->gateFileAccess($request, $letter, $attachment);

        $ext = strtolower(pathinfo($attachment->original_filename, PATHINFO_EXTENSION));

        $mimeMap = [
            'pdf'  => 'application/pdf',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
        ];

        if (! isset($mimeMap[$ext])) {
            return redirect()->route('letters.attachments.download', [$letter, $attachment]);
        }

        $absolutePath = Storage::disk('local')->path($attachment->file_path);

        if (! file_exists($absolutePath)) {
            abort(404, 'This file has been automatically removed (30-day retention policy).');
        }

        $mime = $mimeMap[$ext];
        $size = filesize($absolutePath);
        $safe = rawurlencode($attachment->original_filename);

        // Stream with explicit headers:
        //  • Content-Disposition: inline  → browser renders, does not download
        //  • X-Frame-Options removed       → allows <object>/<embed> embedding
        //    (those tags are NOT restricted by X-Frame-Options, but some
        //    middleware adds the header anyway — we override it here)
        //  • no-store                      → ensure the latest file version always loads
        return response()->stream(
            function () use ($absolutePath) {
                readfile($absolutePath);
            },
            200,
            [
                'Content-Type'              => $mime,
                'Content-Length'            => $size,
                'Content-Disposition'       => "inline; filename=\"{$attachment->original_filename}\"; filename*=UTF-8''{$safe}",
                'Cache-Control'             => 'no-store, must-revalidate',
                'X-Content-Type-Options'    => 'nosniff',
                'X-Frame-Options'           => 'SAMEORIGIN',   // kept for iframe safety elsewhere
            ]
        );
    }

    /** Shared auth gate used by both download and preview. */
    private function gateFileAccess(Request $request, Letter $letter, LetterAttachment $attachment): void
    {
        if ($attachment->letter_id !== $letter->id) {
            abort(404);
        }

        $user = $request->user();

        if ($user->isSubjectOfficer() && ! $user->can_view_letters) {
            abort(403, 'You do not have access to Letter Sharing. Contact the system administrator.');
        }

        $isRecipient = $letter->recipients()->where('user_id', $user->id)->exists();

        if ($letter->created_by !== $user->id && ! $isRecipient && ! $user->isSuperAdmin()) {
            abort(403, 'You are not authorised to access this file.');
        }

        if (! $attachment->isAvailable() || ! Storage::disk('local')->exists($attachment->file_path)) {
            abort(404, 'This file has been automatically removed (files are purged 30 days after upload).');
        }
    }

    private function authorizeOwner(Request $request, Letter $letter): void
    {
        if ($letter->created_by !== $request->user()->id && ! $request->user()->isSuperAdmin()) {
            abort(403, 'You may only view letters you shared yourself.');
        }
    }

    /**
     * Delete a letter — only the creator may do this, and only while ALL
     * recipients have not yet read it. Once even one person opens the
     * letter the option is removed, as the content has already been seen.
     */

    /**
     * Disable a letter — replaces hard-delete.
     * A letter may only be disabled if NO recipient has read it yet.
     * Once any recipient opens it, the record is permanent (audit trail).
     */
    public function destroy(Request $request, Letter $letter)
    {
        $this->authorizeOwner($letter, $request->user());

        if ($letter->recipients()->where('is_read', true)->exists()) {
            return back()->with('error',
                'Cannot disable this letter — at least one recipient has already read it. ' .
                'The record is preserved for audit purposes.'
            );
        }

        $request->validate([
            'disable_reason' => ['nullable','string','max:500'],
        ]);

        $letter->disableRecord(
            $request->user()->id,
            $request->input('disable_reason', 'Withdrawn by originating officer')
        );

        return redirect()->route('letters.index')
            ->with('success', 'Letter has been withdrawn. Recipients will no longer be able to access it.');
    }

    /** Super Admin can view and re-enable disabled letters for audit purposes. */
    public function restore(Request $request, Letter $letter)
    {
        if (! $request->user()->isSuperAdmin()) {
            abort(403, 'Only Super Admin can restore a withdrawn letter.');
        }

        $letter->enableRecord($request->user()->id);
        return back()->with('success', 'Letter restored and visible to recipients again.');
    }
}
