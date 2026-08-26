@extends('layouts.app')
@section('title', 'Transfer Records')
@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
    <h2 class="md-headline-sm">Transfer Records</h2>
    <a href="{{ route('transfer-records.create') }}" class="md-btn md-btn--filled">+ Record Transfer</a>
</div>

<form method="GET" style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;align-items:flex-end;">
    <div class="md-field" style="max-width:140px;">
        <label class="md-field__label">Direction</label>
        <select name="direction" class="md-field__input" style="height:36px;" onchange="this.form.submit()">
            <option value="">All</option>
            <option value="in"  {{ request('direction') === 'in'  ? 'selected' : '' }}>Transfers In</option>
            <option value="out" {{ request('direction') === 'out' ? 'selected' : '' }}>Transfers Out</option>
        </select>
    </div>
    <div class="md-field" style="max-width:150px;">
        <label class="md-field__label">From date</label>
        <input type="date" name="from" class="md-field__input" style="height:36px;"
               value="{{ request('from') }}" onchange="this.form.submit()">
    </div>
    <div class="md-field" style="max-width:150px;">
        <label class="md-field__label">To date</label>
        <input type="date" name="to" class="md-field__input" style="height:36px;"
               value="{{ request('to') }}" onchange="this.form.submit()">
    </div>
    @if(request()->hasAny(['direction','from','to']))
        <a href="{{ route('transfer-records.index') }}" class="md-btn md-btn--text" style="height:36px;align-self:flex-end;">Clear</a>
    @endif
</form>

<div class="md-card md-card--elevated">
    <div style="overflow-x:auto;">
        <table class="md-table">
            <thead>
                <tr>
                    <th>Officer Name</th>
                    <th>Designation</th>
                    <th style="text-align:center;">Direction</th>
                    <th>Type</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Effective Date</th>
                    <th>Recorded By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $r)
                <tr>
                    <td>
                        <div class="md-label-md">{{ $r->employee_name }}</div>
                        @if($r->employee)
                            <div class="md-body-sm" style="color:var(--md-on-surface-variant);">{{ $r->employee->pay_no }}</div>
                        @endif
                    </td>
                    <td class="md-body-sm">{{ $r->designation ?: '—' }}</td>
                    <td style="text-align:center;">
                        @if($r->direction === 'in')
                            <span class="md-badge md-badge--success">▶ In</span>
                        @else
                            <span class="md-badge md-badge--warning">◀ Out</span>
                        @endif
                    </td>
                    <td class="md-body-sm">{{ ucfirst($r->transfer_type) }}</td>
                    <td class="md-body-sm">{{ $r->from_location ?: '—' }}</td>
                    <td class="md-body-sm">{{ $r->to_location ?: '—' }}</td>
                    <td class="md-body-sm">{{ $r->effective_date->format('d M Y') }}</td>
                    <td class="md-body-sm">{{ $r->recordedBy->name ?? '—' }}</td>
                    <td>
                        <div style="display:flex;gap:4px;">
                            <a href="{{ route('transfer-records.edit', $r) }}" class="md-btn md-btn--icon" title="Edit">✏️</a>
                            <x-disable-toggle :record="$r" toggle-route="transfer-records.toggle" label="transfer record" :require-reason="false" :small="true" />
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="md-table__empty">
                    No transfer records found.
                    @if(request()->hasAny(['direction','from','to']))<a href="{{ route('transfer-records.index') }}">Clear filters</a>@endif
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="md-card__footer">{{ $records->links('vendor.pagination.material') }}</div>
</div>
@endsection
