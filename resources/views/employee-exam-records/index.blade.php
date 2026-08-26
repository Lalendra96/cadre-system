@extends('layouts.app')
@section('title', 'Exam Records — ' . $employee->display_name)
@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
    <div style="display:flex;align-items:center;gap:10px;">
        <a href="{{ route('employees.edit', $employee) }}" class="md-btn md-btn--icon" title="Back to profile">&#8592;</a>
        <div>
            <h2 class="md-headline-sm">Competitive Exam &amp; Efficiency Bar (E-Bar) Records</h2>
            <p class="md-body-sm" style="color:var(--md-on-surface-variant);">{{ $employee->display_name }} ({{ $employee->pay_no ?? '—' }})</p>
        </div>
    </div>
    <a href="{{ route('employee-exam-records.create', $employee) }}" class="md-btn md-btn--filled">+ Add Exam Record</a>
</div>

@if(session('success'))
<div style="background:var(--md-success-container,#1b3a2d);color:var(--md-on-success-container,#9ef0b3);padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">✓ {{ session('success') }}</div>
@endif

@php($pendingEBar = $employee->examRecords()->active()->efficiencyBar()->pending()->exists())
@if($pendingEBar)
<div style="background:color-mix(in srgb,#ff9800 15%,transparent);color:#ff9800;padding:12px 18px;
            border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
    ⚠ This employee has a pending Efficiency Bar exam result — increments may be affected until resolved.
</div>
@endif

<div class="md-card md-card--elevated">
    <div style="overflow-x:auto;">
        <table class="md-table">
            <thead>
                <tr><th>Type</th><th>Exam Name</th><th>Date</th><th style="text-align:center;">Result</th><th>Reference No.</th><th>Recorded By</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse($records as $r)
                <tr>
                    <td>
                        <span class="md-badge {{ $r->exam_type === 'efficiency_bar' ? 'md-badge--warning' : 'md-badge--info' }}">
                            {{ \App\Models\EmployeeExamRecord::TYPE_LABELS[$r->exam_type] ?? $r->exam_type }}
                        </span>
                    </td>
                    <td class="md-body-sm">{{ $r->exam_name ?: '—' }}</td>
                    <td class="md-body-sm">{{ $r->exam_date?->format('d M Y') ?? '—' }}</td>
                    <td style="text-align:center;">
                        <span class="md-badge {{ $r->result === 'pass' ? 'md-badge--success' : ($r->result === 'fail' ? 'md-badge--error' : 'md-badge--neutral') }}">
                            {{ \App\Models\EmployeeExamRecord::RESULT_LABELS[$r->result] ?? $r->result }}
                        </span>
                    </td>
                    <td class="md-body-sm">{{ $r->reference_no ?: '—' }}</td>
                    <td class="md-body-sm" style="color:var(--md-on-surface-variant);">{{ $r->recordedBy->name ?? '—' }}</td>
                    <td>
                        <x-disable-toggle :record="$r" toggle-route="employee-exam-records.toggle"
                            :route-params="[$employee, $r]" label="exam record" :require-reason="true" :small="true" />
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="md-table__empty">No exam records yet. <a href="{{ route('employee-exam-records.create', $employee) }}">Add the first one →</a></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="md-card__footer">{{ $records->links('vendor.pagination.material') }}</div>
</div>
@endsection
