@extends('layouts.app')
@section('title', 'Increment History — ' . $employee->display_name)
@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
    <div style="display:flex;align-items:center;gap:10px;">
        <a href="{{ route('employees.edit', $employee) }}" class="md-btn md-btn--icon" title="Back to profile">&#8592;</a>
        <div>
            <h2 class="md-headline-sm">Increment History</h2>
            <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
                {{ $employee->display_name }} ({{ $employee->pay_no ?? '—' }}) — {{ $employee->position->title ?? '—' }}
            </p>
        </div>
    </div>
    <a href="{{ route('employee-increments.create', $employee) }}" class="md-btn md-btn--filled">+ Quick Add Increment</a>
</div>

@if(session('success'))
<div style="background:var(--md-success-container,#1b3a2d);color:var(--md-on-success-container,#9ef0b3);
            padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
    ✓ {{ session('success') }}
</div>
@endif

{{-- Next increment summary --}}
@php($next = $employee->next_increment)
@if($next)
@php($daysAway = (int) now()->diffInDays($next->increment_date, false))
<div class="md-card md-card--elevated" style="padding:16px 20px;margin-bottom:16px;
            display:flex;align-items:center;justify-content:space-between;
            border-left:none;background:{{ $daysAway < 0 ? 'var(--md-error-container)' : 'var(--md-surface-container)' }};">
    <div>
        <div class="md-label-md" style="color:{{ $daysAway < 0 ? 'var(--md-on-error-container)' : 'var(--md-on-surface)' }};">
            {{ $daysAway < 0 ? '⚠ Overdue increment' : ($daysAway <= 30 ? '🔔 Upcoming increment' : 'Next recorded increment') }}
        </div>
        <div class="md-body-sm" style="color:{{ $daysAway < 0 ? 'var(--md-on-error-container)' : 'var(--md-on-surface-variant)' }};">
            {{ $next->increment_date->format('d M Y') }}
            @if($next->amount) · Rs. {{ number_format($next->amount, 2) }} @endif
        </div>
    </div>
    <div style="font-size:13px;font-weight:600;color:{{ $daysAway < 0 ? 'var(--md-error)' : 'var(--md-primary)' }};">
        {{ $daysAway < 0 ? abs($daysAway) . ' day(s) overdue' : ($daysAway === 0 ? 'Today' : "in {$daysAway} day(s)") }}
    </div>
</div>
@endif

<div class="md-card md-card--elevated">
    <div style="overflow-x:auto;">
        <table class="md-table">
            <thead>
                <tr>
                    <th>Increment Date</th>
                    <th style="text-align:right;">Amount</th>
                    <th>Reference No.</th>
                    <th>Notes</th>
                    <th>Recorded By</th>
                    <th style="text-align:center;">Reminder</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($increments as $inc)
                <tr>
                    <td class="md-label-md">{{ $inc->increment_date->format('d M Y') }}</td>
                    <td style="text-align:right;">{{ $inc->amount ? 'Rs. ' . number_format($inc->amount, 2) : '—' }}</td>
                    <td class="md-body-sm">{{ $inc->reference_no ?: '—' }}</td>
                    <td class="md-body-sm" style="color:var(--md-on-surface-variant);max-width:220px;">{{ $inc->notes ?: '—' }}</td>
                    <td class="md-body-sm" style="color:var(--md-on-surface-variant);">{{ $inc->recordedBy->name ?? '—' }}</td>
                    <td style="text-align:center;">
                        @if($inc->notified_at)
                            <span class="md-badge md-badge--success" title="Sent {{ $inc->notified_at->format('d M Y') }}">✓ Sent</span>
                        @elseif($inc->increment_date->isFuture())
                            <span class="md-badge md-badge--neutral">Pending</span>
                        @else
                            <span class="md-badge md-badge--neutral">—</span>
                        @endif
                    </td>
                    <td>
                        <x-disable-toggle :record="$inc" toggle-route="employee-increments.toggle"
                            :route-params="[$employee, $inc]" label="increment record" :require-reason="true" :small="true" />
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="md-table__empty">
                        No increment records yet.
                        <a href="{{ route('employee-increments.create', $employee) }}">Add the first one →</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="md-card__footer">{{ $increments->links('vendor.pagination.material') }}</div>
</div>

<p class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:12px;">
    ℹ A reminder notification is sent automatically to this employee's Subject Officer 30 days before an upcoming increment date.
</p>

@endsection
