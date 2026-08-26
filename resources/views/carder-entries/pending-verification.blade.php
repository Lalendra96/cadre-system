@extends('layouts.app')
@section('title', 'Pending Verification')
@section('content')
<h2 class="md-headline-sm" style="margin-bottom:16px;">Entries Pending Verification</h2>
<div class="md-card md-card--elevated">
    <div style="overflow-x:auto;">
        <table class="md-table">
            <thead><tr><th>Period</th><th>Position</th><th>Subject Code</th><th>In Position</th><th>Submitted By</th><th>Submitted At</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($entries as $e)
                <tr>
                    <td>{{ \DateTime::createFromFormat('!m', $e->month)->format('M') }} {{ $e->year }}</td>
                    <td>{{ $e->position->title ?? '—' }}</td>
                    <td class="md-body-sm">{{ $e->subjectCode->code }}</td>
                    <td>{{ $e->males + $e->females + $e->no_pay_leave }}</td>
                    <td class="md-body-sm">{{ $e->submittedBy->name ?? '—' }}</td>
                    <td class="md-body-sm">{{ $e->submitted_at?->format('d M Y H:i') }}</td>
                    <td>
                        <div style="display:flex;gap:4px;">
                            <form method="POST" action="{{ route('carder-entries.verify', $e) }}">
                                @csrf <button class="md-btn md-btn--tonal md-btn--sm" style="color:var(--md-success);">✓ Verify</button>
                            </form>
                            <form method="POST" action="{{ route('carder-entries.reject-verification', $e) }}" onsubmit="event.preventDefault(); var r=prompt('Rejection reason:'); if(r){this.querySelector('[name=rejection_reason]').value=r;this.submit();}">
                                @csrf <input type="hidden" name="rejection_reason" value="">
                                <button type="submit" class="md-btn md-btn--tonal md-btn--sm" style="color:var(--md-error);">✗ Reject</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="md-table__empty">No entries awaiting verification. ✓</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="md-card__footer">{{ $entries->links('vendor.pagination.material') }}</div>
</div>
@endsection
