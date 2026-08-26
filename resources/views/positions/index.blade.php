@extends('layouts.app')
@section('title', 'Positions')
@section('content')
<div class="md-flex-between" style="margin-bottom:16px;">
    <h1 class="md-h2">Positions</h1>
    <div style="display:flex;gap:8px;align-items:center;">
        @if($canSeeInactive)
        <a href="{{ route('positions.index', ['show_inactive' => $showInactive ? 0 : 1, 'q' => request('q')]) }}"
           class="md-btn {{ $showInactive ? 'md-btn--tonal' : 'md-btn--outlined' }}"
           style="font-size:12px;">
            {{ $showInactive ? '🔓 Hiding disabled' : '🔓 Show disabled' }}
        </a>
        @endif
        <a href="{{ route('positions.create') }}" class="md-btn md-btn--primary">+ New Position</a>
    </div>
</div>

<form method="GET" style="margin-bottom:16px;">
    <input type="text" name="q" class="md-input" style="max-width:280px;" placeholder="Search title or code…" value="{{ request('q') }}">
</form>

<div class="md-card">
    <div class="md-table-wrap">
        <table class="md-table">
            <thead><tr><th>Code</th><th>Title</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse ($positions as $p)
                <tr style="{{ !$p->is_active ? 'opacity:.5;' : '' }}">
                    <td>{{ $p->code }}</td>
                    <td>{{ $p->title }}</td>
                    <td>{!! $p->is_active ? '<span class="md-badge md-badge--success">Active</span>' : '<span class="md-badge md-badge--neutral">Inactive</span>' !!}</td>
                    <td class="md-table__actions">
                        <a href="{{ route('positions.edit', $p) }}" class="md-btn md-btn--icon" title="Edit">&#9998;</a>
                        <a href="{{ route('position-grades.index', $p) }}" class="md-btn md-btn--icon" title="Grading Criteria">🎖️</a>
                        <x-disable-toggle :record="$p" toggle-route="positions.toggle" label="position" :require-reason="true" :small="true" />
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="md-table__empty">No positions found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="md-card__footer">{{ $positions->links('vendor.pagination.material') }}</div>
</div>
@endsection
