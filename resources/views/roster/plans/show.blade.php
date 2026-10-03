@extends('layouts.app')
@section('content')
@include('roster.partials.style')
<div class="container-fluid py-4 roster-shell">
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
        <div><h3 class="roster-title mb-0">{{ $plan->title }}</h3><div class="text-muted">{{ $plan->reference_no }} · {{ $plan->unit->name??'' }} · {{ $plan->start_date->format('d M Y') }}–{{ $plan->end_date->format('d M Y') }} · Revision {{ $plan->revision_no }}</div></div>
        <div class="d-flex gap-2 flex-wrap">
            @if(in_array($plan->status,['draft','returned']))<form method="post" action="{{ route('roster.plans.submit',$plan) }}">@csrf<button class="btn btn-steel">Submit for Approval</button></form>
            @elseif($plan->status==='approved')<form method="post" action="{{ route('roster.plans.start',$plan) }}">@csrf<button class="btn btn-success">Start Assignment</button></form>
            @elseif($plan->status==='active')<form method="post" action="{{ route('roster.plans.complete',$plan) }}">@csrf<button class="btn btn-dark">Complete Roster</button></form>@endif
        </div>
    </div>
    @if(session('success'))<div class="roster-alert roster-alert-success mt-3">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="roster-alert roster-alert-danger mt-3">{{ $errors->first() }}</div>@endif

    @if($staffingIssues->isNotEmpty())
    <div class="roster-alert roster-alert-danger mt-3"><strong>Safe-staffing shortfall detected.</strong> {{ $staffingIssues->count() }} date(s) in this roster currently fall below one or more configured staffing/skill-mix rules. Review the details below before publication or amendment.</div>
    @endif

    <div class="row g-3 mt-2">
        <div class="col-lg-8">
            <div class="card roster-card"><div class="card-header bg-white d-flex justify-content-between"><strong>Duty Assignments</strong><span class="text-muted">{{ $plan->assignments->count() }} duties</span></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Date / Time</th><th>Employee</th><th>Home Unit</th><th>Duty Unit</th><th>Duty</th><th>Coverage</th><th>Acknowledged</th></tr></thead><tbody>
            @foreach($plan->assignments as $a)<tr><td>{{ $a->duty_date->format('d M Y') }}<div class="small text-muted">{{ substr($a->start_time,0,5) }}–{{ substr($a->end_time,0,5) }}</div></td><td>{{ $a->employee?->display_name ?? '—' }}</td><td>{{ $a->homeUnit->name??'—' }}</td><td>{{ $a->dutyUnit->name??'—' }}</td><td>{{ $a->duty_role??'—' }}<div class="small text-muted">{{ $a->details }}</div></td><td><span class="status-pill {{ $a->coverage_type==='cross_unit'?'cross-unit':'home-unit' }}">{{ ucwords(str_replace('_',' ',$a->coverage_type)) }}</span></td><td>@if($a->acknowledged_at)<span class="status-pill home-unit">Yes</span><div class="small text-muted">{{ $a->acknowledged_at->format('d M H:i') }}</div>@else<span class="text-muted">Pending</span>@endif</td></tr>@endforeach
            </tbody></table></div></div>

            @if(in_array($plan->status,['approved','active']))
            <div class="card roster-card mt-3"><div class="card-header bg-white"><strong>Governed Roster Amendment</strong></div><div class="card-body"><p class="text-muted">Approved/active rosters are never silently edited. Submit an amendment with a reason; the current revision remains preserved until approval.</p>
            <form method="post" action="{{ route('roster.amendments.store',$plan) }}" class="roster-form-grid">@csrf
                <label>Action<select name="action" required><option value="replace">Replace employee</option><option value="update">Update duty</option><option value="cancel">Cancel duty</option><option value="add">Add duty</option></select></label>
                <label>Existing duty<select name="roster_assignment_id"><option value="">Not applicable / add duty</option>@foreach($plan->assignments as $a)<option value="{{ $a->id }}">{{ $a->duty_date->format('d M') }} {{ substr($a->start_time,0,5) }} · {{ $a->employee?->display_name }}</option>@endforeach</select></label>
                <label>Employee<select name="employee_id"><option value="">Select for add/replace</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->display_name }}</option>@endforeach</select></label>
                <label>Duty date<input type="date" name="duty_date"></label><label>Start<input type="time" name="start_time"></label><label>End<input type="time" name="end_time"></label>
                <label>Duty unit<select name="duty_unit_id"><option value="">Keep existing / select for add</option>@foreach($units as $unit)<option value="{{ $unit->id }}">{{ $unit->name }}</option>@endforeach</select></label><label>Duty / role<input name="duty_role" maxlength="150"></label>
                <label style="grid-column:1/-1">Details<textarea name="details" rows="2"></textarea></label>
                <label style="grid-column:1/-1">Reason *<textarea name="reason" rows="2" required minlength="5" placeholder="Operational reason, sick replacement, emergency redeployment, etc."></textarea></label>
                <label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="is_emergency" value="1"> Emergency amendment</label><div><button class="roster-btn roster-btn-primary">Submit Amendment</button></div>
            </form></div></div>
            @endif
        </div>
        <div class="col-lg-4">
            <div class="card roster-card"><div class="card-header bg-white"><strong>Approval Workflow</strong></div><div class="card-body">@forelse($plan->approvals as $a)<div class="approval-step"><span class="approval-dot"></span><strong>{{ $a->level->label??('Level '.$a->level_no) }}</strong><div class="small">{{ ucfirst($a->status) }}</div>@if($a->remarks)<div class="small text-muted">{{ $a->remarks }}</div>@endif</div>@empty<div class="text-muted">Submit this draft to initialize the configured approval workflow.</div>@endforelse</div></div>

            @if($staffingIssues->isNotEmpty())<div class="card roster-card mt-3"><div class="card-header bg-white"><strong>Staffing / Skill-Mix Shortfalls</strong></div><div class="card-body">@foreach($staffingIssues as $date=>$issues)<div class="mb-3"><strong>{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</strong>@foreach($issues as $issue)<div class="small mt-1">{{ substr($issue['rule']->start_time,0,5) }}–{{ substr($issue['rule']->end_time,0,5) }} · {{ $issue['rule']->position?->title ?: 'Any position' }}@if($issue['rule']->competency) · {{ $issue['rule']->competency->name }}@endif: <strong>short {{ $issue['shortfall'] }}</strong></div>@endforeach</div>@endforeach</div></div>@endif

            <div class="card roster-card mt-3"><div class="card-header bg-white"><strong>Amendment History</strong></div><div class="card-body">@forelse($plan->amendments as $am)<div class="approval-step"><strong>{{ ucfirst($am->action) }} @if($am->is_emergency)<span class="status-pill cross-unit">Emergency</span>@endif</strong><div class="small">{{ ucwords(str_replace('_',' ',$am->status)) }} · {{ $am->requested_at?->format('d M H:i') }}</div><div class="small text-muted">{{ $am->reason }}</div>@if($am->status==='pending' && $canReviewAmendments && ((int)$am->requested_by !== (int)auth()->id() || auth()->user()->isSuperAdmin()))<form method="post" action="{{ route('roster.amendments.review',$am) }}" class="mt-2">@csrf<input name="review_note" class="form-control form-control-sm mb-1" placeholder="Decision note"><div class="d-flex gap-1"><button name="action" value="approve" class="btn btn-sm btn-success">Approve</button><button name="action" value="reject" class="btn btn-sm btn-outline-danger">Reject</button></div></form>@endif</div>@empty<div class="text-muted">No post-approval amendments.</div>@endforelse</div></div>
        </div>
    </div>
</div>
@endsection
