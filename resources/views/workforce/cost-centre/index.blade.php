@extends('layouts.app')
@section('title', 'Cost Centre Analytics')
@section('content')
@include('workforce.partials.style')
<div class="wf-page"><div class="wf-header"><div><h1 class="wf-title">Cost Centre Analytics</h1><div class="wf-subtitle">Workforce cost by branch, unit or configured cost centre.</div></div></div>
@if(session('success'))<div class="wf-notice">{{ session('success') }}</div>@endif

<div class="wf-card" style="margin-top:14px;overflow:auto"><table class="wf-table"><thead><tr><th>Cost Centre</th><th>Gross Pay</th><th>Overtime</th><th>Locum</th><th>Employer Statutory</th><th>Net Pay</th></tr></thead><tbody>@foreach($rows as $r)<tr><td>{{ $r->cost_centre }}</td><td>{{ number_format($r->gross_pay,2) }}</td><td>{{ number_format($r->overtime,2) }}</td><td>{{ number_format($r->locum,2) }}</td><td>{{ number_format($r->employer_statutory,2) }}</td><td>{{ number_format($r->net_pay,2) }}</td></tr>@endforeach</tbody></table></div></div>
@endsection
