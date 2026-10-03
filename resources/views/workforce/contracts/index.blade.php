@extends('layouts.app')
@section('title', 'Contract Management')
@section('content')
@include('workforce.partials.style')
<div class="wf-page"><div class="wf-header"><div><h1 class="wf-title">Contract Management</h1><div class="wf-subtitle">Permanent, probation, fixed-term, part-time, hourly, locum and visiting contracts.</div></div></div>
@if(session('success'))<div class="wf-notice">{{ session('success') }}</div>@endif

<div class="wf-card" style="margin-top:14px;overflow:auto"><table class="wf-table"><thead><tr><th>Employee</th><th>Type</th><th>Start</th><th>End</th><th>Basic</th><th>Status</th></tr></thead><tbody>@foreach($contracts as $r)<tr><td>#{{ $r->employee_id }}</td><td>{{ $r->contract_type }}</td><td>{{ $r->start_date?->format('Y-m-d') }}</td><td>{{ $r->end_date?->format('Y-m-d') ?? 'Open' }}</td><td>{{ number_format((float)$r->basic_salary,2) }}</td><td><span class="wf-badge success">{{ $r->status }}</span></td></tr>@endforeach</tbody></table></div></div>
@endsection
