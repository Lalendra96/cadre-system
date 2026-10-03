@extends('layouts.app')
@section('title', 'Locum / Session Payments')
@section('content')
@include('workforce.partials.style')
<div class="wf-page"><div class="wf-header"><div><h1 class="wf-title">Locum / Session Payments</h1><div class="wf-subtitle">Session, hourly, per-patient, revenue-share and hybrid remuneration.</div></div></div>
@if(session('success'))<div class="wf-notice">{{ session('success') }}</div>@endif

<div class="wf-card" style="margin-top:14px;overflow:auto"><table class="wf-table"><thead><tr><th>Date</th><th>Employee</th><th>Unit</th><th>Method</th><th>Patients</th><th>Amount</th><th>Status</th></tr></thead><tbody>@foreach($sessions as $r)<tr><td>{{ $r->session_date?->format('Y-m-d') }}</td><td>#{{ $r->employee_id }}</td><td>#{{ $r->unit_id }}</td><td>{{ $r->calculation_method }}</td><td>{{ $r->patients_seen }}</td><td>{{ number_format((float)$r->calculated_amount,2) }}</td><td><span class="wf-badge">{{ $r->status }}</span></td></tr>@endforeach</tbody></table></div></div>
@endsection
