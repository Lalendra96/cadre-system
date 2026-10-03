@extends('layouts.app')
@section('title','My Roster Actions')
@section('content')
@include('roster.partials.style')
<div class="roster-page">
    <div class="roster-page-head"><div><div class="roster-eyebrow">Employee self-service · Roster</div><h1>My Roster Actions</h1><p>View duties, acknowledge published assignments, claim open shifts and respond to peer swap requests. All actions remain subject to managerial approval where configured.</p></div></div>
    @if(session('success'))<div class="roster-alert roster-alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="roster-alert roster-alert-danger">{{ $errors->first() }}</div>@endif

    <section class="roster-card">
        <div class="roster-card-head"><div><h2>Upcoming duties</h2><p>Acknowledgement records receipt of the roster; it does not replace attendance verification.</p></div></div>
        <div class="roster-table-wrap"><table class="roster-table"><thead><tr><th>Date / time</th><th>Duty unit</th><th>Role</th><th>Status</th><th>Acknowledgement</th></tr></thead><tbody>
        @forelse($assignments as $a)<tr><td>{{ $a->duty_date?->format('d M Y') }}<br><small>{{ substr($a->start_time,0,5) }}–{{ substr($a->end_time,0,5) }}</small></td><td>{{ $a->dutyUnit?->name }}</td><td>{{ $a->duty_role ?: 'Duty' }}</td><td>{{ ucfirst($a->status) }}</td><td>@if($a->acknowledged_at)<span class="status-pill home-unit">Acknowledged {{ $a->acknowledged_at->format('d M H:i') }}</span>@else<form method="post" action="{{ route('roster.employee.acknowledge',$a) }}">@csrf<button class="roster-btn roster-btn-secondary">Acknowledge</button></form>@endif</td></tr><tr><td colspan="5"><div class="roster-grid-2"><form method="post" action="{{ route('roster.employee.swaps.store',$a) }}" class="roster-form-grid">@csrf<input type="hidden" name="swap_type" value="swap"><label style="grid-column:1/-1">Swap with another duty<select name="replacement_assignment_id" required><option value="">Choose duty in the same roster</option>@foreach($potentialAssignments->where('roster_plan_id',$a->roster_plan_id) as $candidate)<option value="{{ $candidate->id }}">{{ $candidate->duty_date?->format('d M') }} {{ substr($candidate->start_time,0,5) }}–{{ substr($candidate->end_time,0,5) }} · {{ $candidate->employee?->display_name }} · {{ $candidate->dutyUnit?->name }}</option>@endforeach</select></label><label style="grid-column:1/-1">Reason<input name="reason" required maxlength="1000" placeholder="Why do you want to swap this duty?"></label><div><button class="roster-btn roster-btn-primary">Request Two-Way Swap</button></div></form><form method="post" action="{{ route('roster.employee.swaps.store',$a) }}" class="roster-form-grid">@csrf<input type="hidden" name="swap_type" value="give_away"><label style="grid-column:1/-1">Replacement request reason<input name="reason" required maxlength="1000" placeholder="Ask manager to arrange a replacement"></label><div><button class="roster-btn roster-btn-secondary">Request Replacement</button></div></form></div></td></tr>@empty<tr><td colspan="5">No upcoming duties.</td></tr>@endforelse
        </tbody></table></div>
    </section>

    <section class="roster-card" style="margin-top:16px">
        <div class="roster-card-head"><div><h2>Open shifts</h2><p>Claims are checked against overlap, availability, contract/registration status and configured skill-mix rules before being sent to your manager.</p></div></div>
        <div class="roster-table-wrap"><table class="roster-table"><thead><tr><th>Date</th><th>Time</th><th>Unit</th><th>Role</th><th>Claim</th></tr></thead><tbody>
        @forelse($openShifts as $s)<tr><td>{{ $s->duty_date?->format('d M Y') }}</td><td>{{ substr($s->start_time,0,5) }}–{{ substr($s->end_time,0,5) }}</td><td>{{ $s->unit?->name }}</td><td>{{ $s->duty_role ?: 'Open duty' }}</td><td><form method="post" action="{{ route('roster.employee.claims.store',$s) }}" style="display:flex;gap:8px;align-items:center">@csrf<input name="note" maxlength="500" placeholder="Optional note"><button class="roster-btn roster-btn-primary">Claim</button></form></td></tr>@empty<tr><td colspan="5">No suitable open shifts.</td></tr>@endforelse
        </tbody></table></div>
        @if($claims->isNotEmpty())<div class="roster-table-wrap" style="margin-top:12px"><table class="roster-table"><thead><tr><th>My recent claim</th><th>Status</th><th>Review</th></tr></thead><tbody>@foreach($claims as $c)<tr><td>{{ $c->shift?->duty_date?->format('d M Y') }} · {{ $c->shift?->unit?->name }}</td><td>{{ ucwords(str_replace('_',' ',$c->status)) }}</td><td>{{ $c->review_note ?: '—' }}</td></tr>@endforeach</tbody></table></div>@endif
    </section>

    <section class="roster-card" style="margin-top:16px">
        <div class="roster-card-head"><div><h2>Peer swap requests</h2><p>Accepting a peer request does not change the published roster. It forwards the accepted swap to an authorised manager for final approval.</p></div></div>
        <div class="roster-table-wrap"><table class="roster-table"><thead><tr><th>From</th><th>Duty</th><th>Reason</th><th>Response</th></tr></thead><tbody>
        @forelse($incomingSwaps as $swap)<tr><td>{{ $swap->requester?->display_name }}</td><td>{{ $swap->assignment?->duty_date?->format('d M Y') }} {{ substr($swap->assignment?->start_time ?? '',0,5) }}</td><td>{{ $swap->reason }}</td><td><form method="post" action="{{ route('roster.employee.swaps.respond',$swap) }}" style="display:flex;gap:6px">@csrf<input name="note" placeholder="Optional response"><button name="action" value="accept" class="roster-btn roster-btn-primary">Accept</button><button name="action" value="reject" class="roster-btn roster-btn-secondary">Decline</button></form></td></tr>@empty<tr><td colspan="4">No peer requests awaiting your response.</td></tr>@endforelse
        </tbody></table></div>
    </section>

    <section class="roster-card" style="margin-top:16px">
        <div class="roster-card-head"><div><h2>My shift-change requests</h2><p>Track peer acceptance, manager review and governed amendment status for requests you initiated.</p></div></div>
        <div class="roster-table-wrap"><table class="roster-table"><thead><tr><th>Requested duty</th><th>Type</th><th>Target</th><th>Status</th><th>Response / review</th></tr></thead><tbody>
        @forelse($outgoingSwaps as $swap)<tr><td>{{ $swap->assignment?->duty_date?->format('d M Y') }} {{ substr($swap->assignment?->start_time ?? '',0,5) }}–{{ substr($swap->assignment?->end_time ?? '',0,5) }}</td><td>{{ $swap->swap_type === 'swap' ? 'Two-way swap' : 'Replacement' }}</td><td>{{ $swap->target?->display_name ?: 'Manager to arrange' }}</td><td><span class="status-pill">{{ ucwords(str_replace('_',' ',$swap->status)) }}</span></td><td>{{ $swap->peer_response_note ?: $swap->review_note ?: '—' }}</td></tr>@empty<tr><td colspan="5">No recent shift-change requests.</td></tr>@endforelse
        </tbody></table></div>
    </section>
</div>
@endsection
