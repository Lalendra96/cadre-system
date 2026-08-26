@extends('layouts.app')
@section('title', 'Cadre Review Proposals')
@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
    <h2 class="md-headline-sm">Annual Cadre Review</h2>
    <a href="{{ route('cadre-reviews.create') }}" class="md-btn md-btn--filled">+ New Proposal</a>
</div>
<div class="md-card md-card--elevated">
    <div style="overflow-x:auto;">
        <table class="md-table">
            <thead><tr><th>Title</th><th>Year</th><th>Status</th><th>Created By</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($proposals as $p)
                @php($statusColors=['draft'=>'neutral','submitted'=>'info','director_approved'=>'success','moh_submitted'=>'warning','approved'=>'success','rejected'=>'critical'])
                <tr>
                    <td class="md-label-md">{{ $p->title }}</td>
                    <td style="text-align:center;"><span class="md-badge md-badge--info">{{ $p->proposal_year }}</span></td>
                    <td><span class="md-badge md-badge--{{ $statusColors[$p->status] ?? 'neutral' }}">{{ \App\Models\CadreReviewProposal::STATUS_LABELS[$p->status] ?? $p->status }}</span></td>
                    <td class="md-body-sm">{{ $p->creator->name ?? '—' }}</td>
                    <td class="md-body-sm">{{ $p->created_at->format('d M Y') }}</td>
                    <td>
                        <div style="display:flex;gap:4px;">
                            <a href="{{ route('cadre-reviews.show', $p) }}" class="md-btn md-btn--icon" title="View">👁</a>
                            @if($p->status === 'draft')
                            <a href="{{ route('cadre-reviews.edit', $p) }}" class="md-btn md-btn--icon" title="Edit">✏️</a>
                            <form method="POST" action="{{ route('cadre-reviews.destroy', $p) }}"
                                      onsubmit="return confirm('Cancel this proposal? It will be preserved for audit purposes.');">
                                @csrf @method('DELETE')
                                <button class="md-btn md-btn--icon" style="color:var(--md-error);" title="Cancel proposal">⊘</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="md-table__empty">No proposals yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="md-card__footer">{{ $proposals->links('vendor.pagination.material') }}</div>
</div>
@endsection
