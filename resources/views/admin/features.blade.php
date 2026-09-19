@extends('layouts.app')
@section('title','Feature Management')
@section('content')
<div style="margin-bottom:16px"><h2 class="md-headline-sm">⚙️ Feature Management</h2><p class="md-body-sm">Enable or disable optional Carder modules. Disabled modules are hidden from navigation and protected at route level where applicable.</p></div>
<form method="POST" action="{{ route('admin.features.update') }}">@csrf
<div class="workforce-panel"><div class="panel-title">Modules</div><div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:10px;margin-top:12px">
@foreach($features as $feature)<label class="md-card" style="padding:14px;display:flex;gap:12px;align-items:flex-start"><input type="checkbox" name="features[{{ $feature['id'] }}]" value="1" {{ $feature['enabled']?'checked':'' }}><div><strong>{{ $feature['label'] }}</strong><div class="md-body-sm">{{ $feature['enabled']?'Currently enabled':'Currently disabled' }}</div></div></label>@endforeach
</div></div>
<div class="workforce-panel" style="margin-top:16px"><div class="panel-title">Local AI</div><p class="md-body-sm">Leave endpoint blank for the built-in deterministic offline assistant. If configured, only private/LAN hosts are accepted.</p><div class="md-form-row"><div class="md-form-group"><label class="md-label">LAN AI endpoint</label><input class="md-input" name="ai_local_endpoint" value="{{ \App\Models\SystemSetting::get('ai_local_endpoint','') }}" placeholder="http://172.16.0.220:11434"></div><div class="md-form-group"><label class="md-label">Model</label><input class="md-input" name="ai_local_model" value="{{ \App\Models\SystemSetting::get('ai_local_model','carder-local') }}"></div></div></div>
<div style="display:flex;justify-content:flex-end;margin-top:16px"><button class="md-btn md-btn--filled">Save Feature Configuration</button></div></form>
@endsection
