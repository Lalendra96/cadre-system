@extends('layouts.app')
@section('title', 'Grading Ladder — ' . $position->title)
@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
    <div style="display:flex;align-items:center;gap:10px;">
        <a href="{{ route('positions.index') }}" class="md-btn md-btn--icon" title="Back to Positions">&#8592;</a>
        <div>
            <h2 class="md-headline-sm">Grading Ladder</h2>
            <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
                {{ $position->title }} ({{ $position->code }}) — flexible grading criteria for this position.
            </p>
        </div>
    </div>
    <a href="{{ route('position-grades.create', $position) }}" class="md-btn md-btn--filled">+ Add Grade</a>
</div>

@if(session('success'))
<div style="background:var(--md-success-container,#1b3a2d);color:var(--md-on-success-container,#9ef0b3);
            padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
    ✓ {{ session('success') }}
</div>
@endif

@if($grades->isEmpty())
<div class="md-card md-card--elevated" style="padding:48px;text-align:center;color:var(--md-on-surface-variant);">
    <div style="font-size:32px;margin-bottom:10px;">🎖️</div>
    <p class="md-body-sm" style="margin-bottom:14px;">No grades configured yet for this position.</p>
    <a href="{{ route('position-grades.create', $position) }}" class="md-btn md-btn--filled">+ Add the first grade</a>
</div>
@else
<div style="display:flex;flex-direction:column;gap:12px;">
    @foreach($grades as $g)
    <div class="md-card md-card--elevated" style="padding:18px 20px;{{ !$g->is_active ? 'opacity:.5;' : '' }}">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;">
            <div style="flex:1;">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
                    <span class="md-label-md" style="font-size:15px;">{{ $g->name }}</span>
                    <span class="md-badge md-badge--neutral" style="font-size:10px;">Order: {{ $g->sort_order }}</span>
                    @if($g->is_active)
                        <span class="md-badge md-badge--success">Active</span>
                    @else
                        <span class="md-badge md-badge--neutral">Disabled</span>
                    @endif
                    @if($g->grade_records_count > 0)
                        <span class="md-body-sm" style="color:var(--md-on-surface-variant);">
                            · held by {{ $g->grade_records_count }} employee record(s)
                        </span>
                    @endif
                </div>
                @if($g->description)
                    <div class="md-body-sm" style="color:var(--md-on-surface-variant);margin-bottom:6px;">{{ $g->description }}</div>
                @endif
                <div class="md-body-sm" style="color:var(--md-primary);">
                    {{ $g->criteriaSummary() }}
                </div>
            </div>
            <div style="display:flex;gap:4px;align-items:center;flex-shrink:0;">
                <a href="{{ route('position-grades.edit', [$position, $g]) }}" class="md-btn md-btn--icon" title="Edit">✏️</a>
                <x-disable-toggle :record="$g" toggle-route="position-grades.toggle"
                    :route-params="[$position, $g]" label="grade" :require-reason="false" :small="true" />
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

@endsection
