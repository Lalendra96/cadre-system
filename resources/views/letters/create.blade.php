@extends('layouts.app')
@section('title', 'Share a Letter')

@push('head')
<style>
/* ═══════════════════════════════════════════════════════════════════
   Recipient Picker — MD3 dark, no framework
   ═══════════════════════════════════════════════════════════════════ */

/* Category color tokens — extend by adding [data-cat="…"] rules */
[data-cat="Deputy Director General"]                    { --rp-c: #F5C56B; }
[data-cat="Director"]                                   { --rp-c: #A8C8FF; }
[data-cat="Deputy Director"]                            { --rp-c: #D7BDE8; }
[data-cat="Administrative Officer / Hospital Secretary"]{ --rp-c: #7DD996; }
[data-cat="Chief Clerk"]                                { --rp-c: #BAC6DC; }
[data-cat="Medical Officer Planning"]                   { --rp-c: #FFB4AB; }
[data-cat="Chief Accountant"]                           { --rp-c: #98F5B0; }

/* ── Picker shell ──────────────────────────────────────────────── */
.rp {
  background: var(--md-surface-container-high);
  border: 1px solid var(--md-outline-variant);
  border-radius: var(--md-shape-md);
  overflow: hidden;
}

/* ── Header ─────────────────────────────────────────────────────── */
.rp-header {
  display: flex; align-items: center; gap: 12px;
  padding: 14px 18px;
  background: var(--md-surface-container-highest);
  border-bottom: 1px solid var(--md-outline-variant);
}
.rp-header__label {
  font-size: 14px; font-weight: 600; color: var(--md-on-surface);
  flex: 1;
}
.rp-counter {
  font-size: 12px; font-weight: 600;
  background: var(--md-primary-container);
  color: var(--md-on-primary-container);
  padding: 3px 10px; border-radius: 999px;
  transition: background .2s ease;
  white-space: nowrap;
}
.rp-counter--has { background: var(--md-primary); color: var(--md-on-primary); }
.rp-clear-btn {
  font-size: 12px; color: var(--md-on-surface-variant); background: none;
  border: none; cursor: pointer; padding: 4px 8px; border-radius: 4px;
  transition: color .15s;
}
.rp-clear-btn:hover { color: var(--md-error); }

/* ── Search ──────────────────────────────────────────────────────── */
.rp-search-row { padding: 12px 18px; border-bottom: 1px solid var(--md-outline-variant); }
.rp-search-wrap {
  position: relative;
}
.rp-search-icon {
  position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
  font-size: 15px; pointer-events: none; color: var(--md-on-surface-variant);
}
.rp-search {
  width: 100%; padding: 9px 12px 9px 38px; font-size: 14px;
  background: var(--md-surface-container); color: var(--md-on-surface);
  border: 1px solid var(--md-outline); border-radius: var(--md-shape-sm);
  outline: none; transition: border-color .15s;
}
.rp-search:focus { border-color: var(--md-primary); }
.rp-search::placeholder { color: var(--md-on-surface-variant); }

/* ── Category pill filter strip ─────────────────────────────────── */
.rp-pills {
  display: flex; gap: 6px; padding: 10px 18px;
  overflow-x: auto; flex-wrap: nowrap;
  border-bottom: 1px solid var(--md-outline-variant);
  scrollbar-width: none;
}
.rp-pills::-webkit-scrollbar { display: none; }

.rp-pill {
  display: inline-flex; align-items: center; gap: 5px; white-space: nowrap;
  padding: 5px 14px; border-radius: 999px; font-size: 12px; font-weight: 500;
  cursor: pointer; background: var(--md-surface-container);
  color: var(--md-on-surface-variant);
  border: 1px solid var(--md-outline-variant);
  transition: all .15s ease; flex-shrink: 0;
}
.rp-pill:hover { background: color-mix(in srgb, var(--md-on-surface) 8%, transparent); }
.rp-pill--active {
  background: var(--md-secondary-container);
  color: var(--md-on-secondary-container);
  border-color: transparent;
}
.rp-pill__badge {
  background: var(--md-outline-variant); color: var(--md-on-surface-variant);
  border-radius: 999px; padding: 1px 6px; font-size: 10px; font-weight: 700;
}
.rp-pill--active .rp-pill__badge {
  background: var(--md-primary); color: var(--md-on-primary);
}

/* ── Select-all toolbar (shown when category pill active) ─────── */
.rp-select-all-bar {
  display: none; align-items: center; justify-content: space-between;
  padding: 8px 18px;
  background: color-mix(in srgb, var(--md-primary) 6%, transparent);
  border-bottom: 1px solid var(--md-outline-variant);
  font-size: 12px; color: var(--md-on-surface-variant);
}
.rp-select-all-bar--show { display: flex; }
.rp-select-all-btn {
  background: none; border: 1px solid var(--md-outline-variant);
  color: var(--md-primary); border-radius: 999px;
  padding: 3px 12px; font-size: 12px; font-weight: 500; cursor: pointer;
  transition: background .12s;
}
.rp-select-all-btn:hover { background: color-mix(in srgb, var(--md-primary) 10%, transparent); }

/* ── Person card grid ───────────────────────────────────────────── */
.rp-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
  gap: 10px; padding: 16px 18px;
  max-height: 360px; overflow-y: auto;
}
.rp-grid::-webkit-scrollbar { width: 5px; }
.rp-grid::-webkit-scrollbar-thumb { background: var(--md-outline-variant); border-radius: 3px; }

.rp-card {
  position: relative; display: flex; flex-direction: column;
  align-items: center; text-align: center;
  padding: 16px 10px 14px;
  background: var(--md-surface-container);
  border: 1.5px solid transparent;
  border-radius: var(--md-shape-md);
  cursor: pointer; user-select: none;
  transition: border-color .15s, box-shadow .15s, background .15s, transform .1s;
  overflow: hidden;
}
.rp-card:hover {
  background: var(--md-surface-container-high);
  border-color: var(--md-outline);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,.3);
}
.rp-card--selected {
  border-color: var(--rp-c, var(--md-primary)) !important;
  background: color-mix(in srgb, var(--rp-c, var(--md-primary)) 10%, var(--md-surface-container)) !important;
  box-shadow: 0 0 0 1px var(--rp-c, var(--md-primary)), 0 4px 12px rgba(0,0,0,.3);
}
.rp-card--hidden { display: none; }

/* Avatar circle */
.rp-avatar {
  width: 52px; height: 52px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 16px; font-weight: 700; letter-spacing: .5px;
  background: color-mix(in srgb, var(--rp-c, var(--md-primary)) 15%, var(--md-surface-container-highest));
  color: var(--rp-c, var(--md-primary));
  border: 2px solid color-mix(in srgb, var(--rp-c, var(--md-primary)) 30%, transparent);
  margin-bottom: 10px;
  transition: background .15s, border-color .15s, transform .15s;
  flex-shrink: 0;
}
.rp-card--selected .rp-avatar {
  background: color-mix(in srgb, var(--rp-c, var(--md-primary)) 28%, var(--md-surface-container));
  border-color: var(--rp-c, var(--md-primary));
  transform: scale(1.08);
}

/* Name + category */
.rp-name {
  font-size: 11.5px; font-weight: 500; color: var(--md-on-surface);
  line-height: 1.35; margin-bottom: 5px;
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
  max-height: 2.7em; width: 100%;
}
.rp-cat-badge {
  font-size: 9.5px; font-weight: 600; letter-spacing: .3px;
  color: var(--rp-c, var(--md-primary));
  background: color-mix(in srgb, var(--rp-c, var(--md-primary)) 14%, transparent);
  padding: 2px 7px; border-radius: 999px; max-width: 100%;
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}

/* Checkmark overlay — slides in from top-right */
.rp-check {
  position: absolute; top: 8px; right: 8px;
  width: 20px; height: 20px; border-radius: 50%;
  background: var(--rp-c, var(--md-primary));
  display: flex; align-items: center; justify-content: center;
  font-size: 11px; color: var(--md-background);
  opacity: 0; transform: scale(.5) rotate(-45deg);
  transition: opacity .2s ease, transform .2s ease;
  font-weight: 900; line-height: 1;
}
.rp-card--selected .rp-check {
  opacity: 1; transform: scale(1) rotate(0deg);
}

/* Empty state */
.rp-empty {
  grid-column: 1 / -1; text-align: center; padding: 28px;
  color: var(--md-on-surface-variant); font-size: 13px;
}

/* ── Selected chips strip ───────────────────────────────────────── */
.rp-chips-bar {
  border-top: 1px solid var(--md-outline-variant);
  padding: 10px 18px;
  background: var(--md-surface-container);
  display: none;
}
.rp-chips-bar--show { display: block; }
.rp-chips-bar__label {
  font-size: 10px; font-weight: 600; text-transform: uppercase;
  letter-spacing: .8px; color: var(--md-on-surface-variant); margin-bottom: 8px;
}
.rp-chips {
  display: flex; flex-wrap: wrap; gap: 6px;
}
.rp-chip {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 4px 10px 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 500;
  background: color-mix(in srgb, var(--rp-c, var(--md-primary)) 18%, var(--md-surface-container-highest));
  color: var(--rp-c, var(--md-primary));
  border: 1px solid color-mix(in srgb, var(--rp-c, var(--md-primary)) 35%, transparent);
  cursor: pointer; transition: opacity .12s, background .12s;
  animation: chipIn .15s ease;
}
.rp-chip:hover { opacity: .7; }
.rp-chip__x { font-size: 13px; font-weight: 700; line-height: 1; }
@keyframes chipIn { from { transform: scale(.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }
</style>
@endpush

@section('content')
<h1 class="md-h2" style="margin-bottom:6px;">Share a Letter for Review</h1>
<p class="md-caption" style="margin-bottom:20px;color:var(--md-on-surface-variant);">
    Choose exactly who should review this — recipients are selected individually, not sent to the whole Admin Group.
    Uploaded files are automatically removed after 30 days.
</p>

<form method="POST" action="{{ route('letters.store') }}"
      enctype="multipart/form-data" id="letterForm">
    @csrf

    <div style="display:flex;flex-direction:column;gap:20px;">

        {{-- ── Meta ── --}}
        <div class="md-card md-card--elevated">
            <div class="md-card__body" style="display:flex;flex-direction:column;gap:16px;">

                <div class="md-field">
                    <label class="md-field__label">Title <span style="color:var(--md-error)">*</span></label>
                    <input type="text" name="title"
                           class="md-field__input @error('title') md-field--error @enderror"
                           value="{{ old('title') }}" required autofocus
                           placeholder="e.g. Ward A Staffing Request — July 2026">
                    @error('title')<div class="md-field__error">{{ $message }}</div>@enderror
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="md-field">
                        <label class="md-field__label">Subject Code <span class="md-body-sm" style="font-weight:400;color:var(--md-on-surface-variant);">(optional)</span></label>
                        <select name="subject_code_id" class="md-field__input">
                            <option value="">— Not specific to a code —</option>
                            @foreach($assignedCodes as $code)
                                <option value="{{ $code->id }}" {{ old('subject_code_id') == $code->id ? 'selected' : '' }}>
                                    {{ $code->code }} — {{ $code->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md-field">
                        <label class="md-field__label">Description</label>
                        <textarea name="description" class="md-field__input" rows="2"
                                  placeholder="Optional context for reviewers">{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════
             RECIPIENT PICKER
             ════════════════════════════════════════════════════════ --}}
        @php
            $totalRecipients = $eligibleRecipients->flatten()->count();
            $oldSelected     = array_map('intval', old('recipient_user_ids', []));
        @endphp

        <div>
            <div class="rp" id="rpPicker">

                {{-- Header --}}
                <div class="rp-header">
                    <span class="rp-header__label">
                        Recipients <span style="color:var(--md-error)">*</span>
                    </span>
                    <span class="rp-counter" id="rpCounter">0 of {{ $totalRecipients }}</span>
                    <button type="button" class="rp-clear-btn" id="rpClearBtn">Clear all</button>
                </div>

                {{-- Search --}}
                <div class="rp-search-row">
                    <div class="rp-search-wrap">
                        <span class="rp-search-icon">🔍</span>
                        <input type="text" id="rpSearch" class="rp-search"
                               placeholder="Search officers by name…" autocomplete="off">
                    </div>
                </div>

                {{-- Category pills --}}
                <div class="rp-pills" id="rpPills">
                    <button type="button" class="rp-pill rp-pill--active" data-cat="">
                        All <span class="rp-pill__badge" id="rpAllBadge">{{ $totalRecipients }}</span>
                    </button>
                    @foreach($eligibleRecipients as $catName => $catUsers)
                        <button type="button" class="rp-pill" data-cat="{{ $catName }}">
                            {{ \Illuminate\Support\Str::limit($catName, 28) }}
                            <span class="rp-pill__badge">{{ count($catUsers) }}</span>
                        </button>
                    @endforeach
                </div>

                {{-- Select-all bar (shown when a category filter is active) --}}
                <div class="rp-select-all-bar" id="rpSelectAllBar">
                    <span id="rpSelectAllLabel"></span>
                    <button type="button" class="rp-select-all-btn" id="rpSelectAllBtn"></button>
                </div>

                {{-- Cards grid --}}
                <div class="rp-grid" id="rpGrid">
                    @forelse($eligibleRecipients as $catName => $catUsers)
                        @foreach($catUsers as $u)
                            @php
                                $parts    = array_values(array_filter(preg_split('/[\s.]+/', $u->name)));
                                $initials = implode('', array_map(fn ($p) => strtoupper($p[0] ?? ''), array_slice($parts, 0, 2)));
                                $isOld    = in_array($u->id, $oldSelected);
                            @endphp
                            <div class="rp-card {{ $isOld ? 'rp-card--selected' : '' }}"
                                 data-id="{{ $u->id }}"
                                 data-cat="{{ $catName }}"
                                 data-name="{{ strtolower($u->name) }}">
                                <div class="rp-avatar" data-cat="{{ $catName }}">{{ $initials ?: '?' }}</div>
                                <div class="rp-name">{{ $u->name }}</div>
                                <div class="rp-cat-badge" data-cat="{{ $catName }}">{{ $catName }}</div>
                                <div class="rp-check">✓</div>
                            </div>
                        @endforeach
                    @empty
                        <div class="rp-empty">
                            No eligible recipients are configured yet.<br>
                            Ask the Super Admin to assign Admin Group users to the correct categories<br>
                            and enable <strong>Can Receive Letters</strong> on those categories.
                        </div>
                    @endforelse
                </div>

                {{-- Selected chips strip --}}
                <div class="rp-chips-bar" id="rpChipsBar">
                    <div class="rp-chips-bar__label">Selected recipients</div>
                    <div class="rp-chips" id="rpChips"></div>
                </div>

                {{-- Hidden inputs for form submission — managed by JS --}}
                <div id="rpHidden" aria-hidden="true"></div>
            </div>

            @error('recipient_user_ids')
                <div class="md-field__error" style="margin-top:6px;">{{ $message }}</div>
            @enderror
        </div>

        {{-- ── Files ─────────────────────────────────────────────── --}}
        <div class="md-card md-card--elevated">
            <div class="md-card__header"><span class="md-title-md">Attachments</span></div>
            <div class="md-card__body" style="display:flex;flex-direction:column;gap:12px;">
                <div class="md-dropzone" id="dropzone">
                    <div class="md-dropzone__icon">📁</div>
                    <div class="md-dropzone__title">Drag and drop files here, or click to browse</div>
                    <div class="md-dropzone__hint">PDF, Word, or image — max 10 MB per file</div>
                </div>
                <input type="file" id="fileInput" name="attachments[]"
                       multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" style="display:none;">
                <div class="md-file-stack" id="fileStack"></div>
                @error('attachments')<div class="md-field__error">{{ $message }}</div>@enderror
                @error('attachments.*')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>
        </div>

        {{-- ── Actions ───────────────────────────────────────────── --}}
        <div style="display:flex;justify-content:flex-end;gap:10px;">
            <a href="{{ route('letters.index') }}" class="md-btn md-btn--text">Cancel</a>
            <button type="submit" class="md-btn md-btn--filled" id="submitBtn">
                Share for Review
            </button>
        </div>

    </div>
</form>

{{-- File preview modal --}}
<div id="filePreviewModal" style="display:none;position:fixed;inset:0;z-index:9999;
     background:rgba(0,0,0,.75);align-items:center;justify-content:center;padding:24px;">
    <div style="background:var(--md-surface-container-low);border-radius:var(--md-shape-md);
                max-width:900px;width:100%;max-height:90vh;display:flex;flex-direction:column;
                overflow:hidden;box-shadow:var(--md-elevation-3);">
        <div style="display:flex;align-items:center;justify-content:space-between;
                    padding:14px 20px;border-bottom:1px solid var(--md-outline-variant);">
            <span class="md-title-md" id="filePreviewTitle"
                  style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:80%;"></span>
            <button id="filePreviewClose" class="md-btn md-btn--icon" title="Close">&#10005;</button>
        </div>
        <div id="filePreviewBody"
             style="flex:1;overflow:auto;padding:16px;display:flex;align-items:center;justify-content:center;"></div>
    </div>
</div>

@endsection

@push('scripts')
<script>
/* ═══════════════════════════════════════════════════════════════════
   Recipient Picker
   ═══════════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    const TOTAL     = {{ $totalRecipients }};
    const OLD_IDS   = new Set({{ json_encode($oldSelected) }});

    let selected  = new Set(OLD_IDS);
    let activeCat = '';

    const grid      = document.getElementById('rpGrid');
    const counter   = document.getElementById('rpCounter');
    const chips     = document.getElementById('rpChips');
    const chipsBar  = document.getElementById('rpChipsBar');
    const hidden    = document.getElementById('rpHidden');
    const clearBtn  = document.getElementById('rpClearBtn');
    const search    = document.getElementById('rpSearch');
    const allBar    = document.getElementById('rpSelectAllBar');
    const allLabel  = document.getElementById('rpSelectAllLabel');
    const allBtn    = document.getElementById('rpSelectAllBtn');

    /* ── Card click handler ─────────────────────────────────────── */
    grid.addEventListener('click', function (e) {
        const card = e.target.closest('.rp-card');
        if (!card) return;
        const id = parseInt(card.dataset.id, 10);
        if (selected.has(id)) selected.delete(id); else selected.add(id);
        render();
    });

    /* ── Category pill filter ───────────────────────────────────── */
    document.getElementById('rpPills').addEventListener('click', function (e) {
        const pill = e.target.closest('.rp-pill');
        if (!pill) return;
        activeCat = pill.dataset.cat;
        document.querySelectorAll('.rp-pill').forEach(p =>
            p.classList.toggle('rp-pill--active', p.dataset.cat === activeCat)
        );
        applyFilter();
        updateSelectAllBar();
    });

    /* ── Search ─────────────────────────────────────────────────── */
    search.addEventListener('input', applyFilter);

    function applyFilter() {
        const kw = search.value.toLowerCase().trim();
        document.querySelectorAll('.rp-card').forEach(card => {
            const catMatch  = !activeCat || card.dataset.cat === activeCat;
            const nameMatch = !kw        || card.dataset.name.includes(kw);
            card.classList.toggle('rp-card--hidden', !(catMatch && nameMatch));
        });
        updateSelectAllBar();
    }

    /* ── Select all in current filter ──────────────────────────── */
    function updateSelectAllBar() {
        if (!activeCat) { allBar.classList.remove('rp-select-all-bar--show'); return; }

        const visible = [...document.querySelectorAll(`.rp-card[data-cat="${activeCat}"]:not(.rp-card--hidden)`)];
        const allSel  = visible.every(c => selected.has(parseInt(c.dataset.id, 10)));

        allBar.classList.add('rp-select-all-bar--show');
        allLabel.textContent = visible.length + ' officer' + (visible.length !== 1 ? 's' : '') + ' in this category';
        allBtn.textContent   = allSel ? 'Deselect all' : 'Select all';
        allBtn.onclick       = function () {
            visible.forEach(c => {
                const id = parseInt(c.dataset.id, 10);
                allSel ? selected.delete(id) : selected.add(id);
            });
            render();
            updateSelectAllBar();
        };
    }

    /* ── Clear all ──────────────────────────────────────────────── */
    clearBtn.addEventListener('click', function () {
        selected.clear();
        render();
        updateSelectAllBar();
    });

    /* ── Render ─────────────────────────────────────────────────── */
    function render() {
        /* Card states */
        document.querySelectorAll('.rp-card').forEach(card => {
            const id  = parseInt(card.dataset.id, 10);
            const sel = selected.has(id);
            card.classList.toggle('rp-card--selected', sel);
        });

        /* Counter */
        const n = selected.size;
        counter.textContent = n + ' of ' + TOTAL + ' selected';
        counter.classList.toggle('rp-counter--has', n > 0);

        /* Hidden inputs for form */
        hidden.innerHTML = '';
        selected.forEach(id => {
            const inp  = document.createElement('input');
            inp.type   = 'hidden';
            inp.name   = 'recipient_user_ids[]';
            inp.value  = id;
            hidden.appendChild(inp);
        });

        /* Chips bar */
        if (n === 0) {
            chipsBar.classList.remove('rp-chips-bar--show');
            chips.innerHTML = '';
            return;
        }
        chipsBar.classList.add('rp-chips-bar--show');
        chips.innerHTML = '';

        selected.forEach(id => {
            const card = document.querySelector(`.rp-card[data-id="${id}"]`);
            if (!card) return;
            const cat  = card.dataset.cat;
            const name = card.querySelector('.rp-name').textContent.trim();

            const chip = document.createElement('button');
            chip.type  = 'button';
            chip.className = 'rp-chip';
            chip.setAttribute('data-cat', cat);
            chip.innerHTML = name + ' <span class="rp-chip__x">×</span>';
            chip.onclick   = () => { selected.delete(id); render(); updateSelectAllBar(); };
            chips.appendChild(chip);
        });
    }

    /* Boot */
    render();
    if (selected.size > 0 && activeCat === '') updateSelectAllBar();

})();

/* ═══════════════════════════════════════════════════════════════════
   File drop zone + preview
   ═══════════════════════════════════════════════════════════════════ */
(function () {
    const dropzone  = document.getElementById('dropzone');
    const fileInput = document.getElementById('fileInput');
    const fileStack = document.getElementById('fileStack');
    let files = [];

    function fmt(b) {
        if (b >= 1048576) return (b / 1048576).toFixed(1) + ' MB';
        if (b >= 1024)    return (b / 1024).toFixed(1) + ' KB';
        return b + ' B';
    }

    function renderStack() {
        fileStack.innerHTML = '';
        if (window._rpUrls) window._rpUrls.forEach(URL.revokeObjectURL);
        window._rpUrls = [];

        files.forEach((file, i) => {
            const ext    = file.name.split('.').pop().toLowerCase();
            const isPdf  = ext === 'pdf';
            const isImg  = ['jpg','jpeg','png'].includes(ext);
            const objUrl = (isPdf || isImg) ? URL.createObjectURL(file) : null;
            if (objUrl) window._rpUrls.push(objUrl);

            const icon = isPdf ? '📄' : isImg ? '🖼' : '📎';
            const card = document.createElement('div');
            card.className = 'md-file-card';
            card.innerHTML = `
                <span class="md-file-card__icon">${icon}</span>
                ${isImg ? `<img src="${objUrl}" style="width:44px;height:44px;object-fit:cover;border-radius:4px;flex-shrink:0;cursor:pointer;" onclick="openFilePreview('${objUrl}','image','${file.name}')">` : ''}
                <div class="md-file-card__info">
                    <div class="md-file-card__name">${file.name}</div>
                    <div class="md-file-card__size">${fmt(file.size)}</div>
                </div>
                ${(isPdf||isImg) ? `<button type="button" class="md-btn md-btn--icon" onclick="openFilePreview('${objUrl}','${isPdf?'pdf':'image'}','${file.name}')" title="Preview" style="font-size:16px;">👁</button>` : ''}
                <button type="button" class="md-file-card__remove" data-i="${i}" title="Remove">&times;</button>
            `;
            fileStack.appendChild(card);
        });

        fileStack.querySelectorAll('.md-file-card__remove').forEach(btn => {
            btn.addEventListener('click', () => {
                files.splice(parseInt(btn.dataset.i, 10), 1);
                sync(); renderStack();
            });
        });
    }

    function sync() {
        const dt = new DataTransfer();
        files.forEach(f => dt.items.add(f));
        fileInput.files = dt.files;
    }

    function add(newFiles) {
        Array.from(newFiles).forEach(f => files.push(f));
        sync(); renderStack();
    }

    window.openFilePreview = function (url, type, name) {
        const modal = document.getElementById('filePreviewModal');
        const body  = document.getElementById('filePreviewBody');
        document.getElementById('filePreviewTitle').textContent = name;
        body.innerHTML = '';
        if (type === 'pdf') {
            const o = document.createElement('object');
            o.data  = url; o.type = 'application/pdf';
            o.style.cssText = 'width:100%;height:75vh;border:none;';
            body.appendChild(o);
        } else {
            const img = document.createElement('img');
            img.src = url;
            img.style.cssText = 'max-width:100%;max-height:75vh;object-fit:contain;';
            body.appendChild(img);
        }
        modal.style.display = 'flex';
    };

    document.getElementById('filePreviewClose').addEventListener('click', () =>
        document.getElementById('filePreviewModal').style.display = 'none');
    document.getElementById('filePreviewModal').addEventListener('click', function (e) {
        if (e.target === this) this.style.display = 'none';
    });

    dropzone.addEventListener('click', () => fileInput.click());
    dropzone.addEventListener('dragover', e => { e.preventDefault(); dropzone.classList.add('md-dropzone--dragover'); });
    dropzone.addEventListener('dragleave', () => dropzone.classList.remove('md-dropzone--dragover'));
    dropzone.addEventListener('drop', e => {
        e.preventDefault(); dropzone.classList.remove('md-dropzone--dragover');
        if (e.dataTransfer.files.length) add(e.dataTransfer.files);
    });
    fileInput.addEventListener('change', () => { if (fileInput.files.length) add(fileInput.files); });
})();
</script>
@endpush
