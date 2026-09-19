@extends('layouts.app')
@section('title','Service Letter Letterheads')
@section('content')
<div class="panel-title-row" style="margin-bottom:16px"><div><h2 class="md-headline-sm">🏛 Service Letter Letterheads</h2><p class="md-body-sm">Configure official institutional headers used in printed service letters.</p></div><a class="md-btn md-btn--filled" href="{{ route('service-letter-letterheads.create') }}">+ Add Letterhead</a></div>
<div class="md-card md-card--elevated"><div style="overflow:auto"><table class="md-table"><thead><tr><th>Name</th><th>Institution</th><th>Reference Prefix</th><th>Status</th><th>Actions</th></tr></thead><tbody>
@forelse($letterheads as $l)<tr><td><strong>{{ $l->name }}</strong>@if($l->is_default) <span class="status-chip status-chip--good">Default</span>@endif</td><td>{{ $l->institution_name }}<div class="md-body-sm">{{ $l->ministry_name }}</div></td><td>{{ $l->reference_prefix ?: '—' }}</td><td>{{ $l->is_active?'Active':'Disabled' }}</td><td><a class="md-btn md-btn--text" href="{{ route('service-letter-letterheads.edit',$l) }}">Edit</a><form style="display:inline" method="POST" action="{{ route('service-letter-letterheads.toggle',$l) }}">@csrf @method('PATCH')<button class="md-btn md-btn--text">{{ $l->is_active?'Disable':'Enable' }}</button></form></td></tr>@empty<tr><td colspan="5">No letterheads configured.</td></tr>@endforelse
</tbody></table></div></div>
@endsection
