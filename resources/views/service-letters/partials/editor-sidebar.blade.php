<aside class="sec-side">
    <section class="sec-panel">
        <div class="sec-tabs">
            <button class="sec-tab active" data-tab="collaboration" type="button">Collaboration</button>
            <button class="sec-tab" data-tab="governance" type="button">Governance</button>
            <button class="sec-tab" data-tab="history" type="button">History</button>
        </div>
        <div class="sec-panel-body sec-tab-pane active" data-pane="collaboration">
            <div style="font-weight:700;margin-bottom:10px;">Currently in this document</div>
            <div id="presenceList">
                @forelse($activeEditors as $presence)
                    <div class="sec-person">
                        <span class="sec-avatar">{{ strtoupper(substr($presence->user?->name ?? 'U', 0, 2)) }}</span>
                        <div>
                            <strong style="font-size:12px;">{{ $presence->user?->name ?? 'User' }}</strong>
                            <div class="sec-muted">Active now</div>
                        </div>
                    </div>
                @empty
                    <div class="sec-muted">Presence updates when the workspace is open.</div>
                @endforelse
            </div>
            <div style="font-weight:700;margin:16px 0 6px;">Review comments <span
                    class="sec-muted">({{ $openComments->count() }} open)</span>
            </div>
            <div id="commentsList">
                @forelse($serviceLetter->comments as $comment)
                    <div class="sec-comment" data-comment-id="{{ $comment->id }}"
                        style="{{ $comment->is_resolved ? 'opacity:.58;' : '' }}">
                        <div class="sec-comment-meta">
                            <strong>{{ $comment->user?->name ?? 'User' }}</strong> ·
                            {{ $comment->created_at?->diffForHumans() }} @if ($comment->is_resolved)
                                · Resolved
                            @endif
                        </div>
                        <div class="sec-comment-body">{{ $comment->body }}</div>
                        @if (!$comment->is_resolved && $serviceLetter->status !== 'approved')
                            <button type="button" class="md-btn md-btn--text js-resolve-comment"
                                data-id="{{ $comment->id }}" style="padding:4px 0;margin-top:4px;">Resolve</button>
                        @endif
                    </div>
                @empty
                    <div class="sec-muted" id="noComments">No comments yet.</div>
                @endforelse
            </div>
            <div class="sec-form-field" style="margin-top:12px;">
                <label for="newComment">Add a review comment</label>
                <textarea id="newComment" rows="3" maxlength="1500"
                    placeholder="Comment on content, reference, legal basis, recipient or supporting evidence...">
        </textarea>
            </div>
            <button type="button" id="addComment" class="md-btn md-btn--outlined" style="width:100%;">Add
                Comment</button>
        </div>
        <div class="sec-panel-body sec-tab-pane" data-pane="governance">
            <div class="sec-alert info" style="margin-bottom:12px;">This panel makes the safeguards explicit before the
                document moves into review. It is not legal advice; use the institution's approved policies and records
                schedule.</div>
            <div class="sec-form-field">
                <label>Document classification *</label>
                <select id="document_classification" @disabled($readonly)>
                    <option value="internal" @selected($classification === 'internal')>Internal</option>
                    <option value="confidential" @selected($classification === 'confidential')>Confidential</option>
                    <option value="restricted" @selected($classification === 'restricted')>Restricted / Need-to-know</option>
                    <option value="public" @selected($classification === 'public')>Public</option>
                </select>
            </div>
            <label class="sec-check" style="cursor:pointer;">
                <input id="contains_personal_data" type="checkbox" style="margin-top:3px;" @checked($serviceLetter->contains_personal_data)
                    @disabled($readonly)>
                <span>
                    <strong>Contains personal data</strong>
                    <br>
                    <span class="sec-muted">Keep access limited to staff with a legitimate work need.</span>
                </span>
            </label>
            <div class="sec-form-field">
                <label>Access / handling note</label>
                <textarea id="access_note" rows="2" maxlength="300" @readonly($readonly)
                    placeholder="Example: HR use only; do not forward outside authorised workflow.">{{ $serviceLetter->access_note }}</textarea>
            </div>
            <div style="font-weight:700;margin-top:14px;">Safeguards applied</div>
            <div class="sec-check">
                <i>✓</i>
                <span>
                    <strong>Need-to-know access</strong>
                    <br>
                    <span class="sec-muted">Viewer and editor permissions are role/scope checked server-side.</span>
                </span>
            </div>
            <div class="sec-check">
                <i>✓</i>
                <span>
                    <strong>Version preservation</strong>
                    <br>
                    <span class="sec-muted">Every changed autosave creates a recoverable revision with a SHA-256
                        snapshot hash.</span>
                </span>
            </div>
            <div class="sec-check">
                <i>✓</i>
                <span>
                    <strong>Conflict protection</strong>
                    <br>
                    <span class="sec-muted">Optimistic locking prevents silent overwrites when a newer version
                        exists.</span>
                </span>
            </div>
            <div class="sec-check">
                <i>✓</i>
                <span>
                    <strong>Approval integrity</strong>
                    <br>
                    <span class="sec-muted">Approved content is hashed and the official content fields become
                        immutable.</span>
                </span>
            </div>
            <div class="sec-check">
                <i>✓</i>
                <span>
                    <strong>Audit without duplicating letter text</strong>
                    <br>
                    <span class="sec-muted">Editor events are logged without copying full potentially-sensitive content
                        into the audit log.</span>
                </span>
            </div>
            <div class="sec-check {{ $serviceLetter->reference_no ? '' : 'warn' }}" id="referenceCheck">
                <i>{{ $serviceLetter->reference_no ? '✓' : '!' }}</i>
                <span>
                    <strong>Official reference number</strong>
                    <br>
                    <span class="sec-muted">Required before the document can enter review.</span>
                </span>
            </div>
            <div class="sec-check">
                <i>✓</i>
                <span>
                    <strong>Retention category</strong>
                    <br>
                    <span class="sec-muted">Official record. No delete control is exposed in the editor.</span>
                </span>
            </div>
        </div>
        <div class="sec-panel-body sec-tab-pane" data-pane="history">
            <div class="sec-alert info" style="margin-bottom:10px;">Restoring never deletes history. A restoration
                becomes a new revision.</div>
            @forelse($serviceLetter->revisions->take(20) as $revision)
                <div class="sec-rev">
                    <span class="sec-rev-no">v{{ $revision->version_number }}</span>
                    <div>
                        <strong>{{ $revision->author?->name ?? 'User' }}</strong>
                        <div class="sec-muted">{{ $revision->created_at?->format('d M Y H:i:s') }} ·
                            {{ str_replace('_', ' ', $revision->change_reason) }}</div>
                        <div class="sec-muted">Hash {{ substr($revision->snapshot_hash, 0, 10) }}…</div>
                    </div>
                    @if ($canEdit && $revision->snapshot_hash !== $serviceLetter->contentHash())
                        <form method="POST"
                            action="{{ route('service-letters.revisions.restore', [$serviceLetter, $revision]) }}"
                            onsubmit="return confirm('Restore this revision as a NEW version? Current history will remain available.');">
                            @csrf
                            <button class="md-btn md-btn--text" type="submit">Restore</button>
                        </form>
                    @else
                        <span>
                        </span>
                    @endif
                </div>
            @empty
                <div class="sec-muted">No version snapshots yet.</div>
            @endforelse
        </div>
    </section>
    <section class="sec-panel">
        <div class="sec-panel-head">
            <span>Workflow</span>
            <span class="sec-muted">{{ ucwords(str_replace('_', ' ', $serviceLetter->status)) }}</span>
        </div>
        <div class="sec-panel-body">
            <div class="sec-check">
                <i>✓</i>
                <span>
                    <strong>Draft created</strong>
                    <br>
                    <span class="sec-muted">{{ $serviceLetter->draftedBy?->name ?? 'Originating officer' }}</span>
                </span>
            </div>
            <div
                class="sec-check {{ in_array($serviceLetter->status, ['pending_approval', 'approved']) ? '' : 'warn' }}">
                <i>{{ in_array($serviceLetter->status, ['pending_approval', 'approved']) ? '✓' : '2' }}</i>
                <span>
                    <strong>Under review</strong>
                    <br>
                    <span class="sec-muted">Editing locks after submission.</span>
                </span>
            </div>
            <div class="sec-check {{ $serviceLetter->status === 'approved' ? '' : 'warn' }}">
                <i>{{ $serviceLetter->status === 'approved' ? '✓' : '3' }}</i>
                <span>
                    <strong>Approved & e-signed</strong>
                    <br>
                    <span class="sec-muted">Approved text becomes immutable.</span>
                </span>
            </div>
        </div>
    </section>
</aside>
