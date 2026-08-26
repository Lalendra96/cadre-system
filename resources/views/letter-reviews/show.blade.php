@extends('layouts.app')
@section('title', 'Review Letter')

@push('head')
<style>
/* ── Split pane ─────────────────────────────────────────────────── */
.lr-split {
  display: grid;
  grid-template-columns: 1fr 400px;
  gap: 0;
  height: calc(100vh - 64px - 52px);
  overflow: hidden;
}

/* ── Left: viewer ───────────────────────────────────────────────── */
.lr-viewer {
  display: flex; flex-direction: column;
  background: #1a1a1f;
  border-right: 1px solid var(--md-outline-variant);
  overflow: hidden;
}

.lr-viewer__toolbar {
  display: flex; align-items: center; gap: 6px;
  padding: 6px 10px;
  background: var(--md-surface-container-high);
  border-bottom: 1px solid var(--md-outline-variant);
  flex-shrink: 0; overflow-x: auto;
}

.lr-viewer__tab {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 5px 12px; border-radius: var(--md-shape-sm);
  font-size: 12px; font-weight: 500;
  color: var(--md-on-surface-variant);
  background: transparent; border: 1px solid transparent;
  cursor: pointer; white-space: nowrap;
  max-width: 200px; overflow: hidden; text-overflow: ellipsis;
  transition: background .12s ease;
}
.lr-viewer__tab:hover    { background: color-mix(in srgb,var(--md-on-surface) 8%,transparent); }
.lr-viewer__tab--active  { background: var(--md-secondary-container); color: var(--md-on-secondary-container); }

/* Spacer to push action buttons right */
.lr-viewer__spacer { flex: 1; }

.lr-viewer__btn {
  display: inline-flex; align-items: center; gap: 4px;
  padding: 4px 10px; height: 30px; border-radius: var(--md-shape-sm);
  font-size: 12px; background: transparent;
  border: 1px solid var(--md-outline-variant);
  color: var(--md-on-surface-variant); cursor: pointer;
  text-decoration: none; white-space: nowrap;
  transition: background .12s;
}
.lr-viewer__btn:hover { background: color-mix(in srgb,var(--md-on-surface) 10%,transparent); }

/* ── Frame area ─────────────────────────────────────────────────── */
.lr-viewer__stage {
  flex: 1; position: relative; overflow: hidden;
}

/* Each file panel — hidden until activated */
.lr-panel-slot {
  position: absolute; inset: 0;
  display: none; flex-direction: column;
  align-items: center; justify-content: center;
}
.lr-panel-slot--active { display: flex; }

/* Loading overlay */
.lr-loading {
  position: absolute; inset: 0; z-index: 10;
  display: flex; flex-direction: column;
  align-items: center; justify-content: center; gap: 14px;
  background: #1a1a1f;
  transition: opacity .3s ease;
}
.lr-loading.hidden { opacity: 0; pointer-events: none; }
.lr-spinner {
  width: 36px; height: 36px;
  border: 3px solid var(--md-outline-variant);
  border-top-color: var(--md-primary);
  border-radius: 50%;
  animation: spin .8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* PDF embed — fills parent */
.lr-pdf-embed {
  width: 100%; height: 100%; border: none; display: block;
}

/* Image viewer */
.lr-img-wrap {
  width: 100%; height: 100%;
  overflow: auto; padding: 16px;
  display: flex; align-items: flex-start; justify-content: center;
}
.lr-img {
  max-width: 100%; height: auto;
  border-radius: 4px;
  box-shadow: 0 4px 24px rgba(0,0,0,.5);
  cursor: zoom-in;
}
.lr-img.zoomed { max-width: none; cursor: zoom-out; }

/* Error / unsupported */
.lr-error {
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  gap: 14px; padding: 32px;
  color: var(--md-on-surface-variant); text-align: center;
}
.lr-error-icon { font-size: 3rem; }

/* ── Right: review panel ────────────────────────────────────────── */
.lr-panel {
  display: flex; flex-direction: column;
  overflow-y: auto;
  background: var(--md-surface-container-low);
}
.lr-section {
  padding: 16px 20px;
  border-bottom: 1px solid var(--md-outline-variant);
}
.lr-section:last-child { border-bottom: none; }
.lr-section__label {
  font-size: 11px; font-weight: 600;
  text-transform: uppercase; letter-spacing: .8px;
  color: var(--md-on-surface-variant); margin-bottom: 10px;
}

/* Recipient row */
.lr-recipient-row {
  display: flex; align-items: center; gap: 8px; padding: 5px 0;
}
.lr-recipient-row__name { flex: 1; font-size: 13px; }

@media (max-width:900px) {
  .lr-split { grid-template-columns:1fr; height:auto; overflow:visible; }
  .lr-viewer { height: 65vh; }
  .lr-panel  { max-height: none; }
}
</style>
@endpush

@section('content')

{{-- Page header --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;flex-wrap:wrap;gap:8px;">
    <div>
        <h2 class="md-headline-sm" style="margin-bottom:2px;">{{ $letter->title }}</h2>
        <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
            Shared by <strong>{{ $letter->createdBy->name ?? '—' }}</strong>
            &middot; {{ $letter->created_at->format('d M Y, h:i A') }}
            @if($letter->description)
                &middot; {{ $letter->description }}
            @endif
        </p>
    </div>
    <a href="{{ route('letter-reviews.index') }}" class="md-btn md-btn--text">← Back</a>
</div>

<div class="lr-split">

    {{-- ════════════════════════════════════════════════════════════
         LEFT — File Viewer
         ═══════════════════════════════════════════════════════════ --}}
    <div class="lr-viewer">

        {{-- Toolbar: file tabs + action buttons --}}
        <div class="lr-viewer__toolbar" id="lrToolbar">
            @forelse($letter->attachments as $i => $a)
                @php
                    $ext   = strtolower(pathinfo($a->original_filename, PATHINFO_EXTENSION));
                    $icon  = $ext === 'pdf' ? '📄' : (in_array($ext, ['jpg','jpeg','png']) ? '🖼' : '📎');
                @endphp
                <button class="lr-viewer__tab {{ $i === 0 ? 'lr-viewer__tab--active' : '' }}"
                        id="tab-{{ $i }}"
                        onclick="switchSlot({{ $i }})"
                        title="{{ $a->original_filename }}">
                    {{ $icon }} {{ \Illuminate\Support\Str::limit($a->original_filename, 22) }}
                </button>
            @empty
                <span class="md-body-sm" style="color:var(--md-on-surface-variant);padding:4px 8px;">No files attached</span>
            @endforelse

            <span class="lr-viewer__spacer"></span>

            {{-- Per-active-file action buttons — swapped by JS --}}
            <div id="lrActions">
                @foreach($letter->attachments as $i => $a)
                    <div class="lr-file-actions" id="actions-{{ $i }}" style="{{ $i > 0 ? 'display:none;' : '' }}">
                        @if($a->isAvailable())
                            @php($ext = strtolower(pathinfo($a->original_filename, PATHINFO_EXTENSION)))
                            @if($ext === 'pdf')
                                <button class="lr-viewer__btn" onclick="printSlot({{ $i }})" title="Print">🖨 Print</button>
                            @endif
                            <a href="{{ route('letters.attachments.download', [$letter, $a]) }}"
                               class="lr-viewer__btn" title="Download" download>⬇ Download</a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Stage: one slot per attachment --}}
        <div class="lr-viewer__stage" id="lrStage">
            @forelse($letter->attachments as $i => $a)
                @php
                    $ext      = strtolower(pathinfo($a->original_filename, PATHINFO_EXTENSION));
                    $isPdf    = $ext === 'pdf';
                    $isImage  = in_array($ext, ['jpg','jpeg','png']);
                    $isWord   = in_array($ext, ['doc','docx']);
                    $previewUrl = $a->isAvailable()
                        ? route('letters.attachments.preview', [$letter, $a])
                        : null;
                @endphp

                <div class="lr-panel-slot {{ $i === 0 ? 'lr-panel-slot--active' : '' }}"
                     id="slot-{{ $i }}"
                     data-type="{{ $isPdf ? 'pdf' : ($isImage ? 'image' : 'other') }}"
                     data-available="{{ $a->isAvailable() ? '1' : '0' }}">

                    @if(! $a->isAvailable())
                        {{-- File has been purged --}}
                        <div class="lr-error">
                            <span class="lr-error-icon">🚫</span>
                            <p class="md-title-sm">File removed</p>
                            <p class="md-body-sm">
                                Automatically removed on {{ optional($a->file_removed_at)->format('d M Y') }}<br>
                                (30-day retention policy).
                            </p>
                        </div>

                    @elseif($isPdf)
                        {{-- Loading overlay --}}
                        <div class="lr-loading" id="loading-{{ $i }}">
                            <div class="lr-spinner"></div>
                            <span class="md-body-sm" style="color:var(--md-on-surface-variant);">Loading PDF…</span>
                        </div>

                        {{-- <object>/<embed> cascade — not restricted by X-Frame-Options --}}
                        <object id="pdfObj-{{ $i }}"
                                data="{{ $previewUrl }}"
                                type="application/pdf"
                                class="lr-pdf-embed"
                                onload="hideLoading({{ $i }})"
                                onerror="showPdfFallback({{ $i }})">
                            <embed src="{{ $previewUrl }}"
                                   type="application/pdf"
                                   class="lr-pdf-embed"
                                   onload="hideLoading({{ $i }})">
                                {{-- Text fallback inside object/embed for browsers without PDF support --}}
                                <div class="lr-error" id="pdfFallback-{{ $i }}">
                                    <span class="lr-error-icon">📄</span>
                                    <p class="md-title-sm">{{ $a->original_filename }}</p>
                                    <p class="md-body-sm" style="max-width:280px;">
                                        Your browser cannot display this PDF inline.<br>
                                        Use the Download button above to open it.
                                    </p>
                                    <a href="{{ route('letters.attachments.download', [$letter, $a]) }}"
                                       class="md-btn md-btn--tonal">⬇ Download PDF</a>
                                </div>
                            </embed>
                        </object>

                    @elseif($isImage)
                        <div class="lr-img-wrap">
                            <img src="{{ $previewUrl }}"
                                 class="lr-img"
                                 alt="{{ $a->original_filename }}"
                                 loading="lazy"
                                 onclick="this.classList.toggle('zoomed')"
                                 title="Click to zoom"
                                 onerror="this.parentElement.innerHTML='<div class=\'lr-error\'><span class=\'lr-error-icon\'>⚠️</span><p>Could not load image.</p></div>'">
                        </div>

                    @elseif($isWord)
                        <div class="lr-error">
                            <span class="lr-error-icon">📝</span>
                            <p class="md-title-sm">{{ $a->original_filename }}</p>
                            <p class="md-body-sm" style="max-width:280px;">
                                Word documents cannot be previewed in the browser.<br>
                                Download to open in Microsoft Word.
                            </p>
                            <a href="{{ route('letters.attachments.download', [$letter, $a]) }}"
                               class="md-btn md-btn--tonal">⬇ Download to Review</a>
                        </div>

                    @else
                        <div class="lr-error">
                            <span class="lr-error-icon">📎</span>
                            <p class="md-title-sm">{{ $a->original_filename }}</p>
                            <p class="md-body-sm" style="color:var(--md-on-surface-variant);">{{ $a->human_size }}</p>
                            <a href="{{ route('letters.attachments.download', [$letter, $a]) }}"
                               class="md-btn md-btn--tonal">⬇ Download</a>
                        </div>
                    @endif

                </div>{{-- /slot --}}
            @empty
                <div class="lr-panel-slot lr-panel-slot--active">
                    <div class="lr-error">
                        <span class="lr-error-icon">📭</span>
                        <p class="md-body-sm">No files were attached to this letter.</p>
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════
         RIGHT — Review Panel
         ═══════════════════════════════════════════════════════════ --}}
    <div class="lr-panel">

        {{-- Your review form --}}
        @if($myRecipientRow)
        <div class="lr-section">
            <div class="lr-section__label">Your Review</div>

            @if(session('success'))
                <div style="background:var(--md-success-container);color:var(--md-on-success-container);
                            padding:10px 14px;border-radius:var(--md-shape-sm);margin-bottom:14px;font-size:13px;">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('letter-reviews.update', $letter) }}"
                  style="display:flex;flex-direction:column;gap:14px;">
                @csrf
                <div class="md-field">
                    <label class="md-field__label">Decision <span style="color:var(--md-error)">*</span></label>
                    <select name="status" class="md-field__input @error('status') md-field--error @enderror" required>
                        @foreach(['pending'=>'Pending','reviewed'=>'Reviewed','approved'=>'Approved','rejected'=>'Rejected'] as $val => $lbl)
                            <option value="{{ $val }}" {{ old('status', $myRecipientRow->status) === $val ? 'selected' : '' }}>
                                {{ $lbl }}
                            </option>
                        @endforeach
                    </select>
                    @error('status')<div class="md-field__error">{{ $message }}</div>@enderror
                </div>
                <div class="md-field">
                    <label class="md-field__label">Remarks</label>
                    <textarea name="remarks" class="md-field__input" rows="5" style="resize:vertical;">{{ old('remarks', $myRecipientRow->remarks) }}</textarea>
                </div>
                <button type="submit" class="md-btn md-btn--filled">Save Review</button>
            </form>
        </div>
        @endif

        {{-- Files summary --}}
        <div class="lr-section">
            <div class="lr-section__label">Attachments ({{ $letter->attachments->count() }})</div>
            @foreach($letter->attachments as $i => $a)
                <div style="display:flex;align-items:center;gap:10px;padding:5px 0;cursor:pointer;"
                     onclick="switchSlot({{ $i }})">
                    <span style="font-size:1.1rem;">
                        @php($ext = strtolower(pathinfo($a->original_filename, PATHINFO_EXTENSION)))
                        {{ $ext === 'pdf' ? '📄' : (in_array($ext, ['jpg','jpeg','png']) ? '🖼' : '📎') }}
                    </span>
                    <div style="flex:1;min-width:0;">
                        <div class="md-label-md" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $a->original_filename }}</div>
                        <div class="md-body-sm" style="color:var(--md-on-surface-variant);">
                            {{ $a->human_size }}
                            @if(! $a->isAvailable())
                                &middot; <span style="color:var(--md-error);">Removed</span>
                            @endif
                        </div>
                    </div>
                    @if($a->isAvailable())
                        <a href="{{ route('letters.attachments.download', [$letter, $a]) }}"
                           class="md-btn md-btn--icon" onclick="event.stopPropagation()" title="Download" style="font-size:14px;">⬇</a>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- All recipients --}}
        <div class="lr-section">
            <div class="lr-section__label">Recipients ({{ $letter->recipients->count() }})</div>
            @foreach($letter->recipients as $r)
                <div class="lr-recipient-row">
                    <div class="lr-recipient-row__name">
                        <div class="md-label-md">{{ $r->user->name }}</div>
                        <div class="md-body-sm" style="color:var(--md-on-surface-variant);">{{ $r->user->category->name ?? '—' }}</div>
                    </div>
                    <span class="md-badge {{ $r->is_read ? 'md-badge--success' : 'md-badge--neutral' }}">
                        {{ $r->is_read ? 'Read' : 'Unread' }}
                    </span>
                    @php($sb = ['pending'=>'neutral','reviewed'=>'info','approved'=>'success','rejected'=>'critical'][$r->status] ?? 'neutral')
                    <span class="md-badge md-badge--{{ $sb }}">{{ ucfirst($r->status) }}</span>
                </div>
                @if($r->remarks)
                    <p class="md-body-sm" style="color:var(--md-on-surface-variant);padding:0 4px 8px 4px;
                       border-bottom:1px solid var(--md-outline-variant);font-style:italic;">
                        "{{ $r->remarks }}"
                    </p>
                @endif
            @endforeach
        </div>

    </div>{{-- /lr-panel --}}

</div>{{-- /lr-split --}}
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    var totalSlots = {{ $letter->attachments->count() }};

    /** Switch visible slot + matching toolbar tab + action buttons */
    window.switchSlot = function (idx) {
        for (var i = 0; i < totalSlots; i++) {
            var slot    = document.getElementById('slot-' + i);
            var tab     = document.getElementById('tab-' + i);
            var actions = document.getElementById('actions-' + i);
            if (slot)    slot.classList.toggle('lr-panel-slot--active', i === idx);
            if (tab)     tab.classList.toggle('lr-viewer__tab--active', i === idx);
            if (actions) actions.style.display = i === idx ? '' : 'none';
        }
    };

    /** Hide loading overlay once PDF object fires its load event */
    window.hideLoading = function (idx) {
        var overlay = document.getElementById('loading-' + idx);
        if (overlay) {
            overlay.classList.add('hidden');
            // Remove entirely after transition so it doesn't block clicks
            setTimeout(function () { overlay.remove(); }, 400);
        }
    };

    /** Called if <object> fires an error — show the text fallback inside it */
    window.showPdfFallback = function (idx) {
        hideLoading(idx);
        var fb = document.getElementById('pdfFallback-' + idx);
        if (fb) fb.style.display = 'flex';
    };

    /** Print the active PDF using its embedded object */
    window.printSlot = function (idx) {
        var obj = document.getElementById('pdfObj-' + idx);
        if (obj && obj.contentWindow) {
            try { obj.contentWindow.print(); return; } catch (_) {}
        }
        // Fallback: open in new tab so user can print from there
        var slot = document.getElementById('slot-' + idx);
        var link = slot && slot.querySelector('object[data]');
        if (link) window.open(link.getAttribute('data'), '_blank');
    };

    // Auto-hide loading overlay for slots that load instantly (e.g. images)
    // or when the object is already in the browser cache
    setTimeout(function () {
        for (var i = 0; i < totalSlots; i++) {
            var overlay = document.getElementById('loading-' + i);
            var slot    = document.getElementById('slot-' + i);
            if (overlay && slot && slot.getAttribute('data-type') !== 'pdf') {
                overlay.remove();
            }
        }
    }, 100);
})();
</script>
@endpush
