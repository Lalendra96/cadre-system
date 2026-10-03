@extends('layouts.app')
@section('title', 'Overtime Management')
@section('content')
@include('workforce.partials.style')
<div class="wf-page"><div class="wf-header"><div><h1 class="wf-title">Overtime Management</h1><div class="wf-subtitle">Roster/attendance-linked overtime, approval and payroll inclusion.</div></div></div>
@if(session('success'))<div class="wf-notice">{{ session('success') }}</div>@endif

<div class="wf-card" style="margin-top:14px;overflow:auto"><table class="wf-table"><thead><tr><th>Date</th><th>Employee</th><th>Type</th><th>Requested</th><th>Approved</th><th>Status</th></tr></thead><tbody>@foreach($requests as $r)<tr><td>{{ $r->work_date?->format('Y-m-d') }}</td><td>#{{ $r->employee_id }}</td><td>{{ $r->overtime_type }}</td><td>{{ $r->minutes_requested }} min</td><td>{{ $r->minutes_approved ?? '—' }}</td><td><span class="wf-badge">{{ $r->status }}</span></td></tr>@endforeach</tbody></table></div></div>
@endsection
