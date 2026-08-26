@extends('layouts.app')
@section('title', 'Unit-Position Bindings')
@section('content')

<div style="margin-bottom:16px;">
    <h2 class="md-headline-sm">Unit-Position Bindings</h2>
    <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
        Choose which positions are relevant to each unit — the Unit Post Allocation entry screen only shows
        bound positions once configured, instead of the full position catalog on every unit. A unit with no
        bindings yet still shows every position (nothing existing is ever hidden).
    </p>
</div>

@if(session('success'))
<div style="background:var(--md-success-container,#1b3a2d);color:var(--md-on-success-container,#9ef0b3);padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">✓ {{ session('success') }}</div>
@endif

<form method="GET" style="margin-bottom:16px;">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Search units…" class="md-field__input" style="max-width:280px;">
    <button type="submit" class="md-btn md-btn--outlined">Search</button>
</form>

<div class="md-card md-card--elevated">
    <div style="overflow-x:auto;">
        <table class="md-table">
            <thead>
                <tr>
                    <th>Unit</th>
                    <th>Type</th>
                    <th style="text-align:right;">Bound Positions</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($units as $unit)
                <tr>
                    <td class="md-label-md">{{ $unit->name }}</td>
                    <td class="md-body-sm" style="color:var(--md-on-surface-variant);">{{ $unit->unitType->name ?? '—' }}</td>
                    <td style="text-align:right;">
                        @if($unit->bound_positions_count > 0)
                            <span class="md-badge md-badge--success">{{ $unit->bound_positions_count }}</span>
                        @else
                            <span class="md-badge md-badge--neutral">Not scoped</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('unit-position-bindings.edit', $unit) }}" class="md-btn md-btn--outlined" style="font-size:11px;">Manage →</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="md-table__empty">No units found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="md-card__footer">{{ $units->links('vendor.pagination.material') }}</div>
</div>
@endsection
