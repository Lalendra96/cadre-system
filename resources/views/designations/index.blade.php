@extends('layouts.app')
@section('title', 'Designations')
@section('content')
    <div class="md-flex-between" style="margin-bottom:16px;">
        <h1 class="md-h2">Designations</h1>
        <a href="{{ route('designations.create') }}" class="md-btn md-btn--primary">+ New Designation</a>
    </div>

    <form method="GET" class="md-mt-8" style="margin-bottom:16px;">
        <input type="text" name="q" class="md-input" style="max-width:280px;" placeholder="Search title or code…"
            value="{{ request('q') }}">
    </form>

    <div class="md-card">
        <div class="md-table-wrap">
            <table class="md-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Title</th>
                        <th>Grade</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($designations as $d)
                        <tr>
                            <td>{{ $d->code }}</td>
                            <td>{{ $d->title }}</td>
                            <td>{{ $d->grade ?? '—' }}</td>
                            <td>{!! $d->is_active
                                ? '<span class="md-badge md-badge--success">Active</span>'
                                : '<span class="md-badge md-badge--neutral">Inactive</span>' !!}</td>
                            <td class="md-table__actions">
                                <a href="{{ route('designations.edit', $d) }}" class="md-btn md-btn--icon"
                                    title="Edit">&#9998;</a>
                                <form method="POST" action="{{ route('designations.destroy', $d) }}"
                                    onsubmit="return confirm('Remove this designation?');">
                                    @csrf @method('DELETE')
                                    <button class="md-btn md-btn--icon" title="Delete">&#128465;</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="md-table__empty">No designations found. You can add them here.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="md-card__footer">{{ $designations->links('vendor.pagination.material') }}</div>
    </div>
@endsection
