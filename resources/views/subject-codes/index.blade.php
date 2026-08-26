@extends('layouts.app')
@section('title', 'Subject Codes')
@section('content')
<div class="md-flex-between" style="margin-bottom:16px;">
    <h1 class="md-h2">Subject Codes</h1>
    @if($canSeeInactive)
        <a href="{{ route('subject-codes.index', ['show_inactive' => $showInactive ? 0 : 1]) }}"
           class="md-btn {{ $showInactive ? 'md-btn--tonal' : 'md-btn--outlined' }}"
           style="font-size:12px;">
            {{ $showInactive ? '🔓 Hiding disabled' : '🔓 Show disabled' }}
        </a>
        @endif
        <a href="{{ route('subject-codes.create') }}" class="md-btn md-btn--primary">+ New Subject Code</a>
</div>

<form method="GET" style="margin-bottom:16px;">
    <input type="text" name="q" class="md-input" style="max-width:280px;" placeholder="Search code or name…" value="{{ request('q') }}">
</form>

<div class="md-card">
    <div class="md-table-wrap">
        <table class="md-table">
            <thead><tr><th>Code</th><th>Name</th><th>Positions</th><th>Assigned Officers</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse ($subjectCodes as $sc)
                <tr style="{{ !$sc->is_active ? 'opacity:.5;' : '' }}">
                    <td>{{ $sc->code }}</td>
                    <td>{{ $sc->name }}</td>
                    <td>
                        @forelse($sc->positions as $p)
                            <span class="md-badge md-badge--info" style="margin:2px;">{{ $p->title }}</span>
                        @empty
                            <span style="color:var(--md-on-surface-variant);font-size:12px;">— No position</span>
                        @endforelse
                    </td>
                    <td>
                        @forelse($sc->users as $u)
                            <span class="md-badge md-badge--neutral">{{ $u->name }}</span>
                        @empty
                            —
                        @endforelse
                    </td>
                    <td>{!! $sc->is_active ? '<span class="md-badge md-badge--success">Active</span>' : '<span class="md-badge md-badge--neutral">Inactive</span>' !!}</td>
                    <td class="md-table__actions">
                        <a href="{{ route('subject-codes.edit', $sc) }}" class="md-btn md-btn--icon" title="Edit">&#9998;</a>
                        <x-disable-toggle :record="$sc" toggle-route="subject-codes.toggle" label="subject code" :require-reason="true" :small="true" />
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="md-table__empty">No subject codes found. You can add them here.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="md-card__footer">{{ $subjectCodes->links('vendor.pagination.material') }}</div>
</div>
@endsection
