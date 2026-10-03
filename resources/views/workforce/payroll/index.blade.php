@extends('layouts.app')
@section('title', 'Payroll Management')
@section('content')
@include('workforce.partials.style')
<div class="wf-page"><div class="wf-header"><div><h1 class="wf-title">Payroll Management</h1><div class="wf-subtitle">Calculate, review, approve and lock monthly payroll with effective-dated statutory settings.</div></div></div>
@if(session('success'))<div class="wf-notice">{{ session('success') }}</div>@endif
<div class="wf-notice"><strong>Governance:</strong> APIT is not guessed by the system. Configure and validate current IRD rules before production payroll. Finalized payroll is immutable.</div>
<div class="wf-card" style="margin-top:14px;overflow:auto"><table class="wf-table"><thead><tr><th>Period</th><th>Name</th><th>Status</th><th>Locked</th><th>Prepared By</th><th>Actions</th></tr></thead><tbody>@foreach($runs as $r)<tr><td>{{ sprintf('%04d-%02d',$r->year,$r->month) }}</td><td>{{ $r->name }}</td><td><span class="wf-badge">{{ $r->status }}</span></td><td>{{ $r->is_locked?'Yes':'No' }}</td><td>#{{ $r->prepared_by }}</td><td>@if(!$r->is_locked)<form method="post" action="{{ route('workforce.payroll.finalize',$r) }}">@csrf<button class="wf-btn wf-btn-primary">Finalize & Lock</button></form>@endif</td></tr>@endforeach</tbody></table></div></div>
@endsection
