@extends('layouts.app')
@section('title', 'Unit-wise Post Availability')

@push('head')
<style>
.ub-grid-wrap { overflow-x: auto; }
.ub-table {
    width: 100%; border-collapse: collapse; font-size: 12px;
    min-width: 600px;
}
.ub-table th, .ub-table td {
    padding: 8px 10px; border: 1px solid var(--md-outline-variant);
    white-space: nowrap;
}
.ub-table thead th {
    background: var(--md-surface-container-highest);
    color: var(--md-on-surface); font-weight: 600;
    position: sticky; top: 0; z-index: 2;
}
.ub-table thead th.ub-pos-col {
    position: sticky; left: 0; z-index: 3;
    min-width: 200px; max-width: 200px;
    background: var(--md-surface-container-highest);
}
.ub-table td.ub-pos-cell {
    position: sticky; left: 0; z-index: 1;
    background: var(--md-surface-container);
    font-weight: 500; min-width: 200px; max-width: 200px;
    white-space: normal; word-break: break-word;
}
.ub-table td.ub-num { text-align: right; font-variant-numeric: tabular-nums; }
.ub-table tr:hover td { background: color-mix(in srgb, var(--md-primary) 5%, transparent); }
.ub-table tr:hover td.ub-pos-cell { background: color-mix(in srgb, var(--md-primary) 8%, var(--md-surface-container)); }
.ub-cell-nonzero { font-weight: 600; color: var(--md-primary); }
.ub-cell-zero    { color: var(--md-outline); }
.ub-alert-row td { border-left: 3px solid var(--md-error); }
.ub-fill-bar {
    display: inline-block; height: 5px; border-radius: 3px; min-width: 2px;
    vertical-align: middle; margin-left: 4px;
}
/* Summary KPI strip */
.ub-kpi { display: flex; gap: 16px; flex-wrap: wrap; }
.ub-kpi-card {
    flex: 1; min-width: 120px;
    background: var(--md-surface-container);
    border-radius: var(--md-shape-sm);
    padding: 14px 16px; text-align: center;
}
.ub-kpi-val { font-size: 26px; font-weight: 700; line-height: 1.1; }
.ub-kpi-lbl { font-size: 10px; color: var(--md-on-surface-variant); text-transform: uppercase; letter-spacing: .7px; margin-top: 4px; }
</style>
@endpush

@section('content')
<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
    <div>
        <h2 class="md-headline-sm">Unit-wise Post Availability</h2>
        <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
            Current staff in-post per position broken down by unit.
            Source: Employee Profiles. Use filters to focus on a specific unit type or position.
        </p>
    </div>
    <a href="{{ route('reports.export.moh-csv', ['year'=>$year]) }}" class="md-btn md-btn--outlined">⬇ CSV Export</a>
    <a href="{{ route('reports.unit-breakdown.pdf', request()->query()) }}" class="md-btn md-btn--outlined">📄 PDF</a>
    <a href="{{ route('unit-allocations.index', ['year'=>$year]) }}" class="md-btn md-btn--tonal">✏️ Update Allocations</a>
</div>

{{-- Data source notice ────────────────────────────────────────────────── --}}
<div style="background:var(--md-surface-container);border-radius:var(--md-shape-sm);
            padding:10px 16px;margin-bottom:16px;font-size:12px;
            color:var(--md-on-surface-variant);display:flex;align-items:center;gap:8px;">
    @if(isset($hasAllocationData) && $hasAllocationData)
        <span style="color:var(--md-success);font-size:15px;">●</span>
        Headcount figures are sourced from <strong>Unit Post Allocations</strong> (entered by Planning Officer).
        Cells without an allocation entry fall back to employee profile counts.
    @else
        <span style="color:var(--md-warning);font-size:15px;">●</span>
        No allocation data entered yet. Figures are derived from <strong>Employee Profiles</strong> only.
        <a href="{{ route('unit-allocations.index', ['year'=>$year]) }}" style="color:var(--md-primary);">Enter allocations →</a>
    @endif
</div>

{{-- Filters ──────────────────────────────────────────────────────────── --}}
<form method="GET" class="md-card md-card--outlined"
      style="padding:14px 18px;margin-bottom:16px;display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">

    <div class="md-field" style="min-width:160px;">
        <label class="md-field__label">Year</label>
        <select name="year" class="md-field__input" onchange="this.form.submit()">
            @foreach($years as $y)
                <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
            @endforeach
        </select>
    </div>

    <div class="md-field" style="min-width:180px;">
        <label class="md-field__label">Unit Type</label>
        <select name="unit_type_id" class="md-field__input" onchange="this.form.submit()">
            <option value="">All unit types</option>
            @foreach($unitTypes as $t)
                <option value="{{ $t->id }}" {{ $unitTypeId == $t->id ? 'selected' : '' }}>
                    {{ $t->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="md-field" style="min-width:200px;">
        <label class="md-field__label">Position</label>
        <select name="position_id" class="md-field__input" onchange="this.form.submit()">
            <option value="">All positions</option>
            @foreach($allPositions as $p)
                <option value="{{ $p->id }}" {{ $positionId == $p->id ? 'selected' : '' }}>
                    {{ $p->title }}
                </option>
            @endforeach
        </select>
    </div>

    @if($unitTypeId || $positionId)
        <a href="{{ route('reports.unit-breakdown', ['year' => $year]) }}"
           class="md-btn md-btn--text" style="align-self:flex-end;height:40px;">Clear</a>
    @endif
</form>

{{-- Summary KPIs ─────────────────────────────────────────────────────── --}}
@php
    $totalApproved   = $rows->sum('approvedAmt');
    $totalInPosition = $rows->sum('totalInPos');
    $totalVacancy    = $rows->sum('vacancy');
    $overallFill     = $totalApproved > 0
        ? round(($totalInPosition / $totalApproved) * 100)
        : 0;
@endphp
<div class="ub-kpi" style="margin-bottom:16px;">
    <div class="ub-kpi-card">
        <div class="ub-kpi-val">{{ $rows->count() }}</div>
        <div class="ub-kpi-lbl">Positions shown</div>
    </div>
    <div class="ub-kpi-card">
        <div class="ub-kpi-val">{{ $units->count() }}</div>
        <div class="ub-kpi-lbl">Units shown</div>
    </div>
    <div class="ub-kpi-card">
        <div class="ub-kpi-val" style="color:var(--md-primary);">{{ number_format($totalApproved) }}</div>
        <div class="ub-kpi-lbl">Total approved</div>
    </div>
    <div class="ub-kpi-card">
        <div class="ub-kpi-val" style="color:var(--md-secondary);">{{ number_format($totalInPosition) }}</div>
        <div class="ub-kpi-lbl">In post</div>
    </div>
    <div class="ub-kpi-card">
        <div class="ub-kpi-val" style="color:{{ $totalVacancy > 0 ? 'var(--md-error)' : 'var(--md-success)' }};">
            {{ number_format($totalVacancy) }}
        </div>
        <div class="ub-kpi-lbl">Vacancies</div>
    </div>
    <div class="ub-kpi-card">
        <div class="ub-kpi-val" style="color:{{ $overallFill >= 90 ? 'var(--md-success)' : ($overallFill >= 70 ? 'var(--md-warning)' : 'var(--md-error)') }};">
            {{ $overallFill }}%
        </div>
        <div class="ub-kpi-lbl">Overall fill rate</div>
    </div>
</div>

{{-- Critical positions alert strip ───────────────────────────────────── --}}
@if($criticalPositions->isNotEmpty())
<div style="background:color-mix(in srgb, var(--md-error) 10%, transparent);
            border:1px solid var(--md-error);border-radius:var(--md-shape-sm);
            padding:12px 16px;margin-bottom:16px;">
    <div class="md-label-md" style="color:var(--md-error);margin-bottom:6px;">
        ⚠ {{ $criticalPositions->count() }} {{ \Illuminate\Support\Str::plural('position', $criticalPositions->count()) }}
        with vacancy ≥ {{ $threshold }}% of approved cadre
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:6px;">
        @foreach($criticalPositions as $r)
            <span class="md-badge md-badge--critical">
                {{ $r->position->title }} — {{ $r->vacancy }} vacant ({{ 100 - $r->fillPct }}%)
            </span>
        @endforeach
    </div>
</div>
@endif

{{-- Unit breakdown matrix ─────────────────────────────────────────────── --}}
@if($rows->isEmpty())
    <div class="md-card md-card--elevated" style="padding:60px;text-align:center;color:var(--md-on-surface-variant);">
        <p class="md-title-md">No data available for the selected filters.</p>
        <p class="md-body-sm" style="margin-top:8px;">
            Ensure employees have their <strong>Unit</strong> and <strong>Position</strong> set
            in their profile records.
        </p>
        <a href="{{ route('employees.index') }}" class="md-btn md-btn--tonal" style="margin-top:16px;">
            View Employee Profiles
        </a>
    </div>
@else
<div class="md-card md-card--elevated">
    <div class="ub-grid-wrap" style="max-height:70vh;overflow:auto;">
        <table class="ub-table" id="ubTable">
            <thead>
                <tr>
                    <th class="ub-pos-col">Position</th>
                    <th class="ub-num">Approved</th>
                    <th class="ub-num">Total In Post</th>
                    <th class="ub-num">Vacancy</th>
                    <th class="ub-num">Fill %</th>
                    @foreach($units as $unit)
                        <th class="ub-num" title="{{ $unit->unitType?->name ?? 'No type' }}">
                            <div style="max-width:90px;overflow:hidden;text-overflow:ellipsis;">
                                {{ $unit->name }}
                            </div>
                            @if($unit->code)
                                <div style="font-size:10px;color:var(--md-on-surface-variant);font-weight:400;">{{ $unit->code }}</div>
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $r)
                @php
                    $isAlert = $r->approvedAmt > 0
                        && $r->vacancy / max($r->approvedAmt, 1) * 100 >= $threshold;
                    $fillColor = $r->fillPct >= 90 ? 'var(--md-success)'
                               : ($r->fillPct >= 70 ? 'var(--md-warning)'
                               : 'var(--md-error)');
                @endphp
                <tr class="{{ $isAlert ? 'ub-alert-row' : '' }}">
                    <td class="ub-pos-cell">
                        {{ $r->position->title }}
                        @if($isAlert)
                            <span class="md-badge md-badge--critical" style="font-size:9px;margin-left:4px;">!</span>
                        @endif
                        @if($r->excludedRemarks->isNotEmpty())
                            @php
                                $remarkTotal = $r->excludedRemarks->sum('actual');
                                $remarkTitle = $r->excludedRemarks->map(fn ($x) =>
                                    "{$x->subcategory->name} — {$x->actual} at {$x->unit->name}"
                                )->implode('; ');
                            @endphp
                            <span class="md-badge md-badge--warning" style="font-size:9px;margin-left:4px;" title="{{ $remarkTitle }}">
                                +{{ $remarkTotal }} tracked, excluded
                            </span>
                        @endif
                        @unless($r->reconciles)
                            <span class="md-badge md-badge--error" style="font-size:9px;margin-left:4px;"
                                  title="Unit breakdown total ({{ $r->totalInPos }}) does not match this position's monthly-entry total ({{ $r->monthlyEntryTotal }}), hospital-wide. These are entered independently — one may be stale.">
                                ⚠ {{ $r->totalInPos }} vs {{ $r->monthlyEntryTotal }} monthly
                            </span>
                        @endunless
                        @if(auth()->user()->isSuperAdmin() && $r->unboundDataWarnings->isNotEmpty())
                            @php
                                $unboundTitle = $r->unboundDataWarnings->map(fn ($w) => "{$w->unit->name} ({$w->actual})")->implode('; ');
                            @endphp
                            <span class="md-badge md-badge--warning" style="font-size:9px;margin-left:4px;"
                                  title="This position has headcount data in {{ $r->unboundDataWarnings->count() }} unit(s) it isn't bound to: {{ $unboundTitle }}. Possible data-entry mistake — check Unit-Position Bindings.">
                                🔗 not bound in {{ $r->unboundDataWarnings->count() }} unit(s)
                            </span>
                        @endif
                    </td>
                    <td class="ub-num">{{ $r->approvedAmt ?: '—' }}</td>
                    <td class="ub-num" style="font-weight:600;">{{ $r->totalInPos }}</td>
                    <td class="ub-num">
                        @if($r->vacancy > 0)
                            <span style="color:var(--md-error);font-weight:600;">{{ $r->vacancy }}</span>
                        @else
                            <span style="color:var(--md-success);">0</span>
                        @endif
                    </td>
                    <td class="ub-num">
                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:4px;">
                            <span style="color:{{ $fillColor }};">{{ $r->fillPct }}%</span>
                            <div class="ub-fill-bar" style="width:{{ min($r->fillPct, 100) * 0.5 }}px;background:{{ $fillColor }};"></div>
                        </div>
                    </td>
                    @foreach($r->unitCells as $count)
                        <td class="ub-num {{ $count > 0 ? 'ub-cell-nonzero' : 'ub-cell-zero' }}">
                            {{ $count > 0 ? $count : '—' }}
                        </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:var(--md-surface-container-highest);font-weight:700;">
                    <td class="ub-pos-cell" style="background:var(--md-surface-container-highest);">TOTAL</td>
                    <td class="ub-num">{{ number_format($totalApproved) }}</td>
                    <td class="ub-num">{{ number_format($totalInPosition) }}</td>
                    <td class="ub-num" style="color:{{ $totalVacancy > 0 ? 'var(--md-error)' : 'var(--md-success)' }};">
                        {{ number_format($totalVacancy) }}
                    </td>
                    <td class="ub-num">{{ $overallFill }}%</td>
                    @foreach($units as $unit)
                        @php($colTotal = $rows->sum(fn ($r) => $r->unitCells[$loop->index] ?? 0))
                        <td class="ub-num">{{ $colTotal > 0 ? $colTotal : '—' }}</td>
                    @endforeach
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Legend ──────────────────────────────────────────────────────── --}}
    <div style="padding:12px 18px;border-top:1px solid var(--md-outline-variant);
                display:flex;gap:16px;flex-wrap:wrap;align-items:center;">
        <span class="md-body-sm" style="color:var(--md-on-surface-variant);">Fill rate:</span>
        <span style="font-size:12px;color:var(--md-success);">● ≥ 90% Good</span>
        <span style="font-size:12px;color:var(--md-warning);">● 70–89% Moderate</span>
        <span style="font-size:12px;color:var(--md-error);">● &lt; 70% Critical</span>
        <span class="md-body-sm" style="color:var(--md-on-surface-variant);margin-left:auto;">
            Alert threshold: {{ $threshold }}% vacancy &nbsp;|&nbsp;
            Data as at: {{ now()->format('d M Y H:i') }}
        </span>
    </div>
</div>
@endif

@endsection
