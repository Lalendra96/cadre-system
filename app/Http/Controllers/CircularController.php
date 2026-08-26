<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Circular;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Memo/Circular/Internal Circular portal.
 *
 * index()/create()/store()/toggle() are the INTERNAL side — authenticated
 * staff only, matching every other controller in this system.
 *
 * show()/download() are the PUBLIC side — reachable with NO login, by
 * design ("anyone with the link can view"). See the circulars migration
 * docblock for the full data-security reasoning behind how that's made
 * safe: unguessable token-based resolution, private disk storage, and a
 * 404 (not a 403) for anything disabled or not found, so a public visitor
 * can never distinguish "never existed" from "was revoked."
 */
class CircularController extends Controller
{
    private const MAX_FILE_KB = 10240; // 10MB, matching letter attachments

    // ── Internal portal ──────────────────────────────────────────────────

    // ── Public portal — no authentication, read-only, advanced search ─────

    /**
     * The public browsable portal — "easy access," matching how the
     * hospital's existing intranet resources work (read-only + advanced
     * search, no login). This is DELIBERATELY separate from the internal
     * staff management view (see index() below): a public visitor gets a
     * fast, simple, always-available listing with zero auth overhead —
     * no session check, no role lookup, nothing that could fail or
     * redirect them somewhere unexpected.
     *
     * "Advanced search" = keyword (title/description), category filter,
     * date range, and sort order — all via plain query-string parameters
     * so a specific search can itself be bookmarked/shared as a URL.
     *
     * Deliberately does NOT expose `uploaded_by` on this public view (see
     * circulars.public-index.blade.php) — a staff member's name isn't
     * sensitive, but there's no reason to publish it either when nothing
     * about the request needs it.
     */
    public function publicIndex(Request $request)
    {
        $query = Circular::active();

        if ($q = $request->query('q')) {
            $query->where(function ($sub) use ($q) {
                $sub->whereRaw('title ILIKE ?', ["%{$q}%"])
                    ->orWhereRaw('description ILIKE ?', ["%{$q}%"]);
            });
        }
        if ($category = $request->query('category')) {
            $query->category($category);
        }
        if ($from = $request->query('date_from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->query('date_to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $sort = $request->query('sort', 'newest');
        match ($sort) {
            'oldest'      => $query->orderBy('created_at'),
            'most_viewed' => $query->orderByDesc('view_count'),
            default       => $query->orderByDesc('created_at'),
        };

        $circulars = $query->paginate(15)->withQueryString();

        return view('circulars.public-index', compact('circulars', 'sort'));
    }

    // ── Internal staff management — authenticated, upload/revoke ───────────

    public function index(Request $request)
    {
        $query = Circular::active()->with('uploadedBy')->orderByDesc('created_at');

        if ($category = $request->query('category')) {
            $query->category($category);
        }
        if ($q = $request->query('q')) {
            $query->where(function ($sub) use ($q) {
                $sub->whereRaw('title ILIKE ?', ["%{$q}%"])
                    ->orWhereRaw('description ILIKE ?', ["%{$q}%"]);
            });
        }

        $circulars = $query->paginate(20)->withQueryString();

        return view('circulars.manage', compact('circulars'));
    }

    public function create(Request $request)
    {
        $this->authorizeUpload($request);

        return view('circulars.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeUpload($request);

        $data = $request->validate([
            'title'       => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category'    => ['required', Rule::in(array_keys(Circular::CATEGORY_LABELS))],
            'file'        => ['required', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:' . self::MAX_FILE_KB],
        ]);

        $file = $request->file('file');
        $path = $file->storeAs('circulars', Str::random(20) . '.' . $file->extension(), 'local');

        $circular = Circular::create([
            'title'              => $data['title'],
            'description'        => $data['description'] ?? null,
            'category'           => $data['category'],
            'file_path'          => $path,
            'original_filename'  => $file->getClientOriginalName(),
            'mime_type'          => $file->getMimeType(),
            'file_size'          => $file->getSize(),
            'uploaded_by'        => $request->user()->id,
            'is_active'          => true,
        ]);

        AuditLogService::created($circular, "Uploaded {$circular->category}: \"{$circular->title}\"");

        return redirect()->route('circulars.manage')
            ->with('success', 'Uploaded. Share link is ready below.')
            ->with('new_circular_id', $circular->id);
    }

    public function toggle(Request $request, Circular $circular): RedirectResponse
    {
        abort_unless(
            $request->user()->isSuperAdmin() || $circular->uploaded_by === $request->user()->id,
            403,
            'You may only manage circulars you uploaded yourself.'
        );

        if ($circular->is_active) {
            $reason = (string) $request->input('reason', 'Revoked by uploader.');
            $circular->disableRecord($request->user()->id, $reason);
            AuditLogService::updated($circular, ['is_active' => true], "Revoked circular \"{$circular->title}\" — public link disabled.");
            $message = 'Circular revoked — the public link no longer works.';
        } else {
            $circular->enableRecord($request->user()->id);
            AuditLogService::updated($circular, ['is_active' => false], "Re-enabled circular \"{$circular->title}\".");
            $message = 'Circular re-enabled.';
        }

        return redirect()->route('circulars.manage')->with('success', $message);
    }

    /**
     * Form to pick one or more Position Groups to send this circular to.
     * Scoped to the same audience as toggle() — the uploader, or Super Admin.
     */
    public function sendForm(Request $request, Circular $circular)
    {
        $this->authorizeSend($request, $circular);

        $groupsQuery = \App\Models\PositionGroup::active()->withCount('positions')->orderBy('name');
        if (! $request->user()->isSuperAdmin()) {
            $groupsQuery->where('created_by', $request->user()->id);
        }
        $groups = $groupsQuery->get();

        $recentSends = $circular->sends()->with('sentBy')->latest()->limit(5)->get();

        return view('circulars.send', compact('circular', 'groups', 'recentSends'));
    }

    /**
     * Resolves the selected groups' positions, intersects with the SENDING
     * OFFICER's own effective subject codes (never a broader audience,
     * regardless of which groups exist — see PositionGroup model
     * docblock), and emails each employee who has an address on file.
     *
     * Sent synchronously with a per-recipient try/catch, matching this
     * codebase's existing large-batch pattern (EmployeeImportController) —
     * one bad/invalid email address never aborts the rest of the send.
     */
    public function sendToGroups(Request $request, Circular $circular): RedirectResponse
    {
        $this->authorizeSend($request, $circular);

        $data = $request->validate([
            'position_group_ids'   => ['required', 'array', 'min:1'],
            'position_group_ids.*' => ['integer', 'exists:position_groups,id'],
        ], [
            'position_group_ids.required' => 'Select at least one group to send to.',
        ]);

        $user = $request->user();
        $positionIds = \App\Models\PositionGroup::whereIn('id', $data['position_group_ids'])
            ->get()
            ->flatMap(fn ($g) => $g->positions->pluck('id'))
            ->unique();

        $employees = \App\Models\Employee::active()
            ->whereIn('subject_code_id', $user->effectiveSubjectCodeIds())
            ->whereIn('position_id', $positionIds)
            ->get();

        $sent = 0;
        $failed = 0;
        $skippedNoEmail = 0;

        foreach ($employees as $employee) {
            if (empty($employee->email)) {
                $skippedNoEmail++;
                continue;
            }
            try {
                \Illuminate\Support\Facades\Mail::to($employee->email)
                    ->send(new \App\Mail\CircularNotificationMail($circular, $employee->display_name));
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
            }
        }

        $send = \App\Models\CircularSend::create([
            'circular_id'             => $circular->id,
            'sent_by'                 => $user->id,
            'position_group_ids'      => $data['position_group_ids'],
            'recipient_count'         => $employees->count(),
            'sent_count'              => $sent,
            'failed_count'            => $failed,
            'skipped_no_email_count'  => $skippedNoEmail,
        ]);

        $circular->increment('total_sends');

        AuditLogService::created(
            $send,
            "Sent circular \"{$circular->title}\" to " . count($data['position_group_ids']) . ' group(s): '
            . "{$sent} sent, {$failed} failed, {$skippedNoEmail} skipped (no email on file)."
        );

        $message = "Sent to {$sent} of {$employees->count()} employee(s).";
        if ($skippedNoEmail > 0) {
            $message .= " {$skippedNoEmail} skipped — no email address on file.";
        }
        if ($failed > 0) {
            $message .= " {$failed} failed to send.";
        }

        return redirect()->route('circulars.send-form', $circular)->with('success', $message);
    }

    private function authorizeSend(Request $request, Circular $circular): void
    {
        abort_unless(
            $request->user()->isSuperAdmin() || $circular->uploaded_by === $request->user()->id,
            403,
            'You may only send circulars you uploaded yourself.'
        );

        abort_unless(
            $request->user()->canManageCircularGroups(),
            403,
            'You do not have permission to dispatch circulars. Contact the system administrator to enable it for your account.'
        );
    }

    private function authorizeUpload(Request $request): void
    {
        abort_unless(
            $request->user()->isSubjectOfficer() || $request->user()->isSuperAdmin() || $request->user()->isAdminGroup(),
            403,
            'You do not have permission to upload circulars.'
        );
    }

    // ── Public side — no authentication, resolved by share_token only ──────

    /**
     * Public landing page for one circular — shows title/description/
     * category and a Download button. Deliberately does NOT embed the
     * file inline (no <iframe>/<embed> of arbitrary uploaded content),
     * since that would render user-uploaded HTML-adjacent file types
     * inside this app's own origin — a real XSS vector if a malicious
     * file were ever uploaded despite mime validation. A plain download
     * link avoids that entirely.
     */
    public function show(string $token)
    {
        $circular = Circular::active()->where('share_token', $token)->first();

        // 404, not 403 — a public visitor must never be able to tell the
        // difference between "this link never existed" and "this link
        // was revoked." Both look identical from the outside.
        abort_unless($circular && $circular->fileExists(), 404);

        $circular->increment('view_count');

        return view('circulars.public-show', compact('circular'));
    }

    public function download(string $token): StreamedResponse
    {
        $circular = Circular::active()->where('share_token', $token)->first();
        abort_unless($circular && $circular->fileExists(), 404);

        return Storage::disk('local')->download($circular->file_path, $circular->original_filename);
    }
}
