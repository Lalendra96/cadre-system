@extends('layouts.app')
@section('title', 'Amendment Requests')
@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
    <div>
        <h2 class="md-headline-sm">Amendment Requests</h2>
        <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
            Officers requesting corrections to previously submitted monthly entries.
        </p>
    </div>
    @if($pending > 0)
        <a href="{{ route('carder-entries.pending-verification') }}" class="md-btn md-btn--tonal">
            ✅ Pending Verification ({{ $pending }})
        </a>
    @endif
</div>

<div class="md-card md-card--elevated">
    <div style="overflow-x:auto;">
        <table class="md-table">
            <thead>
                <tr>
                    <th>Period</th>
                    <th>Position</th>
                    <th>Subject Code</th>
                    <th>In Position</th>
                    <th>Requested By</th>
                    <th>Requested At</th>
                    <th>Reason</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($amendments as $e)
                <tr>
                    <td class="md-label-md">
                        {{ \DateTime::createFromFormat('!m', $e->month)->format('M') }} {{ $e->year }}
                    </td>
                    <td class="md-body-sm">{{ $e->position->title ?? '—' }}</td>
                    <td>
                        <span class="md-badge md-badge--neutral">{{ $e->subjectCode->code ?? '—' }}</span>
                    </td>
                    <td style="text-align:center;font-weight:600;">{{ $e->males + $e->females + $e->no_pay_leave }}</td>
                    <td class="md-body-sm">{{ $e->amendmentRequestedBy->name ?? '—' }}</td>
                    <td class="md-body-sm">{{ $e->amendment_requested_at?->format('d M Y H:i') }}</td>
                    <td class="md-body-sm" style="max-width:220px;word-break:break-word;">
                        {{ $e->amendment_reason }}
                    </td>
                    <td>
                        <div style="display:flex;flex-direction:column;gap:4px;">

                            {{-- Approve: unlock entry for resubmission --}}
                            <form method="POST" action="{{ route('entry-amendments.approve', $e) }}">
                                @csrf
                                <button class="md-btn md-btn--tonal md-btn--sm"
                                        style="width:100%;color:var(--md-success);">
                                    ✓ Approve (unlock)
                                </button>
                            </form>

                            {{-- Reject: send back with reason --}}
                            <form method="POST" action="{{ route('entry-amendments.reject', $e) }}"
                                  onsubmit="
                                    event.preventDefault();
                                    var r = prompt('Rejection reason (required):');
                                    if (!r || !r.trim()) return;
                                    this.querySelector('[name=rejection_reason]').value = r;
                                    this.submit();">
                                @csrf
                                <input type="hidden" name="rejection_reason" value="">
                                <button type="submit" class="md-btn md-btn--tonal md-btn--sm"
                                        style="width:100%;color:var(--md-error);">
                                    ✗ Reject
                                </button>
                            </form>

                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="md-table__empty">
                        No pending amendment requests. ✓
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($amendments->hasPages())
    <div class="md-card__footer">{{ $amendments->links('vendor.pagination.material') }}</div>
    @endif
</div>
@endsection
