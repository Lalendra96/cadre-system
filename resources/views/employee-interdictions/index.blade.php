@extends('layouts.app')
@section('title', 'Interdiction Records — ' . $employee->display_name)
@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
    <div style="display:flex;align-items:center;gap:10px;">
        <a href="{{ route('employees.edit', $employee) }}" class="md-btn md-btn--icon" title="Back to profile">&#8592;</a>
        <div>
            <h2 class="md-headline-sm">Interdiction / Disciplinary Inquiry Records</h2>
            <p class="md-body-sm" style="color:var(--md-on-surface-variant);">{{ $employee->display_name }} ({{ $employee->pay_no ?? '—' }})</p>
        </div>
    </div>
    <a href="{{ route('employee-interdictions.create', $employee) }}" class="md-btn md-btn--filled">+ Add Record</a>
</div>

@if(session('success'))
<div style="background:var(--md-success-container,#1b3a2d);color:var(--md-on-success-container,#9ef0b3);padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">✓ {{ session('success') }}</div>
@endif

@if($employee->isCurrentlyInterdicted())
<div style="background:color-mix(in srgb,var(--md-error) 12%,transparent);color:var(--md-error);
            padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
    ⚠ This employee has an <strong>ongoing</strong> interdiction — they substantively hold their post but
    are not currently performing duty.
</div>
@endif

<div class="md-card md-card--elevated">
    <div style="overflow-x:auto;">
        <table class="md-table">
            <thead>
                <tr>
                    <th>Interdiction Date</th>
                    <th>Reason</th>
                    <th>Inquiry Ref.</th>
                    <th style="text-align:center;">Status</th>
                    <th>Reinstatement Date</th>
                    <th>Outcome</th>
                    <th>Recorded By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($interdictions as $i)
                <tr>
                    <td class="md-label-md">{{ $i->interdiction_date->format('d M Y') }}</td>
                    <td class="md-body-sm" style="max-width:200px;">{{ $i->reason ?: '—' }}</td>
                    <td class="md-body-sm">{{ $i->inquiry_reference_no ?: '—' }}</td>
                    <td style="text-align:center;">
                        @if($i->inquiry_status === 'ongoing')
                            <span class="md-badge md-badge--error">Ongoing</span>
                        @else
                            <span class="md-badge md-badge--success">Concluded</span>
                        @endif
                    </td>
                    <td class="md-body-sm">{{ $i->reinstatement_date?->format('d M Y') ?? '—' }}</td>
                    <td class="md-body-sm">{{ \App\Models\EmployeeInterdiction::OUTCOME_LABELS[$i->outcome] ?? '—' }}</td>
                    <td class="md-body-sm" style="color:var(--md-on-surface-variant);">{{ $i->recordedBy->name ?? '—' }}</td>
                    <td>
                        <x-disable-toggle :record="$i" toggle-route="employee-interdictions.toggle"
                            :route-params="[$employee, $i]" label="interdiction record" :require-reason="true" :small="true" />
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="md-table__empty">No interdiction records. <a href="{{ route('employee-interdictions.create', $employee) }}">Add one →</a></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="md-card__footer">{{ $interdictions->links('vendor.pagination.material') }}</div>
</div>
@endsection
