@extends('layouts.app')
@section('title', 'Unit-wise Breakdown — Executive Summary')
@section('content')

<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
    <div>
        <h2 class="md-headline-sm">Unit-wise Breakdown</h2>
        <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
            Executive summary — read-only · {{ auth()->user()->category?->name }}
        </p>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
        <a href="{{ route('reports.unit-breakdown.pdf', ['year' => $year]) }}" class="md-btn md-btn--outlined">🖨 Print / PDF</a>
    </div>
</div>

{{-- Year selector — the only control on this page, deliberately kept simple --}}
<div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;flex-wrap:wrap;">
    <span class="md-label-md">Year:</span>
    @foreach($years as $y)
        <a href="{{ route('executive.unit-breakdown', ['year' => $y]) }}"
           class="md-btn {{ $y == $year ? 'md-btn--tonal' : 'md-btn--outlined' }}"
           style="min-width:70px;">{{ $y }}</a>
    @endforeach
</div>

{{-- ── Narrative summary ────────────────────────────────────────────── --}}
<div class="md-card md-card--elevated" style="padding:22px 26px;margin-bottom:20px;
            background:linear-gradient(135deg, var(--md-surface-container) 0%, var(--md-surface-container-high) 100%);
            border-left:4px solid var(--md-primary);">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
        <span style="font-size:18px;">📋</span>
        <span class="md-label-md" style="color:var(--md-primary);">Summary</span>
    </div>
    <p class="md-body-md" style="line-height:1.7;color:var(--md-on-surface);margin:0;">
        {{ $narrative }}
    </p>
</div>

{{-- ── Key figures ──────────────────────────────────────────────────── --}}
@php
    $totalApproved = $rows->sum('approvedAmt');
    $totalActual   = $rows->sum('totalInPos');
    $totalVacancy  = $rows->sum('vacancy');
    $overallFillPct = $totalApproved > 0 ? round($totalActual / $totalApproved * 100) : 0;
@endphp
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px;">
    @foreach([
        [number_format($totalApproved), 'Total Approved Posts', 'var(--md-primary)'],
        [number_format($totalActual),   'Currently Filled',     'var(--md-secondary,var(--md-primary))'],
        [number_format($totalVacancy),  'Total Vacant',          $totalVacancy > 0 ? 'var(--md-error)' : 'var(--md-on-surface-variant)'],
        [$overallFillPct . '%',         'Overall Fill Rate',     $overallFillPct >= 90 ? 'var(--md-success,#2e7d32)' : ($overallFillPct >= 70 ? '#ff9800' : 'var(--md-error)')],
    ] as [$val, $lbl, $color])
    <div style="background:var(--md-surface-container);border-radius:var(--md-shape-md);padding:16px;text-align:center;">
        <div style="font-size:28px;font-weight:700;color:{{ $color }};">{{ $val }}</div>
        <div style="font-size:11px;color:var(--md-on-surface-variant);text-transform:uppercase;letter-spacing:.6px;margin-top:4px;">{{ $lbl }}</div>
    </div>
    @endforeach
</div>

{{-- ── Position table ───────────────────────────────────────────────── --}}
<div class="md-card md-card--elevated">
    <div style="padding:14px 20px;border-bottom:1px solid var(--md-outline-variant);">
        <span class="md-label-md">By Position</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="md-table">
            <thead>
                <tr>
                    <th>Position</th>
                    <th style="text-align:right;">Approved</th>
                    <th style="text-align:right;">Filled</th>
                    <th style="text-align:right;">Vacant</th>
                    <th style="text-align:right;">Fill %</th>
                    <th>Weakest Unit</th>
                    <th style="text-align:center;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows->sortBy('fillPct') as $r)
                @php
                    $isCritical = $r->approvedAmt > 0 && ($r->vacancy / max($r->approvedAmt,1)) * 100 >= $threshold;
                    $fillColor  = $r->fillPct >= 90 ? 'var(--md-success,#2e7d32)' : ($r->fillPct >= 70 ? '#ff9800' : 'var(--md-error)');
                    $weakest    = $r->unitBreakdown->sortBy('actual')->first();
                @endphp
                <tr style="{{ $isCritical ? 'background:color-mix(in srgb,var(--md-error) 5%,transparent);' : '' }}">
                    <td class="md-label-md">{{ $r->position->title }}</td>
                    <td style="text-align:right;">{{ number_format($r->approvedAmt) }}</td>
                    <td style="text-align:right;font-weight:600;">{{ number_format($r->totalInPos) }}</td>
                    <td style="text-align:right;color:{{ $r->vacancy > 0 ? 'var(--md-error)' : 'var(--md-on-surface-variant)' }};">
                        {{ number_format($r->vacancy) }}
                    </td>
                    <td style="text-align:right;color:{{ $fillColor }};font-weight:600;">{{ $r->fillPct }}%</td>
                    <td class="md-body-sm" style="color:var(--md-on-surface-variant);">
                        @if($weakest && $weakest->actual === 0)
                            {{ $weakest->unit->name }} <span style="color:var(--md-error);">(none placed)</span>
                        @elseif($weakest)
                            {{ $weakest->unit->name }} ({{ $weakest->actual }})
                        @else
                            —
                        @endif
                    </td>
                    <td style="text-align:center;">
                        @if($isCritical)
                            <span class="md-badge md-badge--error">⚠ Critical</span>
                        @elseif($r->fillPct >= 90)
                            <span class="md-badge md-badge--success">Healthy</span>
                        @else
                            <span class="md-badge md-badge--neutral">Watch</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="md-table__empty">No data available for {{ $year }}.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(!$hasAllocationData)
<p class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:12px;">
    ℹ Figures are derived from employee profile records — no Unit Post Allocation entries exist for {{ $year }} yet.
</p>
@endif

@endsection
