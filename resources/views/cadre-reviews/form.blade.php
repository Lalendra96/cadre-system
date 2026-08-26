@extends('layouts.app')
@section('title', isset($proposal) ? 'Edit Cadre Review Proposal' : 'New Cadre Review Proposal')

@push('head')
<style>
.crv-item-row {
  display: grid;
  grid-template-columns: 2fr 100px 130px 120px 1fr;
  gap: 10px;
  align-items: center;
  padding: 10px 0;
  border-bottom: 1px solid var(--md-outline-variant);
}
.crv-item-row:last-child { border-bottom: none; }
.crv-net {
  font-weight: 700; font-size: 14px; text-align: center;
  padding: 4px 10px; border-radius: var(--md-shape-sm);
  min-width: 60px;
}
.crv-net--up   { color: var(--md-success);  background: color-mix(in srgb, var(--md-success) 12%, transparent); }
.crv-net--down { color: var(--md-error);    background: color-mix(in srgb, var(--md-error)   12%, transparent); }
.crv-net--zero { color: var(--md-on-surface-variant); background: var(--md-surface-container-high); }
.crv-summary { display: flex; gap: 20px; flex-wrap: wrap; }
.crv-summary__kpi { text-align: center; }
.crv-summary__val { font-size: 24px; font-weight: 700; }
.crv-summary__lbl { font-size: 11px; color: var(--md-on-surface-variant); text-transform: uppercase; letter-spacing:.6px; }
@media(max-width:700px){ .crv-item-row { grid-template-columns: 1fr 80px 100px 90px; } }
</style>
@endpush

@section('content')

<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
    <a href="{{ route('cadre-reviews.index') }}" class="md-btn md-btn--icon" title="Back">&#8592;</a>
    <div>
        <h2 class="md-headline-sm">{{ isset($proposal) ? 'Edit Proposal' : 'New Cadre Review Proposal' }}</h2>
        <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
            Propose cadre changes for {{ $proposalYear }}. Set the new approved amount per position and provide justification.
        </p>
    </div>
</div>

<form method="POST"
      action="{{ isset($proposal) ? route('cadre-reviews.update', $proposal) : route('cadre-reviews.store') }}"
      id="crForm">
    @csrf
    @if(isset($proposal)) @method('PUT') @endif

    @if($errors->any())
        <div style="background:var(--md-error-container);color:var(--md-on-error-container);
                    padding:14px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
            <ul style="margin:0;padding-left:16px;">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- ── Proposal header ─────────────────────────────────────────────── --}}
    <div class="md-card md-card--elevated" style="margin-bottom:16px;">
        <div class="md-card__header"><span class="md-title-md">Proposal Details</span></div>
        <div class="md-card__body" style="display:flex;flex-direction:column;gap:16px;">

            <div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;">
                <div class="md-field">
                    <label class="md-field__label">Title <span style="color:var(--md-error)">*</span></label>
                    <input type="text" name="title" class="md-field__input @error('title') md-field--error @enderror"
                           value="{{ old('title', $proposal->title ?? '') }}" required
                           placeholder="e.g. FY {{ $proposalYear }} Cadre Revision Proposal">
                    @error('title')<div class="md-field__error">{{ $message }}</div>@enderror
                </div>
                <div class="md-field">
                    <label class="md-field__label">Proposal Year <span style="color:var(--md-error)">*</span></label>
                    <input type="number" name="proposal_year"
                           class="md-field__input @error('proposal_year') md-field--error @enderror"
                           value="{{ old('proposal_year', $proposalYear) }}"
                           min="{{ now()->year }}" max="{{ now()->year + 5 }}" required>
                    @error('proposal_year')<div class="md-field__error">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="md-field">
                <label class="md-field__label">Justification / Covering Note</label>
                <textarea name="justification" class="md-field__input" rows="3"
                          placeholder="Briefly explain the overall rationale for the proposed changes…">{{ old('justification', $proposal->justification ?? '') }}</textarea>
            </div>
        </div>
    </div>

    {{-- ── Summary bar ─────────────────────────────────────────────────── --}}
    <div class="md-card md-card--elevated" style="margin-bottom:16px;padding:16px 20px;">
        <div class="crv-summary" id="crSummary">
            <div class="crv-summary__kpi">
                <div class="crv-summary__val" id="sumPositions">{{ $positions->count() }}</div>
                <div class="crv-summary__lbl">Positions</div>
            </div>
            <div class="crv-summary__kpi">
                <div class="crv-summary__val" style="color:var(--md-primary);" id="sumCurrentTotal">–</div>
                <div class="crv-summary__lbl">Current Total</div>
            </div>
            <div class="crv-summary__kpi">
                <div class="crv-summary__val" style="color:var(--md-secondary);" id="sumProposedTotal">–</div>
                <div class="crv-summary__lbl">Proposed Total</div>
            </div>
            <div class="crv-summary__kpi">
                <div class="crv-summary__val" id="sumNetChange" style="color:var(--md-on-surface-variant);">–</div>
                <div class="crv-summary__lbl">Net Change</div>
            </div>
            <div class="crv-summary__kpi">
                <div class="crv-summary__val" style="color:var(--md-success);" id="sumIncrease">0</div>
                <div class="crv-summary__lbl">Increases</div>
            </div>
            <div class="crv-summary__kpi">
                <div class="crv-summary__val" style="color:var(--md-error);" id="sumDecrease">0</div>
                <div class="crv-summary__lbl">Decreases</div>
            </div>
        </div>
    </div>

    {{-- ── Position items ──────────────────────────────────────────────── --}}
    <div class="md-card md-card--elevated" style="margin-bottom:20px;">
        <div class="md-card__header" style="flex-wrap:wrap;gap:10px;">
            <span class="md-title-md">Position Proposals</span>
            <div style="display:flex;gap:8px;align-items:center;">
                <input type="text" id="positionSearch" class="md-field__input"
                       placeholder="Filter positions…"
                       style="height:34px;max-width:220px;font-size:13px;"
                       oninput="filterRows(this.value)">
                <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;white-space:nowrap;">
                    <input type="checkbox" id="showChangedOnly" onchange="filterChanged(this.checked)">
                    Changed only
                </label>
            </div>
        </div>

        <div style="padding:0 20px 4px;">
            {{-- Column headers --}}
            <div class="crv-item-row" style="padding:6px 0;border-bottom:2px solid var(--md-outline-variant);">
                <span class="md-label-sm" style="color:var(--md-on-surface-variant);">POSITION</span>
                <span class="md-label-sm" style="color:var(--md-on-surface-variant);text-align:right;">CURRENT</span>
                <span class="md-label-sm" style="color:var(--md-primary);">PROPOSED {{ $proposalYear }}</span>
                <span class="md-label-sm" style="color:var(--md-on-surface-variant);text-align:center;">NET CHANGE</span>
                <span class="md-label-sm" style="color:var(--md-on-surface-variant);">JUSTIFICATION</span>
            </div>

            {{-- One row per position --}}
            @foreach($positions as $i => $pos)
                @php
                    $existingItem  = isset($proposal) ? $proposal->items->firstWhere('position_id', $pos->id) : null;
                    $currentApproved = (int) ($approved->get($pos->id, 0));
                    $proposedValue   = old("items.{$i}.proposed_amount",
                        $existingItem?->proposed_amount ?? $currentApproved);
                    $existingJustification = old("items.{$i}.justification",
                        $existingItem?->justification ?? '');
                @endphp
                <div class="crv-item-row position-row"
                     data-title="{{ strtolower($pos->title) }}"
                     data-current="{{ $currentApproved }}"
                     data-proposed="{{ $proposedValue }}">

                    {{-- Position name --}}
                    <div>
                        <div class="md-label-md">{{ $pos->title }}</div>
                        <input type="hidden" name="items[{{ $i }}][position_id]"      value="{{ $pos->id }}">
                        <input type="hidden" name="items[{{ $i }}][current_approved]" value="{{ $currentApproved }}">
                    </div>

                    {{-- Current approved (read-only) --}}
                    <div style="text-align:right;">
                        <span class="md-label-lg" style="color:var(--md-on-surface-variant);">{{ $currentApproved }}</span>
                    </div>

                    {{-- Proposed amount (editable) --}}
                    <div>
                        <input type="number"
                               name="items[{{ $i }}][proposed_amount]"
                               class="md-field__input proposed-input"
                               value="{{ $proposedValue }}"
                               min="0" max="9999"
                               data-current="{{ $currentApproved }}"
                               data-row="{{ $i }}"
                               oninput="onProposedChange(this)"
                               style="text-align:right;height:36px;">
                        @error("items.{$i}.proposed_amount")
                            <div class="md-field__error" style="font-size:10px;">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Net change badge --}}
                    <div style="text-align:center;">
                        <span class="crv-net crv-net--zero" id="net-{{ $i }}">
                            {{ $proposedValue - $currentApproved >= 0 ? '+' : '' }}{{ $proposedValue - $currentApproved }}
                        </span>
                    </div>

                    {{-- Per-item justification --}}
                    <div>
                        <input type="text"
                               name="items[{{ $i }}][justification]"
                               class="md-field__input"
                               value="{{ $existingJustification }}"
                               placeholder="Reason for change…"
                               style="height:36px;font-size:12px;">
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div style="display:flex;justify-content:flex-end;gap:10px;">
        <a href="{{ route('cadre-reviews.index') }}" class="md-btn md-btn--text">Cancel</a>
        <button type="submit" class="md-btn md-btn--filled">
            {{ isset($proposal) ? 'Update Draft' : 'Save as Draft' }}
        </button>
    </div>
</form>

@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    // ── Net change badge update ────────────────────────────────────────────
    window.onProposedChange = function (input) {
        var i       = input.dataset.row;
        var current = parseInt(input.dataset.current, 10) || 0;
        var proposed = parseInt(input.value, 10) || 0;
        var net     = proposed - current;
        var badge   = document.getElementById('net-' + i);
        if (!badge) return;

        badge.textContent = (net >= 0 ? '+' : '') + net;
        badge.className   = 'crv-net ' + (net > 0 ? 'crv-net--up' : net < 0 ? 'crv-net--down' : 'crv-net--zero');

        // Mark row as changed for the "changed only" filter
        var row = input.closest('.position-row');
        if (row) row.dataset.proposed = proposed;

        recalcSummary();
    };

    // ── Summary recalculation ─────────────────────────────────────────────
    function recalcSummary() {
        var rows = document.querySelectorAll('.position-row');
        var currentTotal = 0, proposedTotal = 0, increases = 0, decreases = 0;

        rows.forEach(function (row) {
            var current  = parseInt(row.dataset.current, 10) || 0;
            var proposed = parseInt(row.dataset.proposed, 10) || current;
            var inp = row.querySelector('.proposed-input');
            if (inp) proposed = parseInt(inp.value, 10) || 0;
            currentTotal  += current;
            proposedTotal += proposed;
            if (proposed > current) increases++;
            if (proposed < current) decreases++;
        });

        var net = proposedTotal - currentTotal;
        document.getElementById('sumCurrentTotal').textContent = currentTotal.toLocaleString();
        document.getElementById('sumProposedTotal').textContent = proposedTotal.toLocaleString();
        var netEl = document.getElementById('sumNetChange');
        netEl.textContent = (net >= 0 ? '+' : '') + net.toLocaleString();
        netEl.style.color = net > 0 ? 'var(--md-success)' : net < 0 ? 'var(--md-error)' : 'var(--md-on-surface-variant)';
        document.getElementById('sumIncrease').textContent = increases;
        document.getElementById('sumDecrease').textContent = decreases;
    }

    // ── Position keyword filter ───────────────────────────────────────────
    window.filterRows = function (kw) {
        var lc = kw.toLowerCase().trim();
        document.querySelectorAll('.position-row').forEach(function (row) {
            var titleMatch = !lc || row.dataset.title.includes(lc);
            var changedOnly = document.getElementById('showChangedOnly').checked;
            var inp = row.querySelector('.proposed-input');
            var changed = inp ? parseInt(inp.value) !== parseInt(inp.dataset.current) : false;
            row.style.display = (titleMatch && (!changedOnly || changed)) ? '' : 'none';
        });
    };

    window.filterChanged = function (checked) {
        filterRows(document.getElementById('positionSearch').value);
    };

    // ── Boot ──────────────────────────────────────────────────────────────
    // Initialise net badges and summary from current values
    document.querySelectorAll('.proposed-input').forEach(function (inp) {
        onProposedChange(inp);
    });

})();
</script>
@endpush
