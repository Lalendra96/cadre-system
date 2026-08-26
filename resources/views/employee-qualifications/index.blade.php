@extends('layouts.app')
@section('title', 'Qualifications — ' . $employee->display_name)
@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
    <div style="display:flex;align-items:center;gap:10px;">
        <a href="{{ route('employees.edit', $employee) }}" class="md-btn md-btn--icon" title="Back to profile">&#8592;</a>
        <div>
            <h2 class="md-headline-sm">Education & Qualifications</h2>
            <p class="md-body-sm" style="color:var(--md-on-surface-variant);">{{ $employee->display_name }} ({{ $employee->pay_no ?? '—' }})</p>
        </div>
    </div>
    <a href="{{ route('employee-qualifications.create', $employee) }}" class="md-btn md-btn--filled">+ Add Qualification</a>
</div>

@if(session('success'))
<div style="background:var(--md-success-container,#1b3a2d);color:var(--md-on-success-container,#9ef0b3);padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">✓ {{ session('success') }}</div>
@endif

<div class="md-card md-card--elevated">
    <div style="overflow-x:auto;">
        <table class="md-table">
            <thead>
                <tr><th>Qualification</th><th>Institution</th><th style="text-align:center;">Year</th><th>Reference No.</th><th>Recorded By</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse($qualifications as $q)
                <tr>
                    <td class="md-label-md">{{ $q->qualification_name }}</td>
                    <td class="md-body-sm">{{ $q->institution ?: '—' }}</td>
                    <td style="text-align:center;">{{ $q->year_obtained ?: '—' }}</td>
                    <td class="md-body-sm">{{ $q->reference_no ?: '—' }}</td>
                    <td class="md-body-sm" style="color:var(--md-on-surface-variant);">{{ $q->recordedBy->name ?? '—' }}</td>
                    <td>
                        <x-disable-toggle :record="$q" toggle-route="employee-qualifications.toggle"
                            :route-params="[$employee, $q]" label="qualification" :require-reason="true" :small="true" />
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="md-table__empty">No qualifications recorded yet. <a href="{{ route('employee-qualifications.create', $employee) }}">Add the first one →</a></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="md-card__footer">{{ $qualifications->links('vendor.pagination.material') }}</div>
</div>
@endsection
