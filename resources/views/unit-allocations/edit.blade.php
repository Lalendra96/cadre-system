@extends('layouts.app')
@section('title', 'Post Allocation — ' . $unit->name)

@push('head')
<style>
.ua-grid { border-collapse: collapse; width: 100%; font-size: 13px; }
.ua-grid th, .ua-grid td {
    padding: 9px 12px;
    border-bottom: 1px solid var(--md-outline-variant);
    vertical-align: middle;
}
.ua-grid thead th {
    background: var(--md-surface-container-highest);
    font-weight: 600; color: var(--md-on-surface);
    white-space: nowrap; position: sticky; top: 0; z-index: 2;
}
.ua-grid tbody tr:hover { background: color-mix(in srgb, var(--md-primary) 4%, transparent); }
.ua-grid td.ua-pos  { min-width: 200px; font-weight: 500; color: var(--md-on-surface); }
.ua-grid td.ua-ref  { color: var(--md-on-surface-variant); text-align: right; font-size: 12px; }
.ua-grid td.ua-num  { text-align: right; }
.ua-input {
    width: 80px; text-align: right; padding: 5px 8px; font-size: 13px;
    background: var(--md-surface-container);
    color: var(--md-on-surface);
    border: 1px solid var(--md-outline);
    border-radius: var(--md-shape-xs, 4px);
    outline: none;
    transition: border-color .15s;
}
.ua-input:focus { border-color: var(--md-primary); }
.ua-input:invalid { border-color: var(--md-error); }
.ua-notes {
    width: 100%; font-size: 12px; padding: 4px 8px;
    background: var(--md-surface-container);
    color: var(--md-on-surface);
    border: 1px solid var(--md-outline);
    border-radius: var(--md-shape-xs, 4px);
    outline: none; resize: none;
}
.ua-notes:focus { border-color: var(--md-primary); }
.ua-vacancy { display: inline-block; min-width: 32px; text-align: center; font-weight: 600; }
.ua-fill-bar {
    display: inline-block; height: 4px; border-radius: 2px;
    vertical-align: middle; margin-left: 4px; transition: width .3s;
}
.ua-changed { background: color-mix(in srgb, var(--md-primary) 6%, transparent) !important; }
.ua-sticky-footer {
    position: sticky; bottom: 0; z-index: 5;
    background: var(--md-surface-container-highest);
    border-top: 2px solid var(--md-outline-variant);
    padding: 12px 20px;
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; flex-wrap: wrap;
}
</style>
@endpush

@section('content')

{{-- Breadcrumb + header ────────────────────────────────────────────── --}}
<div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
    <a href="{{ route('unit-allocations.index', ['year'=>$year]) }}"
       class="md-btn md-btn--icon" title="Back to all units">&#8592;</a>
    <h2 class="md-headline-sm">{{ $unit->name }}</h2>
    @if($unit->unitType)
        <span class="md-badge md-badge--info">{{ $unit->unitType->name }}</span>
    @endif
    @if($unit->code)
        <code style="font-size:12px;color:var(--md-on-surface-variant);">{{ $unit->code }}</code>
    @endif
    @if($isScoped)
        <span class="md-badge md-badge--success" title="Showing only positions Super Admin has bound to this unit">
            {{ $positions->count() }} position(s) bound
        </span>
    @else
        <span class="md-badge md-badge--warning" title="No positions have been bound to this unit yet — showing every position hospital-wide">
            ⚠ Showing all positions — not scoped yet
        </span>
    @endif
    @if(auth()->user()->isSuperAdmin())
        <a href="{{ route('unit-position-bindings.edit', $unit) }}" class="md-btn md-btn--outlined" style="font-size:11px;margin-left:auto;">
            ⚙️ Manage Positions for This Unit
        </a>
    @endif
</div>
<p class="md-body-sm" style="color:var(--md-on-surface-variant);margin-bottom:20px;margin-left:48px;">
    Enter the number of posts <strong>allocated</strong> to this unit and the
    <strong>current headcount</strong> per position.
    The <em>Profile Count</em> column is auto-calculated from employee records (read-only reference).
</p>

{{-- Year selector ───────────────────────────────────────────────────── --}}
<div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
    <span class="md-label-md">Year:</span>
    @foreach($years as $y)
        <a href="{{ route('unit-allocations.edit', [$unit, 'year' => $y]) }}"
           class="md-btn {{ $y == $year ? 'md-btn--tonal' : 'md-btn--outlined' }}"
           style="min-width:70px;">{{ $y }}</a>
    @endforeach
</div>

@if(session('success'))
<div style="background:var(--md-success-container);color:var(--md-on-success-container);
            padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
    {{ session('success') }}
</div>
@endif

@if($errors->any())
<div style="background:var(--md-error-container);color:var(--md-on-error-container);
            padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
    <div style="font-weight:600;margin-bottom:4px;">Please correct the following:</div>
    <ul style="margin:0;padding-left:16px;">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

{{-- Main form ───────────────────────────────────────────────────────── --}}
<form method="POST" action="{{ route('unit-allocations.update', $unit) }}" id="allocForm">
    @csrf @method('PUT')
    <input type="hidden" name="year" value="{{ $year }}">

    <div class="md-card md-card--elevated">
        <div style="overflow-x:auto;max-height:72vh;overflow-y:auto;">
            <table class="ua-grid" id="allocTable">
                <thead>
                    <tr>
                        <th class="ua-pos" style="min-width:200px;">Position</th>
                        <th style="text-align:right;min-width:90px;">
                            Allocated Posts
                            <div style="font-size:10px;font-weight:400;color:var(--md-on-surface-variant);">planned / approved</div>
                        </th>
                        <th style="text-align:right;min-width:90px;">
                            Actual In Post
                            <div style="font-size:10px;font-weight:400;color:var(--md-on-surface-variant);">current headcount</div>
                        </th>
                        <th style="text-align:right;min-width:80px;">Vacancy</th>
                        <th style="text-align:right;min-width:70px;">Fill %</th>
                        <th style="text-align:right;min-width:80px;">Profile Count
                            <div style="font-size:10px;font-weight:400;color:var(--md-on-surface-variant);">from employee records</div>
                        </th>
                        <th style="min-width:200px;">Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $subIndex = 0;
                    @endphp
                    @foreach($positions as $i => $pos)
                    @php
                        $alloc   = $existing->get($pos->id);
                        $allocPosts  = $alloc?->allocated_posts ?? 0;
                        $actualInPost = $alloc?->actual_in_post ?? 0;
                        $profileCount = (int) ($profileCounts->get($pos->id, 0));
                        $approvedTotal = $approvedCarder->get($pos->id, 0);
                        $vacancy = max($allocPosts - $actualInPost, 0);
                        $fillPct = $allocPosts > 0 ? round($actualInPost / $allocPosts * 100) : 0;
                        $fillColor = $fillPct >= 90 ? 'var(--md-success)' : ($fillPct >= 70 ? 'var(--md-warning)' : ($allocPosts > 0 ? 'var(--md-error)' : 'var(--md-outline)'));
                    @endphp
                    <tr id="row-{{ $pos->id }}" class="{{ ($allocPosts > 0 || $actualInPost > 0) ? 'ua-changed' : '' }}">
                        <td class="ua-pos">
                            {{ $pos->title }}
                            <input type="hidden" name="rows[{{ $i }}][position_id]" value="{{ $pos->id }}">
                            @if($approvedTotal > 0)
                                <div style="font-size:10px;color:var(--md-on-surface-variant);">Hospital-wide approved: {{ $approvedTotal }}</div>
                            @endif
                        </td>
                        <td class="ua-num">
                            <input type="number"
                                   name="rows[{{ $i }}][allocated_posts]"
                                   class="ua-input alloc-input"
                                   value="{{ $allocPosts }}"
                                   min="0" max="9999"
                                   data-row="{{ $pos->id }}"
                                   onchange="recalcRow('{{ $pos->id }}')"
                                   oninput="recalcRow('{{ $pos->id }}')"
                                   title="Planned/approved posts for this unit">
                        </td>
                        <td class="ua-num">
                            <input type="number"
                                   name="rows[{{ $i }}][actual_in_post]"
                                   class="ua-input actual-input"
                                   value="{{ $actualInPost }}"
                                   min="0" max="9999"
                                   data-row="{{ $pos->id }}"
                                   onchange="recalcRow('{{ $pos->id }}')"
                                   oninput="recalcRow('{{ $pos->id }}')"
                                   title="Current headcount in this unit">
                        </td>
                        <td class="ua-num">
                            <span class="ua-vacancy" id="vac-{{ $pos->id }}"
                                  style="color:{{ $vacancy > 0 ? 'var(--md-error)' : 'var(--md-success)' }};">
                                {{ $vacancy > 0 ? $vacancy : '0' }}
                            </span>
                        </td>
                        <td class="ua-num">
                            <span id="fill-{{ $pos->id }}" style="color:{{ $fillColor }};font-weight:600;">{{ $fillPct }}%</span>
                            <div class="ua-fill-bar" id="fillbar-{{ $pos->id }}"
                                 style="width:{{ min($fillPct, 100) * 0.5 }}px;background:{{ $fillColor }};"></div>
                        </td>
                        <td class="ua-ref">
                            @if($profileCount > 0)
                                <span style="color:var(--md-primary);font-weight:600;">{{ $profileCount }}</span>
                            @else
                                <span style="color:var(--md-outline);">—</span>
                            @endif
                        </td>
                        <td>
                            <input type="text"
                                   name="rows[{{ $i }}][notes]"
                                   class="ua-notes"
                                   value="{{ old("rows.{$i}.notes", $alloc?->notes ?? '') }}"
                                   placeholder="e.g. 2 on no-pay, 1 acting up"
                                   maxlength="300">
                        </td>
                    </tr>
                    @if($pos->subcategories->isNotEmpty())
                        @foreach($pos->subcategories as $sub)
                        @php
                            $subKey = "{$pos->id}:{$sub->id}";
                            $subAlloc = $existingSubcategoryRows->get($subKey);
                            $subAllocPosts = $subAlloc?->allocated_posts ?? 0;
                            $subActualInPost = $subAlloc?->actual_in_post ?? 0;
                            $subIndex = ($subIndex ?? 0);
                        @endphp
                        <tr style="background:color-mix(in srgb, var(--md-primary) 3%, transparent);">
                            <td class="ua-pos" style="padding-left:28px;font-weight:400;font-size:12.5px;color:var(--md-on-surface-variant);">
                                ↳ {{ $sub->name }}
                                @unless($sub->counts_toward_parent_total)
                                    <span class="md-badge md-badge--warning" style="font-size:9px;margin-left:6px;" title="Tracked here, but not added to {{ $pos->title }}'s total above">excluded from total</span>
                                @endunless
                                <input type="hidden" name="sub_rows[{{ $subIndex }}][position_id]" value="{{ $pos->id }}">
                                <input type="hidden" name="sub_rows[{{ $subIndex }}][subcategory_id]" value="{{ $sub->id }}">
                            </td>
                            <td class="ua-num">
                                <input type="number" name="sub_rows[{{ $subIndex }}][allocated_posts]"
                                       class="ua-input" value="{{ $subAllocPosts }}" min="0" max="9999" style="width:64px;">
                            </td>
                            <td class="ua-num">
                                <input type="number" name="sub_rows[{{ $subIndex }}][actual_in_post]"
                                       class="ua-input" value="{{ $subActualInPost }}" min="0" max="9999" style="width:64px;">
                            </td>
                            <td class="ua-num" style="color:var(--md-on-surface-variant);font-size:11px;">—</td>
                            <td class="ua-num" style="color:var(--md-on-surface-variant);font-size:11px;">—</td>
                            <td class="ua-ref">—</td>
                            <td>
                                <input type="text" name="sub_rows[{{ $subIndex }}][notes]" class="ua-notes"
                                       value="{{ old("sub_rows.{$subIndex}.notes", $subAlloc?->notes ?? '') }}"
                                       placeholder="Optional note" maxlength="300">
                            </td>
                        </tr>
                        @php
                            $subIndex++;
                        @endphp
                        @endforeach
                    @endif
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background:var(--md-surface-container-highest);font-weight:700;">
                        <td class="ua-pos">TOTAL</td>
                        <td class="ua-num" id="totalAlloc">
                            {{ $existing->sum('allocated_posts') }}
                        </td>
                        <td class="ua-num" id="totalActual">
                            {{ $existing->sum('actual_in_post') }}
                        </td>
                        <td class="ua-num" id="totalVac" style="color:var(--md-error);">
                            {{ $existing->sum(fn($a) => max($a->allocated_posts - $a->actual_in_post, 0)) }}
                        </td>
                        <td class="ua-num" id="totalFill">
                            @php
                                $tA = $existing->sum('allocated_posts');
                                $tI = $existing->sum('actual_in_post');
                            @endphp
                            {{ $tA > 0 ? round($tI / $tA * 100) : 0 }}%
                        </td>
                        <td class="ua-ref">
                            {{ $profileCounts->sum() }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Sticky footer actions ─────────────────────────────────────── --}}
        <div class="ua-sticky-footer">
            <div class="md-body-sm" style="color:var(--md-on-surface-variant);">
                Last updated:
                @php
                    $lastUpdate = $existing->sortByDesc('updated_at')->first();
                @endphp
                @if($lastUpdate)
                    {{ $lastUpdate->updated_at->format('d M Y H:i') }}
                    by {{ $lastUpdate->lastUpdatedBy?->name ?? 'Unknown' }}
                @else
                    Not yet saved for {{ $year }}
                @endif
            </div>
            <div style="display:flex;gap:10px;align-items:center;">
                <button type="button" class="md-btn md-btn--text" onclick="clearZeros()">Clear Unchanged</button>
                <a href="{{ route('unit-allocations.index', ['year'=>$year]) }}" class="md-btn md-btn--outlined">Cancel</a>
                <button type="submit" class="md-btn md-btn--filled">
                    💾 Save {{ $unit->name }} — {{ $year }}
                </button>
            </div>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    // ── Live vacancy / fill recalculation ─────────────────────────────────
    window.recalcRow = function (posId) {
        var row    = document.getElementById('row-' + posId);
        if (!row) return;

        var allocEl  = row.querySelector('.alloc-input');
        var actualEl = row.querySelector('.actual-input');
        var alloc    = parseInt(allocEl.value, 10)  || 0;
        var actual   = parseInt(actualEl.value, 10) || 0;
        var vacancy  = Math.max(alloc - actual, 0);
        var fillPct  = alloc > 0 ? Math.round(actual / alloc * 100) : 0;
        var fillColor = fillPct >= 90 ? 'var(--md-success)'
                      : fillPct >= 70 ? 'var(--md-warning)'
                      : (alloc > 0   ? 'var(--md-error)' : 'var(--md-outline)');

        var vacEl  = document.getElementById('vac-'     + posId);
        var fillEl = document.getElementById('fill-'    + posId);
        var barEl  = document.getElementById('fillbar-' + posId);

        if (vacEl) {
            vacEl.textContent = vacancy;
            vacEl.style.color = vacancy > 0 ? 'var(--md-error)' : 'var(--md-success)';
        }
        if (fillEl) {
            fillEl.textContent = fillPct + '%';
            fillEl.style.color = fillColor;
        }
        if (barEl) {
            barEl.style.width      = Math.min(fillPct, 100) * 0.5 + 'px';
            barEl.style.background = fillColor;
        }

        // Highlight row if any value entered
        row.classList.toggle('ua-changed', alloc > 0 || actual > 0);

        recalcTotals();
    };

    // ── Totals footer recalculation ───────────────────────────────────────
    function recalcTotals() {
        var rows     = document.querySelectorAll('#allocTable tbody tr');
        var totAlloc = 0, totActual = 0, totVac = 0;

        rows.forEach(function (row) {
            var a = parseInt(row.querySelector('.alloc-input')?.value,  10) || 0;
            var b = parseInt(row.querySelector('.actual-input')?.value, 10) || 0;
            totAlloc  += a;
            totActual += b;
            totVac    += Math.max(a - b, 0);
        });

        var fill = totAlloc > 0 ? Math.round(totActual / totAlloc * 100) : 0;

        setText('totalAlloc',  totAlloc);
        setText('totalActual', totActual);
        setText('totalVac',    totVac);
        setText('totalFill',   fill + '%');
    }

    function setText(id, val) {
        var el = document.getElementById(id);
        if (el) el.textContent = val;
    }

    // ── Clear unchanged rows (both inputs 0) ─────────────────────────────
    window.clearZeros = function () {
        document.querySelectorAll('#allocTable tbody tr').forEach(function (row) {
            var aEl = row.querySelector('.alloc-input');
            var bEl = row.querySelector('.actual-input');
            if (!aEl || !bEl) return;
            if (parseInt(aEl.value, 10) === 0 && parseInt(bEl.value, 10) === 0) {
                row.classList.remove('ua-changed');
            }
        });
    };

    // ── Quick-fill: press Tab to move between rows in the same column ─────
    document.querySelectorAll('.ua-input').forEach(function (inp) {
        inp.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter') return;
            e.preventDefault();
            // Move to next input in the same column
            var all  = Array.from(document.querySelectorAll(
                '.' + (inp.classList.contains('alloc-input') ? 'alloc-input' : 'actual-input')
            ));
            var idx = all.indexOf(inp);
            if (idx !== -1 && all[idx + 1]) all[idx + 1].focus();
        });
    });

    // Warn before leaving with unsaved changes
    var pristine = document.getElementById('allocForm').innerHTML;
    function warnUnsavedChanges(e) {
        if (document.getElementById('allocForm').innerHTML !== pristine) {
            e.preventDefault();
            e.returnValue = '';
        }
    }
    window.addEventListener('beforeunload', warnUnsavedChanges);
    document.getElementById('allocForm').addEventListener('submit', function () {
        window.removeEventListener('beforeunload', warnUnsavedChanges);
    });

})();
</script>
@endpush
