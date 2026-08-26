@extends('layouts.app')
@section('title', 'Position Subcategories')
@section('content')

<div style="margin-bottom:16px;">
    <h2 class="md-headline-sm">Position Subcategories</h2>
    <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
        Subgroup a Position into named subcategories — e.g. "Medical Consultant" can have "Health Information Consultant",
        "Emergency Physician", "PGIM Trainee". Each is tracked separately on the Unit-wise Breakdown; a subcategory can
        optionally be excluded from its parent's total (used for cases like PGIM trainees — counted and visible, but not
        added to the main headcount).
    </p>
</div>

@if(session('success'))
<div style="background:var(--md-success-container,#1b3a2d);color:var(--md-on-success-container,#9ef0b3);padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">✓ {{ session('success') }}</div>
@endif

<div style="display:flex;flex-direction:column;gap:14px;">
    @foreach($positions as $position)
    <div class="md-card md-card--elevated">
        <div style="padding:14px 20px;border-bottom:1px solid var(--md-outline-variant);display:flex;align-items:center;justify-content:space-between;">
            <span class="md-label-md">{{ $position->title }}</span>
            <a href="{{ route('position-subcategories.create', $position) }}" class="md-btn md-btn--outlined" style="font-size:11px;">+ Add Subcategory</a>
        </div>
        @if($position->subcategories->isEmpty())
            <div style="padding:16px 20px;color:var(--md-on-surface-variant);font-size:12.5px;">No subcategories configured.</div>
        @else
        <div style="overflow-x:auto;">
            <table class="md-table">
                <thead>
                    <tr>
                        <th>Subcategory</th>
                        <th>Description</th>
                        <th style="text-align:center;">Counts Toward Total</th>
                        <th style="text-align:center;">Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($position->subcategories as $sub)
                    <tr style="{{ !$sub->is_active ? 'opacity:.5;' : '' }}">
                        <td class="md-label-md">{{ $sub->name }}</td>
                        <td class="md-body-sm" style="color:var(--md-on-surface-variant);">{{ $sub->description ?: '—' }}</td>
                        <td style="text-align:center;">
                            @if($sub->counts_toward_parent_total)
                                <span class="md-badge md-badge--success">Yes</span>
                            @else
                                <span class="md-badge md-badge--warning" title="Tracked separately, excluded from the parent position's main total">No — excluded</span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            @if($sub->is_active)
                                <span class="md-badge md-badge--success">Active</span>
                            @else
                                <span class="md-badge md-badge--neutral">Disabled</span>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex;gap:4px;align-items:center;">
                                <a href="{{ route('position-subcategories.edit', [$position, $sub]) }}" class="md-btn md-btn--icon" title="Edit">✏️</a>
                                <x-disable-toggle :record="$sub" toggle-route="position-subcategories.toggle"
                                    :route-params="[$position, $sub]" label="subcategory" :require-reason="false" :small="true" />
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    @endforeach
</div>
@endsection
