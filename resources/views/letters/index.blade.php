@extends('layouts.app')
@section('title', 'Letter Sharing')
@section('content')
<div class="md-flex-between" style="margin-bottom:16px;">
    <h1 class="md-h2">Letters I've Shared</h1>
    <a href="{{ route('letters.create') }}" class="md-btn md-btn--primary">+ Share a Letter</a>
</div>

<div class="md-card">
    <div class="md-table-wrap">
        <table class="md-table">
            <thead>
                <tr><th>Title</th><th>Shared On</th><th>Files</th><th>Read Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse ($letters as $letter)
                <tr>
                    <td>{{ $letter->title }}</td>
                    <td>{{ $letter->created_at->format('d M Y') }}</td>
                    <td class="md-flex md-gap-8" style="flex-wrap:wrap;">
                        @forelse($letter->attachments as $a)
                            @if($a->isAvailable())
                                <a href="{{ route('letters.attachments.download', [$letter, $a]) }}" class="md-btn md-btn--sm md-btn--outlined">{{ $a->original_filename }}</a>
                            @else
                                <span class="md-caption">{{ $a->original_filename }} (removed)</span>
                            @endif
                        @empty
                            <span class="md-caption">—</span>
                        @endforelse
                    </td>
                    <td class="md-flex md-gap-8" style="flex-wrap:wrap;">
                        @foreach($letter->recipients as $r)
                            <span class="md-badge {{ $r->is_read ? 'md-badge--success' : 'md-badge--neutral' }}">
                                {{ $r->user->category->name ?? $r->user->name }}: {{ $r->is_read ? 'Read' : 'Unread' }}
                            </span>
                        @endforeach
                    </td>
                    <td class="md-table__actions">
                        <a href="{{ route('letters.show', $letter) }}" class="md-btn md-btn--icon" title="View">&#128065;</a>
                        @if($letter->recipients->where('is_read', true)->isEmpty())
                            <x-disable-toggle :record="$letter" toggle-route="letters.destroy" label="letter" :require-reason="false" :small="true" />
                        @else
                            <span class="md-btn md-btn--icon" style="opacity:.3;cursor:not-allowed;"
                                  title="Cannot withdraw — at least one recipient has already read this letter">⊘</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="md-table__empty">You haven't shared any letters yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="md-card__footer">{{ $letters->links('vendor.pagination.material') }}</div>
</div>
@endsection
