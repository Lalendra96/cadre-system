@extends('layouts.app')
@section('title', 'Acting Appointments')
@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px;">
    <div>
        <h2 class="md-headline-sm">Acting Appointments</h2>
        <p class="md-body-sm" style="color:var(--md-on-surface-variant);">Officers currently performing duties in a position above their substantive grade.</p>
    </div>
    <a href="{{ route('acting-appointments.create') }}" class="md-btn md-btn--filled">+ New Appointment</a>
</div>

{{-- Current active appointments --}}
<div class="md-card md-card--elevated" style="margin-bottom:20px;">
    <div class="md-card__header">
        <span class="md-title-md">Currently Active</span>
        <span class="md-badge md-badge--success">{{ $current->total() }}</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="md-table">
            <thead>
                <tr>
                    <th>Officer</th>
                    <th>Acting As</th>
                    <th>Substantive Position</th>
                    <th>From</th>
                    <th>Until</th>
                    <th>Duration</th>
                    <th>Order No.</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($current as $a)
                <tr>
                    <td>
                        <div class="md-label-md">{{ $a->employee->name ?? '—' }}</div>
                        <div class="md-body-sm" style="color:var(--md-on-surface-variant);">{{ $a->employee->pay_no ?? '' }}</div>
                    </td>
                    <td><span class="md-badge md-badge--info">{{ $a->actingPosition->title ?? '—' }}</span></td>
                    <td class="md-body-sm">{{ $a->substantivePosition->title ?? '—' }}</td>
                    <td class="md-body-sm">{{ $a->start_date->format('d M Y') }}</td>
                    <td class="md-body-sm">{{ $a->end_date ? $a->end_date->format('d M Y') : '—' }}</td>
                    <td><span class="md-badge md-badge--neutral">{{ $a->duration }}</span></td>
                    <td class="md-body-sm">{{ $a->appointment_order_no ?: '—' }}</td>
                    <td>
                        <div style="display:flex;gap:4px;">
                            <a href="{{ route('acting-appointments.edit', $a) }}" class="md-btn md-btn--icon" title="Edit">✏️</a>
                            <x-disable-toggle :record="$a" toggle-route="acting-appointments.toggle" label="appointment" :require-reason="false" :small="true" />
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="md-table__empty">No active acting appointments.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($current->hasPages())
    <div class="md-card__footer">{{ $current->links('vendor.pagination.material') }}</div>
    @endif
</div>

{{-- Historical --}}
@if($historical->total() > 0)
<div class="md-card md-card--elevated">
    <div class="md-card__header">
        <span class="md-title-md" style="color:var(--md-on-surface-variant);">Historical (ended or inactive)</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="md-table">
            <thead><tr><th>Officer</th><th>Acting As</th><th>Period</th><th>Order No.</th></tr></thead>
            <tbody>
                @foreach($historical as $a)
                <tr style="opacity:.7;">
                    <td class="md-label-md">{{ $a->employee->name ?? '—' }}</td>
                    <td class="md-body-sm">{{ $a->actingPosition->title ?? '—' }}</td>
                    <td class="md-body-sm">
                        {{ $a->start_date->format('d M Y') }} —
                        {{ $a->end_date ? $a->end_date->format('d M Y') : 'ongoing' }}
                    </td>
                    <td class="md-body-sm">{{ $a->appointment_order_no ?: '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($historical->hasPages())
    <div class="md-card__footer">{{ $historical->links('vendor.pagination.material') }}</div>
    @endif
</div>
@endif
@endsection
