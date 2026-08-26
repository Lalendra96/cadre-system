@extends('layouts.app')
@section('title', 'Unit Post Allocations')
@section('content')

<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
    <div>
        <h2 class="md-headline-sm">Unit Post Allocations</h2>
        <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
            Enter the planned post count and current headcount per unit and position.
            Click a unit to edit its allocation grid.
        </p>
    </div>
    <a href="{{ route('reports.unit-breakdown') }}" class="md-btn md-btn--outlined">📊 View Breakdown Report</a>
</div>

{{-- Filters ──────────────────────────────────────────────────────────── --}}
<form method="GET" style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;align-items:flex-end;">
    <div class="md-field" style="max-width:120px;">
        <label class="md-field__label">Year</label>
        <select name="year" class="md-field__input" onchange="this.form.submit()">
            @foreach($years as $y)
                <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
            @endforeach
        </select>
    </div>
    <div class="md-field" style="max-width:200px;">
        <label class="md-field__label">Unit Type</label>
        <select name="unit_type_id" class="md-field__input" onchange="this.form.submit()">
            <option value="">All types</option>
            @foreach($unitTypes as $t)
                <option value="{{ $t->id }}" {{ $unitTypeId == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
            @endforeach
        </select>
    </div>
</form>

{{-- Summary strip ────────────────────────────────────────────────────── --}}
@php
    $totalUnits      = $units->count();
    $fullyEntered    = $units->where('completion_pct', 100)->count();
    $partiallyEntered = $units->where('completion_pct', '>', 0)->where('completion_pct', '<', 100)->count();
    $notStarted      = $units->where('completion_pct', 0)->count();
@endphp
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px;">
    @foreach([
        [$totalUnits,      'Total Units',         'var(--md-primary)'],
        [$fullyEntered,    'Fully Entered',        'var(--md-success)'],
        [$partiallyEntered,'Partially Entered',   'var(--md-warning)'],
        [$notStarted,      'Not Started',          'var(--md-on-surface-variant)'],
    ] as [$val, $lbl, $col])
    <div style="background:var(--md-surface-container);border-radius:var(--md-shape-md);padding:14px;text-align:center;">
        <div style="font-size:26px;font-weight:700;color:{{ $col }};">{{ $val }}</div>
        <div style="font-size:10px;color:var(--md-on-surface-variant);text-transform:uppercase;letter-spacing:.6px;margin-top:4px;">{{ $lbl }}</div>
    </div>
    @endforeach
</div>

{{-- Unit cards ────────────────────────────────────────────────────────── --}}
@if($units->isEmpty())
    <div class="md-card md-card--elevated" style="padding:60px;text-align:center;color:var(--md-on-surface-variant);">
        <p class="md-title-md">No units found.</p>
        <a href="{{ route('units.create') }}" class="md-btn md-btn--tonal" style="margin-top:16px;">Create Units</a>
    </div>
@else
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px;">
    @foreach($units as $unit)
    @php
        $pct   = $unit->completion_pct;
        $color = $pct >= 100 ? 'var(--md-success)' : ($pct > 0 ? 'var(--md-warning)' : 'var(--md-outline-variant)');
        $badge = $pct >= 100 ? 'md-badge--success' : ($pct > 0 ? 'md-badge--warning' : 'md-badge--neutral');
    @endphp
    <a href="{{ route('unit-allocations.edit', [$unit, 'year' => $year]) }}"
       style="text-decoration:none;">
        <div class="md-card md-card--elevated" style="padding:16px;
             border-left:4px solid {{ $color }};
             transition:box-shadow .15s,transform .12s;cursor:pointer;"
             onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 16px rgba(0,0,0,.35)'"
             onmouseout="this.style.transform='';this.style.boxShadow=''">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:10px;">
                <div>
                    <div class="md-title-sm" style="color:var(--md-on-surface);">{{ $unit->name }}</div>
                    @if($unit->unitType)
                        <div class="md-body-sm" style="color:var(--md-on-surface-variant);">{{ $unit->unitType->name }}</div>
                    @endif
                </div>
                <span class="md-badge {{ $badge }}">
                    {{ $pct }}%
                </span>
            </div>

            {{-- Progress bar ──────────────────────────────────────── --}}
            <div style="height:5px;background:var(--md-outline-variant);border-radius:3px;overflow:hidden;margin-bottom:10px;">
                <div style="height:100%;width:{{ $pct }}%;background:{{ $color }};border-radius:3px;transition:width .6s;"></div>
            </div>

            <div class="md-body-sm" style="color:var(--md-on-surface-variant);">
                {{ $unit->recorded_count }} of {{ $unit->total_positions }} positions entered
                @if($unit->code) · <code style="font-size:11px;">{{ $unit->code }}</code>@endif
            </div>

            <div style="margin-top:10px;font-size:12px;color:var(--md-primary);font-weight:500;">
                {{ $pct === 100 ? '✓ Complete — click to review' : ($pct > 0 ? '→ Continue entering' : '→ Start entering') }}
            </div>
        </div>
    </a>
    @endforeach
</div>
@endif
@endsection
