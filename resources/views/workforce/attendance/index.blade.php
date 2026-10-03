@extends('layouts.app')
@section('title', 'Attendance Management')
@section('content')
@include('workforce.partials.style')
<div class="wf-page"><div class="wf-header"><div><h1 class="wf-title">Attendance Management</h1><div class="wf-subtitle">Planned-vs-actual duty, manual/device attendance and governed correction requests.</div></div></div>
@if(session('success'))<div class="wf-notice">{{ session('success') }}</div>@endif
@if($errors->any())<div class="wf-notice" style="background:var(--md-error-container);color:var(--md-on-error-container)">{{ $errors->first() }}</div>@endif
<div class="wf-card" style="margin-top:14px;overflow:auto"><table class="wf-table"><thead><tr><th>Date</th><th>Employee</th><th>Planned</th><th>Actual</th><th>Status</th><th>Worked</th><th>Variance</th><th>Correction</th></tr></thead><tbody>
@foreach($records as $r)
@php
    $plannedMinutes = null;
    if($r->rosterAssignment){
        $s=\Carbon\Carbon::parse($r->work_date->format('Y-m-d').' '.$r->rosterAssignment->start_time);
        $e=\Carbon\Carbon::parse($r->work_date->format('Y-m-d').' '.$r->rosterAssignment->end_time);
        if($e->lte($s)) $e->addDay();
        $plannedMinutes=$s->diffInMinutes($e);
    }
@endphp
<tr><td>{{ $r->work_date?->format('Y-m-d') }}</td><td>{{ $r->employee?->display_name ?? '#'.$r->employee_id }}</td><td>@if($r->rosterAssignment){{ substr($r->rosterAssignment->start_time,0,5) }}–{{ substr($r->rosterAssignment->end_time,0,5) }}@else—@endif</td><td>{{ optional($r->clock_in_at)->format('H:i') ?: '—' }}–{{ optional($r->clock_out_at)->format('H:i') ?: '—' }}</td><td><span class="wf-badge">{{ $r->status }}</span></td><td>{{ intdiv($r->worked_minutes,60) }}h {{ $r->worked_minutes%60 }}m</td><td>@if($plannedMinutes!==null){{ $r->worked_minutes-$plannedMinutes >=0 ? '+' : '' }}{{ $r->worked_minutes-$plannedMinutes }} min @else—@endif @if($r->late_minutes)<br><small>Late {{ $r->late_minutes }}m</small>@endif @if($r->early_departure_minutes)<br><small>Early {{ $r->early_departure_minutes }}m</small>@endif</td><td><form method="post" action="{{ route('workforce.attendance.corrections.store',$r) }}" style="display:grid;gap:5px;min-width:210px">@csrf<input type="datetime-local" name="requested_clock_in_at"><input type="datetime-local" name="requested_clock_out_at"><input name="reason" placeholder="Reason" required><button class="wf-btn">Request correction</button></form></td></tr>
@endforeach
</tbody></table></div>

<div class="wf-card" style="margin-top:16px;overflow:auto"><h2 style="margin:0 0 12px">Pending attendance corrections</h2><table class="wf-table"><thead><tr><th>Employee</th><th>Work date</th><th>Requested</th><th>Reason</th><th>Decision</th></tr></thead><tbody>@forelse($corrections as $c)<tr><td>{{ $c->employee?->display_name }}</td><td>{{ $c->attendanceRecord?->work_date?->format('Y-m-d') }}</td><td>{{ optional($c->requested_clock_in_at)->format('Y-m-d H:i') }} → {{ optional($c->requested_clock_out_at)->format('Y-m-d H:i') }}</td><td>{{ $c->reason }}</td><td><div style="display:flex;gap:6px"><form method="post" action="{{ route('workforce.attendance.corrections.decide',$c) }}">@csrf<input type="hidden" name="action" value="approve"><button class="wf-btn">Approve</button></form><form method="post" action="{{ route('workforce.attendance.corrections.decide',$c) }}">@csrf<input type="hidden" name="action" value="reject"><button class="wf-btn">Reject</button></form></div></td></tr>@empty<tr><td colspan="5">No pending corrections.</td></tr>@endforelse</tbody></table></div>
</div>
@endsection
