@extends('layouts.app')
@section('title', 'Live Letter Workspace')

@php
    $featureOn = \App\Services\FeatureToggleService::enabled('live_letter_editing');
    $myReviewer = $row && $row->canReview();
    $openComments = $letter->comments->where('is_resolved', false)->where('comment_type', 'comment');
    $openSuggestions = $letter->comments->where('is_resolved', false)->where('comment_type', 'suggestion');
    $workflow = [
        'draft' => 'Draft',
        'editing' => 'In Edit',
        'in_review' => 'In Review',
        'returned' => 'Returned',
        'approved' => 'Approved',
        'issued' => 'Issued',
        'archived' => 'Archived',
    ];
    $workflowOrder = ['draft', 'editing', 'in_review', 'returned', 'approved', 'issued', 'archived'];
    $currentIndex = array_search($letter->workflow_status, $workflowOrder, true);
    $officialHeadData = $letter->officialLetterheadData();
    $officialHead = $officialHeadData !== [] ? (object) $officialHeadData : null;
@endphp

@push('head')
    <link rel="stylesheet" href="{{ asset('css/letter-sharing-workspace.css') }}?v=20260924">
@endpush

@section('content')
    <div class="live-letter-workspace" data-autosave-url="{{ route('letters.workspace.autosave', $letter) }}"
        data-heartbeat-url="{{ route('letters.workspace.heartbeat', $letter) }}"
        data-comment-url="{{ route('letters.workspace.comments', $letter) }}" data-can-edit="{{ $canEdit ? '1' : '0' }}"
        data-lock-version="{{ (int) $letter->editor_lock_version }}">

        @if (!$featureOn)
            <div class="ll-feature-note"
                style="background:var(--md-warning-container);border-color:var(--md-warning);color:var(--md-on-warning-container);">
                <strong>Live Letter Editing is currently disabled by Super Admin.</strong>
                This governed record remains available in read-only mode. Existing comments, versions, decisions and audit
                evidence are preserved.
            </div>
        @endif

        <div class="llw-top">
            <div class="llw-title">
                <div class="llw-title-row">
                    @if ($canEdit)
                        <input id="title" class="llw-title-input" value="{{ $letter->title }}" maxlength="200"
                            aria-label="Letter title">
                    @else
                        <span class="llw-title-input" style="display:inline-block;">{{ $letter->title }}</span>
                        <input id="title" type="hidden" value="{{ $letter->title }}">
                    @endif
                    <span class="llw-live">LIVE WORKSPACE</span>
                    <span
                        class="ll-status ll-status--{{ $letter->workflow_status }}">{{ $workflow[$letter->workflow_status] ?? ucfirst($letter->workflow_status) }}</span>
                </div>
                <div class="ll-subtitle">
                    Governed Letter Sharing · {{ $letter->reference_no ?: 'Reference not assigned' }}
                    @if ($letter->review_due_date)
                        · Review due {{ $letter->review_due_date->format('d M Y') }}
                    @endif
                </div>
            </div>

            <div class="llw-top-actions">
                <div id="presenceAvatars" class="llw-presence" title="People currently viewing this letter"></div>
                @if (
                    $letter->workflow_status === \App\Models\Letter::STATUS_APPROVED &&
                        (auth()->id() === $letter->created_by || auth()->user()->isSuperAdmin()))
                    <form method="POST" action="{{ route('letters.workspace.issue', $letter) }}">@csrf
                        <button class="llw-primary" type="submit">Issue Letter</button>
                    </form>
                @elseif(auth()->id() === $letter->created_by && $letter->isContentEditable() && $featureOn)
                    <form method="POST" action="{{ route('letters.workspace.submit-review', $letter) }}">@csrf
                        <button class="llw-primary" type="submit">Submit for Review</button>
                    </form>
                @elseif(auth()->id() === $letter->created_by &&
                        $letter->workflow_status === \App\Models\Letter::STATUS_RETURNED &&
                        $featureOn)
                    <form method="POST" action="{{ route('letters.workspace.reopen', $letter) }}">@csrf
                        <button class="llw-primary" type="submit">Reopen for Correction</button>
                    </form>
                @endif
                @if (in_array(
                        $letter->workflow_status,
                        [\App\Models\Letter::STATUS_APPROVED, \App\Models\Letter::STATUS_ISSUED, \App\Models\Letter::STATUS_ARCHIVED],
                        true))
                    <a class="llw-secondary" href="{{ route('letters.export.pdf', $letter) }}">Export Final PDF</a>
                    <a class="llw-secondary" href="{{ route('letters.export.docx', $letter) }}">Export Final DOCX</a>
                @endif
                <a class="llw-secondary"
                    href="{{ auth()->user()->isSubjectOfficer() ? route('letters.index') : route('letter-reviews.index') }}">Back
                    to Letters</a>
            </div>
        </div>

        <div class="llw-menu" aria-label="Document menu">
            <button type="button" data-action="save">File</button>
            <button type="button" data-action="undo">Edit</button>
            <button type="button" data-action="find">View / Find</button>
            <button type="button" data-action="insert-date">Insert Date</button>
            <button type="button" data-action="copy">Copy</button>
            <button type="button" onclick="window.print()">Print</button>
            <button type="button" data-side-tab="info">Governance</button>
            <button type="button" data-side-tab="versions">Version History</button>
        </div>

        <div class="llw-format" aria-label="Letter editing toolbar">
            <button type="button" data-action="undo" title="Undo">↶</button>
            <button type="button" data-action="redo" title="Redo">↷</button>
            <span class="llw-divider"></span>
            <button type="button" data-action="bullet" title="Bulleted list">• List</button>
            <button type="button" data-action="number" title="Numbered list">1. List</button>
            <button type="button" data-action="indent" title="Indent">→|</button>
            <button type="button" data-action="outdent" title="Outdent">|←</button>
            <span class="llw-divider"></span>
            <button type="button" data-action="suggest" title="Create a suggestion from selected text">Suggest</button>
            <button type="button" data-action="comment" title="Comment on selected text">Comment</button>
            <span style="margin-left:auto;font-size:11px;color:var(--ll-muted);white-space:nowrap;">Ctrl+S save · Ctrl+F
                find</span>
        </div>

        <div class="llw-grid">
            <main class="llw-canvas-area">
                <article class="llw-paper" aria-label="Official letter canvas">
                    <header class="llw-paper-head">
                        @if ($officialHead?->logo_path)
                            <img src="{{ asset('storage/' . $letter->letterhead->logo_path) }}"
                                alt="Official letterhead logo">
                        @else
                            <img src="{{ asset('images/branding/sri-lanka-emblem.png') }}" alt="Sri Lanka emblem">
                        @endif
                        <strong>{{ $officialHead?->institution_name ?? 'Official Correspondence' }}</strong>
                        <small>
                            {{ collect([$officialHead?->department_name, $officialHead?->ministry_name])->filter()->implode(' · ') ?:'Carder Management · Ministry of Health – Sri Lanka' }}
                        </small>
                        @if ($officialHead?->address_line_1 || $officialHead?->address_line_2)
                            <small>{{ collect([$officialHead?->address_line_1, $officialHead?->address_line_2])->filter()->implode(', ') }}</small>
                        @endif
                    </header>

                    <div class="llw-meta-row">
                        <label>Our Ref:
                            @if ($canEdit)
                                <input id="reference_no" value="{{ $letter->reference_no }}" maxlength="100"
                                    placeholder="e.g. HR/2026/001">
                            @else
                                <strong>{{ $letter->reference_no ?: '—' }}</strong><input id="reference_no"
                                    type="hidden" value="{{ $letter->reference_no }}">
                            @endif
                        </label>
                        <label style="justify-content:flex-end;">Date:
                            <strong>{{ now()->format('d F Y') }}</strong></label>
                    </div>

                    @if ($canEdit)
                        <textarea id="live_content" class="llw-editor" spellcheck="true" aria-label="Live letter content">{{ $letter->live_content }}</textarea>
                    @else
                        <div class="llw-readonly">{{ $letter->live_content }}</div>
                        <textarea id="live_content" hidden>{{ $letter->live_content }}</textarea>
                    @endif
                    <div id="selectionNote" class="llw-selection-note"></div>
                </article>
            </main>

            <aside class="llw-side">
                <div class="llw-side-tabs">
                    <button class="llw-side-tab active" data-panel="comments">Comments <span
                            class="ll-count">{{ $openComments->count() }}</span></button>
                    <button class="llw-side-tab" data-panel="suggestions">Suggestions <span
                            class="ll-count">{{ $openSuggestions->count() }}</span></button>
                    <button class="llw-side-tab" data-panel="info">Document Info</button>
                    <button class="llw-side-tab" data-panel="versions">Versions</button>
                </div>

                <section id="panel-comments" class="llw-panel active">
                    @forelse($letter->comments->where('comment_type','comment') as $comment)
                        <div class="llw-comment {{ $comment->is_resolved ? 'llw-comment--resolved' : '' }}">
                            <div class="llw-comment-head">
                                <strong>{{ $comment->user?->name }}</strong><span>{{ $comment->created_at->format('d M H:i') }}</span>
                            </div>
                            @if ($comment->quoted_text)
                                <div class="llw-quote">“{{ \Illuminate\Support\Str::limit($comment->quoted_text, 260) }}”
                                </div>
                            @endif
                            <div class="llw-comment-body">{{ $comment->body }}</div>
                            @if ($comment->is_resolved)
                                <div class="ll-status ll-status--approved" style="margin-top:7px;">Resolved</div>
                            @elseif(auth()->id() === $letter->created_by || auth()->id() === $comment->user_id || auth()->user()->isSuperAdmin())
                                <form class="llw-comment-actions" method="POST"
                                    action="{{ route('letters.workspace.comments.resolve', [$letter, $comment]) }}">
                                    @csrf<button type="submit">Resolve</button></form>
                            @endif
                        </div>
                    @empty
                        <div class="ll-subtitle">No comments yet. Select text in the letter and choose
                            <strong>Comment</strong>, or add a general comment below.
                        </div>
                    @endforelse
                </section>

                <section id="panel-suggestions" class="llw-panel">
                    <div class="ll-feature-note" style="font-size:11px;margin-bottom:9px;">Suggestions never change
                        official text automatically. An authorised editor must decide whether to apply them.</div>
                    @forelse($letter->comments->where('comment_type','suggestion') as $comment)
                        <div class="llw-comment {{ $comment->is_resolved ? 'llw-comment--resolved' : '' }}">
                            <div class="llw-comment-head"><strong>{{ $comment->user?->name }} <span
                                        class="llw-comment-type">SUGGESTION</span></strong><span>{{ $comment->created_at->format('d M H:i') }}</span>
                            </div>
                            @if ($comment->quoted_text)
                                <div class="llw-quote">“{{ \Illuminate\Support\Str::limit($comment->quoted_text, 260) }}”
                                </div>
                            @endif
                            <div class="llw-comment-body">{{ $comment->body }}</div>
                            @if ($comment->is_resolved)
                                <div class="ll-status ll-status--approved" style="margin-top:7px;">Resolved</div>
                            @elseif(auth()->id() === $letter->created_by || auth()->id() === $comment->user_id || auth()->user()->isSuperAdmin())
                                <form class="llw-comment-actions" method="POST"
                                    action="{{ route('letters.workspace.comments.resolve', [$letter, $comment]) }}">
                                    @csrf<button type="submit">Resolve</button></form>
                            @endif
                        </div>
                    @empty
                        <div class="ll-subtitle">No suggestions yet.</div>
                    @endforelse
                </section>

                <section id="panel-info" class="llw-panel">
                    <div class="llw-info-card">
                        <h3>Letter Details</h3>
                        <label class="llw-info-line"><span>Official Letter Header</span>
                            @if ($canEdit)
                                <select id="letterhead_id" class="ll-select" style="width:190px;height:30px;">
                                    <option value="">System default / none</option>
                                    @foreach ($letterheads as $letterhead)
                                        <option value="{{ $letterhead->id }}" @selected((int) $letter->letterhead_id === (int) $letterhead->id)>
                                            {{ $letterhead->name }}{{ $letterhead->is_default ? ' — Default' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <strong>{{ $officialHead?->name ?? 'System default / none' }}</strong>
                                <input id="letterhead_id" type="hidden" value="{{ $letter->letterhead_id }}">
                            @endif
                        </label>
                        <label class="llw-info-line"><span>Classification</span>
                            @if ($canEdit)
                                <select id="classification" class="ll-select" style="width:150px;height:30px;">
                                    @foreach (['internal' => 'Internal', 'confidential' => 'Confidential', 'restricted' => 'Restricted'] as $v => $l)
                                        <option value="{{ $v }}" @selected($letter->document_classification === $v)>
                                            {{ $l }}</option>
                                    @endforeach
                                </select>
                            @else
                                <strong>{{ ucfirst($letter->document_classification) }}</strong><input id="classification"
                                    type="hidden" value="{{ $letter->document_classification }}">
                            @endif
                        </label>
                        <label class="llw-info-line"><span>Contains personal data</span>
                            @if ($canEdit)
                                <input id="personal_data" type="checkbox"
                                {{ $letter->contains_personal_data ? 'checked' : '' }}>@else<strong>{{ $letter->contains_personal_data ? 'Yes' : 'No' }}</strong><input
                                    id="personal_data" type="checkbox" hidden
                                    {{ $letter->contains_personal_data ? 'checked' : '' }}>
                            @endif
                        </label>
                        <label class="llw-info-line"><span>Retention</span>
                            @if ($canEdit)
                                <select id="retention" class="ll-select" style="width:160px;height:30px;">
                                    @foreach (['official_correspondence' => 'Official correspondence', 'hr_record' => 'HR record', 'administrative_record' => 'Administrative record', 'temporary_working_record' => 'Temporary working record'] as $v => $l)
                                        <option value="{{ $v }}" @selected($letter->retention_category === $v)>
                                            {{ $l }}</option>
                                    @endforeach
                                </select>
                            @else<strong>{{ str_replace('_', ' ', ucfirst($letter->retention_category)) }}</strong><input
                                    id="retention" type="hidden" value="{{ $letter->retention_category }}">
                            @endif
                        </label>
                        <label class="llw-info-line"><span>Review due</span>
                            @if ($canEdit)
                                <input id="review_due_date" type="date"
                                    value="{{ optional($letter->review_due_date)->format('Y-m-d') }}"
                                style="border:1px solid var(--ll-border);border-radius:6px;padding:4px;background:transparent;color:inherit;">@else<strong>{{ optional($letter->review_due_date)->format('d M Y') ?: '—' }}</strong><input
                                    id="review_due_date" type="hidden"
                                    value="{{ optional($letter->review_due_date)->format('Y-m-d') }}">
                            @endif
                        </label>
                        <div style="margin-top:8px;"><span style="font-size:11px;color:var(--ll-muted)">Handling / access
                                note</span>
                            @if ($canEdit)
                            <textarea id="access_note" class="ll-select" style="height:70px;padding:8px;margin-top:4px;">{{ $letter->access_note }}</textarea>@else<div
                                    style="font-size:11px;margin-top:4px;white-space:pre-wrap">
                                    {{ $letter->access_note ?: 'No special handling note.' }}</div>
                                <textarea id="access_note" hidden>{{ $letter->access_note }}</textarea>
                            @endif
                        </div>
                    </div>

                    <div class="llw-info-card">
                        <h3>People & Permissions</h3>
                        <div class="llw-info-line"><span>{{ $letter->createdBy?->name }}</span><strong>Owner</strong>
                        </div>
                        @foreach ($letter->recipients as $recipient)
                            <div class="llw-info-line">
                                <span>{{ $recipient->user?->name }}<br><small>{{ $recipient->is_read ? 'Read ' . optional($recipient->read_at)->format('d M H:i') : 'Not yet read' }}</small></span><strong>{{ ucfirst($recipient->access_level) }}</strong>
                            </div>
                        @endforeach
                    </div>

                    <div
                        class="llw-governance {{ $letter->document_classification === 'restricted' ? 'llw-warning' : '' }}">
                        <strong>Governance safeguard</strong><br>
                        Access is identity-based only. No public/anonymous share link is generated. Review submission locks
                        content; approved/issued records are immutable and integrity hashed. Final PDF/DOCX exports are
                        available only after approval and are audit logged. If the final approver has a registered
                        e-signature, that signature is bound to the approved record and included in final exports.
                    </div>
                </section>

                <section id="panel-versions" class="llw-panel">
                    <div class="ll-feature-note" style="font-size:11px;">Restoring a version creates a new governed
                        revision; it never erases history.</div>
                    @foreach ($letter->revisions as $revision)
                        <div class="llw-version">
                            <div class="llw-version-row">
                                <strong>v{{ $revision->version_number }}</strong><span>{{ $revision->created_at->format('d M H:i') }}</span>
                            </div>
                            <small>{{ $revision->author?->name }} ·
                                {{ str_replace('_', ' ', $revision->change_reason ?: 'saved') }}</small>
                            @if ($canEdit && $featureOn)
                                <form method="POST"
                                    action="{{ route('letters.workspace.revisions.restore', [$letter, $revision]) }}"
                                    style="margin-top:6px;"
                                    onsubmit="return confirm('Restore this revision as a new version? Existing history will remain preserved.');">
                                    @csrf
                                    <button class="llw-secondary" style="height:28px;font-size:10px"
                                        type="submit">Restore as New Version</button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </section>

                @if ($canComment)
                    <div class="llw-compose">
                        <textarea id="commentBody" maxlength="3000" placeholder="Add a comment or suggestion…"></textarea>
                        <input id="quotedText" type="hidden" value="">
                        <div class="llw-compose-row">
                            <select id="commentType">
                                <option value="comment">Comment</option>
                                <option value="suggestion">Suggestion</option>
                            </select>
                            <button id="commentBtn" class="llw-primary" type="button" style="height:32px">Post</button>
                        </div>
                    </div>
                @endif
            </aside>
        </div>

        <div class="llw-statusbar">
            <span id="saveState">{{ $canEdit ? 'Saved' : 'Read-only governed record' }}</span>
            <span><span id="wordCount">0</span> words · v{{ $letter->revisions->first()?->version_number ?? 1 }} ·
                {{ ucfirst($letter->document_classification) }}</span>
        </div>

        <div class="llw-bottom">
            <section class="llw-bottom-card">
                <h3>Workflow — Letter Lifecycle</h3>
                <div class="llw-timeline">
                    @foreach ($workflowOrder as $i => $state)
                        @php
                            $done = $currentIndex !== false && $i < $currentIndex;
                            $current = $letter->workflow_status === $state;
                        @endphp
                        <div class="llw-step {{ $done ? 'done' : '' }} {{ $current ? 'current' : '' }}">
                            <strong>{{ $workflow[$state] }}</strong>
                            <div class="ll-subtitle">
                                @switch($state)
                                    @case('draft')
                                        Created as an official working draft.
                                    @break

                                    @case('editing')
                                        Authorised editors collaborate; autosaved versions are preserved.
                                    @break

                                    @case('in_review')
                                        Content locked while designated reviewers decide.
                                    @break

                                    @case('returned')
                                        Changes requested; prior review evidence remains preserved.
                                    @break

                                    @case('approved')
                                        All required reviewers approved; integrity hash sealed.
                                    @break

                                    @case('issued')
                                        Official issued copy becomes read-only.
                                    @break

                                    @case('archived')
                                        Retained under records policy.
                                    @break
                                @endswitch
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($myReviewer && $letter->workflow_status === \App\Models\Letter::STATUS_IN_REVIEW)
                    <form method="POST" action="{{ route('letter-reviews.update', $letter) }}"
                        style="display:grid;gap:8px;margin-top:10px;">@csrf
                        <label style="font-size:12px;font-weight:700">Your formal review decision</label>
                        <select name="status" class="ll-select" required>
                            <option value="reviewed">Reviewed — decision pending</option>
                            <option value="approved">Approve</option>
                            <option value="rejected">Return for correction</option>
                        </select>
                        <textarea name="remarks" class="ll-select" style="height:74px;padding:8px"
                            placeholder="Review note / reason. A reason is required when returning for correction."></textarea>
                        <button class="llw-primary" type="submit">Record Review Decision</button>
                    </form>
                @endif
            </section>

            <section class="llw-bottom-card">
                <h3>Governance & Legal Safeguards</h3>
                <div class="llw-safeguards">
                    <div class="llw-safe"><strong>Role-based access</strong>Only named authorised users can open the
                        letter.</div>
                    <div class="llw-safe"><strong>Document classification</strong>Internal, Confidential or Restricted
                        handling is visible in the workspace.</div>
                    <div class="llw-safe"><strong>Review lock</strong>Formal submission prevents editing during approval.
                    </div>
                    <div class="llw-safe"><strong>Immutable approved record</strong>Approved/issued content cannot be
                        silently rewritten.</div>
                    <div class="llw-safe"><strong>Version & audit trail</strong>Every material save preserves author, time
                        and content hash.</div>
                    <div class="llw-safe"><strong>Read receipts</strong>Recipient access is recorded for accountability.
                    </div>
                    <div class="llw-safe"><strong>Personal-data indicator</strong>PII-bearing correspondence is explicitly
                        marked for handling.</div>
                    <div class="llw-safe"><strong>No anonymous sharing</strong>Public links are intentionally excluded from
                        this government workflow.</div>
                </div>
                @if ($letter->approved_content_hash)
                    <div class="llw-governance" style="margin-top:10px;word-break:break-all"><strong>Approved integrity
                            hash</strong><br>{{ $letter->approved_content_hash }}</div>
                @endif
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/letter-sharing-workspace.js') }}?v=20260922"></script>
@endpush
