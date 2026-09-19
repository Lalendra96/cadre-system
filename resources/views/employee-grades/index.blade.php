@extends('layouts.app')
@section('title', 'Grade History — ' . $employee->display_name)
@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
    <div style="display:flex;align-items:center;gap:10px;">
        <a href="{{ route('employees.edit', $employee) }}" class="md-btn md-btn--icon" title="Back to profile">&#8592;</a>
        <div>
            <h2 class="md-headline-sm">Grade History</h2>
            <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
                {{ $employee->display_name }} ({{ $employee->pay_no ?? '—' }}) — {{ $employee->position->title ?? '—' }}
            </p>
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        @if($employee->position)
            <a href="{{ route('position-grades.index', $employee->position) }}" class="md-btn md-btn--outlined">⚙️ Grading Criteria</a>
        @endif
        <a href="{{ route('employee-grades.create', $employee) }}" class="md-btn md-btn--filled">+ Propose Grade Record</a>
    </div>
</div>

@if(session('success'))
<div style="background:var(--md-success-container,#1b3a2d);color:var(--md-on-success-container,#9ef0b3);
            padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
    ✓ {{ session('success') }}
</div>
@endif

{{-- Current grade summary --}}
@php($current = $employee->current_grade)
<div class="md-card md-card--elevated" style="padding:16px 20px;margin-bottom:16px;
            display:flex;align-items:center;justify-content:space-between;
            background:var(--md-surface-container);">
    <div>
        <div class="md-label-md">Current Recorded Grade</div>
        <div class="md-body-sm" style="color:var(--md-on-surface-variant);">
            @if($current)
                {{ $current->positionGrade->name ?? '—' }}
                · effective since {{ $current->effective_date->format('d M Y') }}
            @else
                No grade recorded yet for this employee's current position.
            @endif
        </div>
    </div>
    @if($current)
        <span class="md-badge md-badge--success" style="font-size:13px;padding:6px 14px;">
            {{ $current->positionGrade->name ?? '—' }}
        </span>
    @endif
</div>

<div class="md-card md-card--elevated">
    <div style="overflow-x:auto;">
        <table class="md-table">
            <thead>
                <tr>
                    <th>Grade</th>
                    <th>Effective Date</th>
                    <th>End Date</th>
                    <th>Reference No.</th>
                    <th>Notes</th>
                    <th>Recorded By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $r)
                <tr>
                    <td class="md-label-md">{{ $r->positionGrade->name ?? '—' }}</td>
                    <td class="md-body-sm">{{ $r->effective_date->format('d M Y') }}</td>
                    <td class="md-body-sm" style="color:var(--md-on-surface-variant);">
                        {{ $r->end_date ? $r->end_date->format('d M Y') : '— (current)' }}
                    </td>
                    <td class="md-body-sm">{{ $r->reference_no ?: '—' }}</td>
                    <td class="md-body-sm" style="color:var(--md-on-surface-variant);max-width:200px;">{{ $r->notes ?: '—' }}</td>
                    <td class="md-body-sm" style="color:var(--md-on-surface-variant);">{{ $r->recordedBy->name ?? '—' }}</td>
                    <td>
                        <x-disable-toggle :record="$r" toggle-route="employee-grades.toggle"
                            :route-params="[$employee, $r]" label="grade record" :require-reason="true" :small="true" />
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="md-table__empty">
                        No grade records yet.
                        <a href="{{ route('employee-grades.create', $employee) }}">Add the first one →</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="md-card__footer">{{ $records->links('vendor.pagination.material') }}</div>
</div>

<div
    style="
        margin-top: 12px;
        padding: 10px 12px;
        border-left: 4px solid var(--md-primary);
        background: var(--md-surface-container);
    "
>
    <strong>Decision-support note:</strong>
    Grade criteria and dates shown by the system are recorded indicators only.
    A new grade record is applied only after independent human approval and
    verification of the applicable official authority.
</div>

<p class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:12px;">
    ℹ Adding a new grade record automatically closes out the previous "current" record's end date —
    you never need to edit an old entry manually.
</p>

@endsection
