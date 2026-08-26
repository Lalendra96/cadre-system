@extends('layouts.app')
@section('title', 'Position Groups')
@section('content')
@php($isSuperAdmin = auth()->user()->isSuperAdmin())

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
    <div>
        <h2 class="md-headline-sm">Position Groups</h2>
        <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
            @if($isSuperAdmin)
                Every group created hospital-wide — named collections of positions used as the send-target for circulars.
            @else
                Your own groups (e.g. "Nursing Staff") — select these when sending a circular to reach everyone in those positions under your subject codes.
            @endif
        </p>
    </div>
    <a href="{{ route('position-groups.create') }}" class="md-btn md-btn--filled">+ New Group</a>
</div>

@if(session('success'))
<div style="background:var(--md-success-container,#1b3a2d);color:var(--md-on-success-container,#9ef0b3);padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">✓ {{ session('success') }}</div>
@endif

<div class="md-card md-card--elevated">
    <div style="overflow-x:auto;">
        <table class="md-table">
            <thead>
                <tr>
                    <th>Group Name</th>
                    <th>Description</th>
                    @if($isSuperAdmin)<th>Created By</th>@endif
                    <th style="text-align:right;">Positions</th>
                    <th style="text-align:center;">Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($groups as $g)
                <tr style="{{ !$g->is_active ? 'opacity:.5;' : '' }}">
                    <td class="md-label-md">{{ $g->name }}</td>
                    <td class="md-body-sm" style="color:var(--md-on-surface-variant);max-width:280px;">{{ $g->description ?: '—' }}</td>
                    @if($isSuperAdmin)<td class="md-body-sm">{{ $g->creator->name ?? '—' }}</td>@endif
                    <td style="text-align:right;">{{ $g->positions_count }}</td>
                    <td style="text-align:center;">
                        @if($g->is_active)
                            <span class="md-badge md-badge--success">Active</span>
                        @else
                            <span class="md-badge md-badge--neutral">Disabled</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex;gap:4px;align-items:center;">
                            <a href="{{ route('position-groups.edit', $g) }}" class="md-btn md-btn--icon" title="Edit">✏️</a>
                            <x-disable-toggle :record="$g" toggle-route="position-groups.toggle" label="position group" :require-reason="false" :small="true" />
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $isSuperAdmin ? 6 : 5 }}" class="md-table__empty">
                        No groups yet. <a href="{{ route('position-groups.create') }}">Create the first one →</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="md-card__footer">{{ $groups->links('vendor.pagination.material') }}</div>
</div>
@endsection
