@extends('layouts.app')
@section('title', 'Leave Records — ' . $employee->display_name)
@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
    <div style="display:flex;align-items:center;gap:10px;">
        <a href="{{ route('employees.edit', $employee) }}" class="md-btn md-btn--icon" title="Back to profile">&#8592;</a>
        <div>
            <h2 class="md-headline-sm">Leave / No-Pay Leave Records</h2>
            <p class="md-body-sm" style="color:var(--md-on-surface-variant);">{{ $employee->display_name }} ({{ $employee->pay_no ?? '—' }})</p>
        </div>
    </div>
    <a href="{{ route('employee-leave-records.create', $employee) }}" class="md-btn md-btn--filled">+ Add Leave Record</a>
</div>

@if(session('success'))
<div style="background:var(--md-success-container,#1b3a2d);color:var(--md-on-success-container,#9ef0b3);padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">✓ {{ session('success') }}</div>
@endif

@if($employee->isCurrentlyOnLeave())
<div style="background:color-mix(in srgb,#ff9800 15%,transparent);color:#ff9800;padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
    ℹ This employee is currently recorded as away on leave.
</div>
@endif

<div class="md-card md-card--elevated">
    <div style="overflow-x:auto;">
        <table class="md-table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Start Date</th>
                    <th>Expected Return</th>
                    <th>Actual Return</th>
                    <th>Reference No.</th>
                    <th style="text-align:center;">Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $r)
                <tr>
                    <td><span class="md-badge md-badge--info">{{ $r->leave_type === 'no_pay_leave' ? 'No-Pay Leave' : 'Other' }}</span></td>
                    <td class="md-label-md">{{ $r->start_date->format('d M Y') }}</td>
                    <td class="md-body-sm {{ $r->is_overdue ? 'text-error' : '' }}" style="{{ $r->is_overdue ? 'color:var(--md-error);font-weight:600;' : '' }}">
                        {{ $r->expected_return_date?->format('d M Y') ?? '—' }}
                    </td>
                    <td class="md-body-sm">{{ $r->actual_return_date?->format('d M Y') ?? '—' }}</td>
                    <td class="md-body-sm">{{ $r->reference_no ?: '—' }}</td>
                    <td style="text-align:center;">
                        @if($r->actual_return_date)
                            <span class="md-badge md-badge--success">Returned</span>
                        @elseif($r->is_overdue)
                            <span class="md-badge md-badge--error">Overdue</span>
                        @else
                            <span class="md-badge md-badge--neutral">Away</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex;gap:4px;align-items:center;">
                            @if(!$r->actual_return_date)
                            <form method="POST" action="{{ route('employee-leave-records.mark-returned', [$employee, $r]) }}"
                                  onsubmit="return confirmMarkReturned(this);" style="display:inline;">
                                @csrf
                                <input type="hidden" name="actual_return_date" class="mark-returned-date">
                                <button type="button" class="md-btn md-btn--icon" title="Mark returned"
                                        onclick="promptMarkReturned(this)">✓</button>
                            </form>
                            @endif
                            <x-disable-toggle :record="$r" toggle-route="employee-leave-records.toggle"
                                :route-params="[$employee, $r]" label="leave record" :require-reason="true" :small="true" />
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="md-table__empty">No leave records. <a href="{{ route('employee-leave-records.create', $employee) }}">Add one →</a></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="md-card__footer">{{ $records->links('vendor.pagination.material') }}</div>
</div>
@endsection

@push('scripts')
<script>
function promptMarkReturned(btn) {
    var today = new Date().toISOString().split('T')[0];
    var date = prompt('Actual return date (YYYY-MM-DD):', today);
    if (!date) return;
    var form = btn.closest('form');
    form.querySelector('.mark-returned-date').value = date;
    form.submit();
}
</script>
@endpush
