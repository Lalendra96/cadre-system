@extends('layouts.app')
@section('title', $entry->exists ? 'Edit Monthly Entry' : 'New Monthly Entry')

@section('content')
<h2 class="md-headline-sm" style="margin-bottom:16px;">
    {{ $entry->exists ? 'Edit Monthly Entry' : 'New Monthly Entry' }}
</h2>

<form method="POST"
      action="{{ $entry->exists ? route('carder-entries.update', $entry) : route('carder-entries.store') }}"
      class="md-card md-card--elevated"
      id="entryForm">
    @csrf
    @if($entry->exists) @method('PUT') @endif

    <div class="md-card__body" style="display:flex;flex-direction:column;gap:20px;">

        @if($errors->any())
            <div style="background:var(--md-error-container);color:var(--md-on-error-container);
                        padding:12px 16px;border-radius:var(--md-shape-sm);font-size:13px;">
                <ul style="margin:0;padding-left:16px;">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif

        {{-- ── Subject Code ──────────────────────────────────────────────── --}}
        <div class="md-field">
            <label class="md-field__label">
                Subject Code <span style="color:var(--md-error)">*</span>
            </label>

            @if($entry->exists)
                {{-- Locked on edit --}}
                <input class="md-field__input"
                       value="{{ $entry->subjectCode->code }} — {{ $entry->subjectCode->name }}"
                       disabled>
                <input type="hidden" name="subject_code_id" value="{{ $entry->subject_code_id }}">
            @else
                {{-- Each option embeds its linked positions as JSON so the JS
                     can populate the position dropdown without any network call,
                     and without hitting the super-admin-only positions endpoint. --}}
                <select name="subject_code_id"
                        id="subjectCodeSelect"
                        class="md-field__input @error('subject_code_id') md-field--error @enderror"
                        required>
                    <option value="" data-positions="[]">— Select subject code —</option>
                    @foreach($assignedCodes as $code)
                        <option value="{{ $code->id }}"
                                data-positions="{{ $code->positions->map(fn($p) => ['id'=>$p->id,'title'=>$p->title])->values()->toJson() }}"
                                {{ old('subject_code_id') == $code->id ? 'selected' : '' }}>
                            {{ $code->code }} — {{ $code->name }}
                        </option>
                    @endforeach
                </select>
                @error('subject_code_id')
                    <div class="md-field__error">{{ $message }}</div>
                @enderror
            @endif
        </div>

        {{-- ── Position ──────────────────────────────────────────────────── --}}
        <div class="md-field" id="positionWrapper">

            @if($entry->exists)
                {{-- Edit: position is fixed — show label + hidden input --}}
                <label class="md-field__label">Position</label>
                <input class="md-field__input"
                       value="{{ $entry->position->title ?? '—' }}"
                       disabled>
                <input type="hidden" name="position_id" value="{{ $entry->position_id }}">

            @else
                {{-- Create: shown/hidden + auto-selected by JS below --}}

                {{-- Case: no position yet (rendered server-side on first load
                     before code is selected) --}}
                <div id="positionEmpty"
                     style="padding:10px 14px;background:var(--md-surface-container-high);
                            border:1px dashed var(--md-outline);border-radius:var(--md-shape-xs);
                            font-size:13px;color:var(--md-on-surface-variant);">
                    Select a subject code above to see available positions.
                </div>

                {{-- Case: exactly one position — auto-filled, shown as read-only chip --}}
                <div id="positionSingle" style="display:none;">
                    <label class="md-field__label">Position</label>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span id="positionSingleLabel"
                              style="padding:10px 14px;background:var(--md-surface-container-high);
                                     border:1px solid var(--md-outline);border-radius:var(--md-shape-xs);
                                     font-size:14px;flex:1;">—</span>
                        <span class="md-badge md-badge--info" style="font-size:11px;">Auto-selected</span>
                    </div>
                    <input type="hidden" id="positionSingleInput" name="position_id" value="">
                </div>

                {{-- Case: multiple positions — officer must choose --}}
                <div id="positionMultiple" style="display:none;">
                    <label class="md-field__label">
                        Position <span style="color:var(--md-error)">*</span>
                        <span class="md-body-sm" style="color:var(--md-on-surface-variant);font-weight:400;">
                            — this subject code covers multiple positions; select which one this entry is for
                        </span>
                    </label>
                    <select name="position_id"
                            id="positionMultipleSelect"
                            class="md-field__input @error('position_id') md-field--error @enderror"
                            required>
                        <option value="">— Select position —</option>
                    </select>
                    @error('position_id')
                        <div class="md-field__error">{{ $message }}</div>
                    @enderror
                </div>

            @endif
        </div>

        {{-- ── Period ────────────────────────────────────────────────────── --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="md-field">
                <label class="md-field__label">Year <span style="color:var(--md-error)">*</span></label>
                <input type="number" name="year"
                       class="md-field__input @error('year') md-field--error @enderror"
                       value="{{ old('year', $entry->year ?? now()->year) }}"
                       min="2000" max="2100"
                       {{ $entry->exists ? 'readonly' : '' }} required>
                @error('year')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>

            <div class="md-field">
                <label class="md-field__label">Month <span style="color:var(--md-error)">*</span></label>
                @if($entry->exists)
                    <input class="md-field__input"
                           value="{{ \DateTime::createFromFormat('!m', $entry->month)->format('F') }}"
                           disabled>
                    <input type="hidden" name="month" value="{{ $entry->month }}">
                @else
                    <select name="month"
                            class="md-field__input @error('month') md-field--error @enderror"
                            required>
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}"
                                    {{ old('month', now()->month) == $m ? 'selected' : '' }}>
                                {{ \DateTime::createFromFormat('!m', $m)->format('F') }}
                            </option>
                        @endfor
                    </select>
                @endif
                @error('month')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>
        </div>

        {{-- ── Headcount ─────────────────────────────────────────────────── --}}
        <div style="display:flex;align-items:center;justify-content:space-between;">
            <span class="md-label-md">Headcount</span>
            @unless($entry->exists)
            <button type="button" id="copyPreviousBtn" class="md-btn md-btn--outlined" style="font-size:12px;" disabled>
                📋 Copy Previous Entry
            </button>
            @endunless
        </div>
        <div id="copyPreviousStatus" class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:-12px;min-height:16px;"></div>
        <div id="deadlineStatusBanner" style="display:none;"></div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="md-field">
                <label class="md-field__label">Males <span style="color:var(--md-error)">*</span></label>
                <input type="number" name="males"
                       class="md-field__input @error('males') md-field--error @enderror"
                       value="{{ old('males', $entry->males ?? 0) }}" min="0" required>
                @error('males')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>
            <div class="md-field">
                <label class="md-field__label">Females <span style="color:var(--md-error)">*</span></label>
                <input type="number" name="females"
                       class="md-field__input @error('females') md-field--error @enderror"
                       value="{{ old('females', $entry->females ?? 0) }}" min="0" required>
                @error('females')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
            <div class="md-field">
                <label class="md-field__label">Transferred In <span style="color:var(--md-error)">*</span></label>
                <input type="number" name="transferred_in"
                       class="md-field__input @error('transferred_in') md-field--error @enderror"
                       value="{{ old('transferred_in', $entry->transferred_in ?? 0) }}" min="0" required>
                @error('transferred_in')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>
            <div class="md-field">
                <label class="md-field__label">Transferred Out <span style="color:var(--md-error)">*</span></label>
                <input type="number" name="transferred_out"
                       class="md-field__input @error('transferred_out') md-field--error @enderror"
                       value="{{ old('transferred_out', $entry->transferred_out ?? 0) }}" min="0" required>
                @error('transferred_out')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>
            <div class="md-field">
                <label class="md-field__label">No-Pay Leave <span style="color:var(--md-error)">*</span></label>
                <input type="number" name="no_pay_leave"
                       class="md-field__input @error('no_pay_leave') md-field--error @enderror"
                       value="{{ old('no_pay_leave', $entry->no_pay_leave ?? 0) }}" min="0" required>
                @error('no_pay_leave')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="md-field">
            <label class="md-field__label">Remarks</label>
            <textarea name="remarks"
                      class="md-field__input"
                      rows="3">{{ old('remarks', $entry->remarks) }}</textarea>
        </div>

    </div>{{-- /card body --}}

    <div class="md-card__footer">
        <a href="{{ route('carder-entries.index') }}" class="md-btn md-btn--text">Cancel</a>
        <button type="submit" id="submitBtn" class="md-btn md-btn--filled">Submit Entry</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    var codeSelect   = document.getElementById('subjectCodeSelect');
    if (!codeSelect) return;  // edit mode — no dynamic selector needed

    var emptyPanel   = document.getElementById('positionEmpty');
    var singlePanel  = document.getElementById('positionSingle');
    var singleLabel  = document.getElementById('positionSingleLabel');
    var singleInput  = document.getElementById('positionSingleInput');
    var multiPanel   = document.getElementById('positionMultiple');
    var multiSelect  = document.getElementById('positionMultipleSelect');
    var submitBtn    = document.getElementById('submitBtn');

    // Pre-selected values from a failed validation round-trip
    var oldPositionId = '{{ old('position_id') }}';

    function showEmpty() {
        emptyPanel.style.display  = '';
        singlePanel.style.display = 'none';
        multiPanel.style.display  = 'none';
        singleInput.value         = '';
        multiSelect.required      = false;
    }

    function showSingle(pos) {
        emptyPanel.style.display  = 'none';
        singlePanel.style.display = '';
        multiPanel.style.display  = 'none';
        singleLabel.textContent   = pos.title;
        singleInput.value         = pos.id;
        multiSelect.required      = false;
    }

    function showMultiple(positions) {
        emptyPanel.style.display  = 'none';
        singlePanel.style.display = 'none';
        multiPanel.style.display  = '';
        multiSelect.required      = true;

        // Rebuild options
        multiSelect.innerHTML = '<option value="">— Select position —</option>';
        positions.forEach(function (p) {
            var opt    = document.createElement('option');
            opt.value  = p.id;
            opt.textContent = p.title;
            if (String(p.id) === oldPositionId) {
                opt.selected = true;
            }
            multiSelect.appendChild(opt);
        });

        // If only one choice after filtering still ended up in "multiple"
        // (edge-case guard), auto-select it
        if (multiSelect.options.length === 2) {
            multiSelect.options[1].selected = true;
        }
    }

    function onCodeChange() {
        var selected  = codeSelect.options[codeSelect.selectedIndex];
        var json      = selected ? selected.getAttribute('data-positions') : '[]';
        var positions = [];

        try { positions = JSON.parse(json || '[]'); } catch (_) {}

        if (positions.length === 0) {
            showEmpty();
        } else if (positions.length === 1) {
            showSingle(positions[0]);
        } else {
            showMultiple(positions);
        }
    }

    codeSelect.addEventListener('change', onCodeChange);

    // Run immediately on page load to handle:
    //   a) validation failure returning old('subject_code_id')
    //   b) page with only one code (auto-select the code and trigger)
    if (codeSelect.value) {
        onCodeChange();
    } else if (codeSelect.options.length === 2) {
        // Only one assignable code — auto-select it
        codeSelect.options[1].selected = true;
        onCodeChange();
    }
})();
</script>

<script>
(function () {
    'use strict';

    // Only present in create mode — both the button and banner are wrapped
    // in an unless-entry-exists check in the markup above.
    var copyBtn    = document.getElementById('copyPreviousBtn');
    var copyStatus = document.getElementById('copyPreviousStatus');
    var deadlineBanner = document.getElementById('deadlineStatusBanner');
    if (!copyBtn && !deadlineBanner) return; // edit mode — nothing to wire up

    var codeSelect  = document.getElementById('subjectCodeSelect');
    var yearInput   = document.querySelector('input[name="year"]');
    var monthSelect = document.querySelector('select[name="month"]');

    function currentPositionId() {
        var singleInput = document.getElementById('positionSingleInput');
        if (singleInput && singleInput.value) return singleInput.value;
        var multiSelect = document.getElementById('positionMultipleSelect');
        if (multiSelect && multiSelect.value) return multiSelect.value;
        return '';
    }

    function currentParams() {
        return {
            subject_code_id: codeSelect ? codeSelect.value : '',
            position_id:     currentPositionId(),
            year:            yearInput ? yearInput.value : '',
            month:           monthSelect ? monthSelect.value : '',
        };
    }

    function paramsReady(p) {
        return p.subject_code_id && p.position_id && p.year && p.month;
    }

    function refreshCopyButtonState() {
        if (!copyBtn) return;
        copyBtn.disabled = !paramsReady(currentParams());
        copyStatus.textContent = '';
    }

    function refreshDeadlineBanner() {
        if (!deadlineBanner || !yearInput || !monthSelect || !yearInput.value || !monthSelect.value) return;

        fetch('{{ route("carder-entries.deadline-status") }}?year=' + encodeURIComponent(yearInput.value)
              + '&month=' + encodeURIComponent(monthSelect.value), {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
        })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (!data) { deadlineBanner.style.display = 'none'; return; }
                if (data.locked) {
                    deadlineBanner.style.display = 'block';
                    deadlineBanner.style.cssText = 'display:block;background:color-mix(in srgb,var(--md-error) 12%,transparent);'
                        + 'color:var(--md-error);padding:10px 14px;border-radius:var(--md-shape-sm);font-size:12.5px;margin-bottom:4px;';
                    deadlineBanner.textContent = '⚠ The submission deadline for this period has passed. You may still submit, '
                        + 'but it will be flagged as late.';
                } else {
                    deadlineBanner.style.display = 'none';
                }
            })
            .catch(function () { deadlineBanner.style.display = 'none'; });
    }

    [codeSelect, yearInput, monthSelect].forEach(function (el) {
        if (!el) return;
        el.addEventListener('change', function () {
            refreshCopyButtonState();
            refreshDeadlineBanner();
        });
    });
    // Position selects are rebuilt dynamically by the other script — re-check
    // on any change bubbling up from the position wrapper too.
    var positionWrapper = document.getElementById('positionWrapper');
    if (positionWrapper) {
        positionWrapper.addEventListener('change', refreshCopyButtonState);
    }

    if (copyBtn) {
        copyBtn.addEventListener('click', function () {
            var p = currentParams();
            if (!paramsReady(p)) return;

            copyStatus.textContent = 'Loading previous entry…';
            copyStatus.style.color = 'var(--md-on-surface-variant)';

            var qs = Object.keys(p).map(function (k) { return k + '=' + encodeURIComponent(p[k]); }).join('&');

            fetch('{{ route("carder-entries.copy-previous") }}?' + qs, {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
            })
                .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
                .then(function (res) {
                    if (!res.ok) {
                        copyStatus.textContent = res.data.error || 'Could not load a previous entry.';
                        copyStatus.style.color = 'var(--md-error)';
                        return;
                    }
                    ['males', 'females', 'transferred_in', 'transferred_out', 'no_pay_leave'].forEach(function (field) {
                        var input = document.querySelector('input[name="' + field + '"]');
                        if (input && res.data[field] !== undefined) input.value = res.data[field];
                    });
                    var monthLabel = new Date(2000, res.data.month - 1, 1).toLocaleString('en', { month: 'long' });
                    copyStatus.textContent = '✓ Copied from ' + monthLabel + ' ' + res.data.year + '. Review before submitting.';
                    copyStatus.style.color = 'var(--md-primary)';
                })
                .catch(function () {
                    copyStatus.textContent = 'Could not reach the server — please try again.';
                    copyStatus.style.color = 'var(--md-error)';
                });
        });
    }

    // Initial check on page load (old() values may already be filled in
    // after a failed validation round-trip).
    refreshCopyButtonState();
    refreshDeadlineBanner();
})();
</script>
@endpush
