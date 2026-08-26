@extends('layouts.app')
@section('title', 'Units')
@section('content')
<div class="md-flex-between" style="margin-bottom:16px;">
    <h1 class="md-h2">Units</h1>
    @if($canSeeInactive)
        <a href="{{ route('units.index', ['show_inactive' => $showInactive ? 0 : 1]) }}"
           class="md-btn {{ $showInactive ? 'md-btn--tonal' : 'md-btn--outlined' }}"
           style="font-size:12px;">
            {{ $showInactive ? '🔓 Hiding disabled' : '🔓 Show disabled' }}
        </a>
        @endif
        <a href="{{ route('units.create') }}" class="md-btn md-btn--primary">+ New Unit</a>
</div>

<form method="GET" style="margin-bottom:16px;">
    <input type="text" name="q" class="md-input" style="max-width:280px;" placeholder="Search code or name…" value="{{ request('q') }}">
</form>

<div class="md-card">
    <div class="md-table-wrap">
        <table class="md-table">
            <thead><tr><th>Code</th><th>Name</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse ($units as $u)
                <tr style="{{ !$u->is_active ? 'opacity:.5;' : '' }}">
                    <td>{{ $u->code }}</td>
                    <td>{{ $u->name }}</td>
                    <td>{!! $u->is_active ? '<span class="md-badge md-badge--success">Active</span>' : '<span class="md-badge md-badge--neutral">Inactive</span>' !!}</td>
                    <td class="md-table__actions">
                        <a href="{{ route('units.edit', $u) }}" class="md-btn md-btn--icon" title="Edit">&#9998;</a>
                        <x-disable-toggle :record="$u" toggle-route="units.toggle" label="unit" :require-reason="true" :small="true" />
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="md-table__empty">No units found. You can add them here.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="md-card__footer">{{ $units->links('vendor.pagination.material') }}</div>
</div>
@endsection
