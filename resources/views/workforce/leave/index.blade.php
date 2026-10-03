@extends('layouts.app')
@section('title', 'Leave Management')
@section('content')
@include('workforce.partials.style')
<div class="wf-page"><div class="wf-header"><div><h1 class="wf-title">Leave Management</h1><div class="wf-subtitle">Balance-aware requests, half-day controls, carry-forward foundation and governed approval decisions.</div></div></div>
@if(session('success'))<div class="wf-notice">{{ session('success') }}</div>@endif
@if($errors->any())<div class="wf-notice" style="background:var(--md-error-container);color:var(--md-on-error-container)">{{ $errors->first() }}</div>@endif
<div class="wf-card" style="margin-top:14px;overflow:auto"><table class="wf-table"><thead><tr><th>Employee</th><th>Type</th><th>Period</th><th>Days</th><th>Status</th><th>Reason</th></tr></thead><tbody>@foreach($requests as $r)<tr><td>{{ $r->employee?->display_name ?? '#'.$r->employee_id }}</td><td>{{ $r->leaveType?->name }}</td><td>{{ $r->start_date?->format('Y-m-d') }} → {{ $r->end_date?->format('Y-m-d') }}</td><td>{{ $r->days }}</td><td><span class="wf-badge">{{ $r->status }}</span></td><td>{{ $r->reason }}</td></tr>@endforeach</tbody></table></div>
<div class="wf-card" style="margin-top:16px;overflow:auto"><h2 style="margin:0 0 12px">Current leave balances</h2><table class="wf-table"><thead><tr><th>Employee</th><th>Leave type</th><th>Opening</th><th>Accrued</th><th>Used</th><th>Adjustment</th><th>Available</th></tr></thead><tbody>@forelse($balances as $b)<tr><td>{{ $b->employee?->display_name }}</td><td>{{ $b->leaveType?->name }}</td><td>{{ $b->opening_balance }}</td><td>{{ $b->accrued }}</td><td>{{ $b->used }}</td><td>{{ $b->adjusted }}</td><td><strong>{{ number_format($b->available(),2) }}</strong></td></tr>@empty<tr><td colspan="7">Balances are created when requests are submitted or approved.</td></tr>@endforelse</tbody></table></div>
</div>
@endsection
