@extends('layouts.app')
@section('title', 'Navigation Configuration')
@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
    <div>
        <h2 class="md-headline-sm">Navigation Configuration</h2>
        <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
            Every link in the sidebar, grouped by section. Toggle items on/off, reorder within a section by
            changing sort order, and control which roles/conditions see each one.
        </p>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="{{ route('nav-items.preview') }}" class="md-btn md-btn--outlined">👁 Preview as Role</a>
        <a href="{{ route('nav-items.create') }}" class="md-btn md-btn--filled">+ New Nav Item</a>
    </div>
</div>

@if(session('success'))
<div style="background:var(--md-success-container,#1b3a2d);color:var(--md-on-success-container,#9ef0b3);padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">✓ {{ session('success') }}</div>
@endif

@foreach($items as $section => $sectionItems)
<div class="md-card md-card--elevated" style="margin-bottom:16px;">
    <div style="padding:14px 20px;border-bottom:1px solid var(--md-outline-variant);">
        <span class="md-label-md">{{ $section }}</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="md-table">
            <thead>
                <tr>
                    <th>Label</th>
                    <th>Route</th>
                    <th>Roles</th>
                    <th>Conditions</th>
                    <th style="text-align:right;">Order</th>
                    <th style="text-align:center;">Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sectionItems as $item)
                <tr style="{{ !$item->is_active ? 'opacity:.5;' : '' }}">
                    <td class="md-label-md">{{ $item->label }}</td>
                    <td class="md-body-sm" style="color:var(--md-on-surface-variant);font-family:monospace;font-size:11px;">{{ $item->route_name }}</td>
                    <td class="md-body-sm">
                        @if($item->allowed_roles)
                            @foreach($item->allowed_roles as $r)
                                <span class="md-badge md-badge--info" style="font-size:9px;">{{ $r }}</span>
                            @endforeach
                        @else
                            <span style="color:var(--md-on-surface-variant);">any logged-in</span>
                        @endif
                    </td>
                    <td class="md-body-sm">
                        @if($item->custom_checks)
                            @foreach($item->custom_checks as $c)
                                <span class="md-badge md-badge--warning" style="font-size:9px;" title="{{ \App\Models\NavItem::customCheckLabels()[$c] ?? $c }}">{{ $c }}</span>
                            @endforeach
                        @else
                            —
                        @endif
                    </td>
                    <td style="text-align:right;">{{ $item->sort_order }}</td>
                    <td style="text-align:center;">
                        @if($item->is_active)
                            <span class="md-badge md-badge--success">Active</span>
                        @else
                            <span class="md-badge md-badge--neutral">Disabled</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex;gap:4px;align-items:center;">
                            <a href="{{ route('nav-items.edit', $item) }}" class="md-btn md-btn--icon" title="Edit">✏️</a>
                            <form method="POST" action="{{ route('nav-items.toggle', $item) }}" style="display:inline;">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="md-btn md-btn--icon" title="{{ $item->is_active ? 'Disable' : 'Enable' }}">
                                    {{ $item->is_active ? '🚫' : '✓' }}
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endforeach
@endsection
