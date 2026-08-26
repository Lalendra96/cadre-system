@extends('layouts.app')
@section('title', 'Letter Reviews')
@section('content')
<h1 class="md-h2" style="margin-bottom:16px;">Letters For Review</h1>

<div class="md-card">
    <div class="md-table-wrap">
        <table class="md-table">
            <thead>
                <tr><th>Title</th><th>Shared By</th><th>Shared On</th>
                    @if(auth()->user()->isSuperAdmin())<th>Overall Status</th>@else<th>My Status</th>@endif
                <th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse ($letters as $letter)
                <tr>
                    <td>{{ $letter->title }}</td>
                    <td>{{ $letter->createdBy->name ?? '—' }}</td>
                    <td>{{ $letter->created_at->format('d M Y') }}</td>
                    <td>
                        @if(auth()->user()->isSuperAdmin())
                            @foreach($letter->recipients as $r)
                                <span class="md-badge {{ $r->is_read ? 'md-badge--success' : 'md-badge--neutral' }}">{{ $r->user->category->name ?? $r->user->name }}: {{ $r->is_read ? 'Read' : 'Unread' }}</span>
                            @endforeach
                        @else
                            @php($mine = $letter->recipients->firstWhere('user_id', auth()->id()))
                            @if($mine)
                                <span class="md-badge {{ $mine->is_read ? 'md-badge--success' : 'md-badge--neutral' }}">{{ $mine->is_read ? 'Read' : 'Unread' }} — {{ ucfirst($mine->status) }}</span>
                            @endif
                        @endif
                    </td>
                    <td class="md-table__actions">
                        <a href="{{ route('letter-reviews.show', $letter) }}" class="md-btn md-btn--icon" title="View">&#128065;</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="md-table__empty">No letters to review.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="md-card__footer">{{ $letters->links('vendor.pagination.material') }}</div>
</div>
@endsection
