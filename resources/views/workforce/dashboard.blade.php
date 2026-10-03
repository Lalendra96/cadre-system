@extends('layouts.app')
@section('title', 'Workforce Management')
@section('content')
@include('workforce.partials.style')
<div class="wf-page"><div class="wf-header"><div><h1 class="wf-title">Workforce Management</h1><div class="wf-subtitle">Roster, attendance, leave, overtime, contracts, payroll and workforce intelligence</div></div></div>
<div class="wf-grid">@foreach($cards as $label=>$value)<div class="wf-card"><div class="wf-subtitle">{{ $label }}</div><div class="wf-kpi">{{ number_format($value) }}</div></div>@endforeach</div>
<div class="wf-card" style="margin-top:14px"><h3>Enabled Modules</h3><div class="wf-grid">@foreach(\App\Services\WorkforceFeatureService::FEATURES as $key=>$label)<div><span class="wf-badge {{ $features->enabled($key)?'success':'danger' }}">{{ $features->enabled($key)?'Enabled':'Disabled' }}</span> {{ $label }}</div>@endforeach</div></div></div>
@endsection
