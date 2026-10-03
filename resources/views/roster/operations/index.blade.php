@extends('layouts.app')
@section('title', 'Roster Operations')
@section('content')
@include('roster.partials.style')
<div class="roster-page">
    <div class="roster-page-head">
        <div>
            <div class="roster-eyebrow">Roster intelligence</div>
            <h1>Roster Operations</h1>
            <p>Availability, open shifts, swap requests and safe-staffing controls. Employee names are rendered through the decrypted Employee model.</p>
        </div>
        <a class="roster-btn roster-btn-secondary" href="{{ route('roster.dashboard') }}">Back to Roster</a>
    </div>

    @if(session('success'))<div class="roster-alert roster-alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="roster-alert roster-alert-danger">{{ $errors->first() }}</div>@endif

    <div class="roster-grid-2">
        <section class="roster-card">
            <div class="roster-card-head"><div><h2>Employee availability</h2><p>Record preferred or unavailable periods before building the roster.</p></div></div>
            <form method="post" action="{{ route('roster.availability.store') }}" class="roster-form-grid">@csrf
                <label>Employee<select name="employee_id" required><option value="">Select employee</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->display_name }}</option>@endforeach</select></label>
                <label>Date<input type="date" name="available_date" required></label>
                <label>Type<select name="availability_type" required><option value="available">Available</option><option value="preferred">Preferred</option><option value="unavailable">Unavailable</option></select></label>
                <label>Preferred shift<select name="preferred_shift"><option value="">Any</option><option value="morning">Morning</option><option value="evening">Evening</option><option value="night">Night</option><option value="on_call">On-call</option></select></label>
                <label>From<input type="time" name="available_from"></label><label>To<input type="time" name="available_to"></label>
                <label style="grid-column:1/-1">Note<textarea name="note" rows="2"></textarea></label>
                <div style="grid-column:1/-1"><button class="roster-btn roster-btn-primary">Save availability</button></div>
            </form>
        </section>
        <section class="roster-card">
            <div class="roster-card-head"><div><h2>Open shift</h2><p>Publish an unfilled duty without assigning an employee prematurely.</p></div></div>
            <form method="post" action="{{ route('roster.open-shifts.store') }}" class="roster-form-grid">@csrf
                <label>Roster plan<select name="roster_plan_id" required>@foreach($plans as $plan)<option value="{{ $plan->id }}">{{ $plan->reference_no }} · {{ $plan->title }}</option>@endforeach</select></label>
                <label>Date<input type="date" name="duty_date" required></label>
                <label>Start<input type="time" name="start_time" required></label><label>End<input type="time" name="end_time" required></label>
                <label>Duty unit<select name="duty_unit_id" required>@foreach($units as $unit)<option value="{{ $unit->id }}">{{ $unit->name }}</option>@endforeach</select></label>
                <label>Position<select name="position_id"><option value="">Any suitable</option>@foreach($positions as $position)<option value="{{ $position->id }}">{{ $position->title }}</option>@endforeach</select></label>
                <label>Role<input name="duty_role" maxlength="150"></label><label>Staff required<input type="number" name="required_staff" min="1" max="50" value="1" required></label>
                <label style="grid-column:1/-1">Details<textarea name="details" rows="2"></textarea></label>
                <div style="grid-column:1/-1"><button class="roster-btn roster-btn-primary">Create open shift</button></div>
            </form>
        </section>
    </div>

    <section class="roster-card" style="margin-top:16px">
        <div class="roster-card-head"><div><h2>Open shifts</h2><p>Claims are compliance-checked for overlap, leave, availability, contract/registration validity and configured hour/rest warnings.</p></div></div>
        <div class="roster-table-wrap"><table class="roster-table"><thead><tr><th>Date</th><th>Time</th><th>Unit</th><th>Role</th><th>Coverage</th><th>Assign</th></tr></thead><tbody>
        @forelse($openShifts as $shift)<tr><td>{{ $shift->duty_date?->format('Y-m-d') }}</td><td>{{ substr($shift->start_time,0,5) }}–{{ substr($shift->end_time,0,5) }}</td><td>{{ $shift->unit?->name }}</td><td>{{ $shift->duty_role ?: 'Open duty' }}</td><td>{{ $shift->filled_staff }}/{{ $shift->required_staff }}</td><td><form method="post" action="{{ route('roster.open-shifts.claim',$shift) }}" style="display:flex;gap:8px">@csrf<select name="employee_id" required><option value="">Employee</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->display_name }}</option>@endforeach</select><button class="roster-btn roster-btn-secondary">Assign</button></form></td></tr>@empty<tr><td colspan="6">No open shifts.</td></tr>@endforelse
        </tbody></table></div>
    </section>

    <section class="roster-card" style="margin-top:16px">
        <div class="roster-card-head"><div><h2>Employee open-shift claims</h2><p>Employee claims do not change the roster until an authorised manager approves them.</p></div></div>
        <div class="roster-table-wrap"><table class="roster-table"><thead><tr><th>Employee</th><th>Shift</th><th>Compliance</th><th>Decision</th></tr></thead><tbody>
        @forelse($openShiftClaims as $claim)<tr><td>{{ $claim->employee?->display_name }}</td><td>{{ $claim->shift?->duty_date?->format('d M Y') }} · {{ $claim->shift?->unit?->name }}<br><small>{{ substr($claim->shift?->start_time ?? '',0,5) }}–{{ substr($claim->shift?->end_time ?? '',0,5) }}</small></td><td>@php($warnings=$claim->compliance_snapshot['warnings']??[])@if($warnings)<span class="status-pill cross-unit">{{ count($warnings) }} warning(s)</span>@else<span class="status-pill home-unit">Passed</span>@endif</td><td><form method="post" action="{{ route('roster.open-shift-claims.review',$claim) }}" style="display:flex;gap:6px">@csrf<input name="review_note" placeholder="Review note"><button name="action" value="approve" class="roster-btn roster-btn-primary">Approve</button><button name="action" value="reject" class="roster-btn roster-btn-secondary">Reject</button></form></td></tr>@empty<tr><td colspan="4">No employee claims awaiting review.</td></tr>@endforelse
        </tbody></table></div>
    </section>

    <div class="roster-grid-2" style="margin-top:16px">
        <section class="roster-card"><div class="roster-card-head"><div><h2>Upcoming availability</h2></div></div><div class="roster-table-wrap"><table class="roster-table"><thead><tr><th>Date</th><th>Employee</th><th>Type</th><th>Window</th></tr></thead><tbody>@forelse($availability as $a)<tr><td>{{ $a->available_date?->format('Y-m-d') }}</td><td>{{ $a->employee?->display_name }}</td><td>{{ ucfirst($a->availability_type) }}</td><td>{{ $a->available_from ? substr($a->available_from,0,5) : '—' }} – {{ $a->available_to ? substr($a->available_to,0,5) : '—' }}</td></tr>@empty<tr><td colspan="4">No availability records.</td></tr>@endforelse</tbody></table></div></section>
        <section class="roster-card"><div class="roster-card-head"><div><h2>Shift change requests</h2></div></div><div class="roster-table-wrap"><table class="roster-table"><thead><tr><th>Employee</th><th>Duty</th><th>Target</th><th>Status</th><th>Decision</th></tr></thead><tbody>@forelse($swaps as $swap)<tr><td>{{ $swap->requester?->display_name }}</td><td>{{ $swap->assignment?->duty_date?->format('Y-m-d') }} {{ $swap->assignment?->start_time }}</td><td>{{ $swap->target?->display_name ?: 'Open replacement' }}</td><td>{{ str_replace('_',' ',$swap->status) }}</td><td>@if($swap->status==='pending_manager')<form method="post" action="{{ route('roster.swaps.decide',$swap) }}" style="display:flex;gap:6px;min-width:280px">@csrf<input type="hidden" name="action" value="approve">@if(!$swap->target_employee_id)<select name="target_employee_id" required><option value="">Replacement employee</option>@foreach($employees as $employee)@if($employee->id !== $swap->requested_by_employee_id)<option value="{{ $employee->id }}">{{ $employee->display_name }}</option>@endif @endforeach</select>@endif<button class="roster-btn roster-btn-secondary">Approve</button></form>@elseif($swap->status==='pending_peer')<span class="text-muted">Waiting for peer acceptance</span>@endif</td></tr>@empty<tr><td colspan="5">No shift changes.</td></tr>@endforelse</tbody></table></div></section>
    </div>

    <section class="roster-card" style="margin-top:16px">
      <div class="roster-card-head"><div><h2>Safe staffing rule</h2><p>Configure minimum coverage; legal/clinical requirements remain client-governed and effective policy must be verified before use.</p></div></div>
      <form method="post" action="{{ route('roster.staffing-rules.store') }}" class="roster-form-grid">@csrf
        <label>Unit<select name="unit_id" required>@foreach($units as $unit)<option value="{{ $unit->id }}">{{ $unit->name }}</option>@endforeach</select></label>
        <label>Day<select name="day_of_week"><option value="">Every day</option><option value="1">Monday</option><option value="2">Tuesday</option><option value="3">Wednesday</option><option value="4">Thursday</option><option value="5">Friday</option><option value="6">Saturday</option><option value="0">Sunday</option></select></label>
        <label>Start<input type="time" name="start_time" required></label><label>End<input type="time" name="end_time" required></label>
        <label>Position<select name="position_id"><option value="">Any</option>@foreach($positions as $position)<option value="{{ $position->id }}">{{ $position->title }}</option>@endforeach</select></label>
        <label>Required competency<select name="competency_id"><option value="">None</option>@foreach($competencies as $competency)<option value="{{ $competency->id }}">{{ $competency->name }}</option>@endforeach</select></label>
        <label>Minimum staff<input type="number" name="minimum_staff" min="1" max="100" value="1" required></label>
        <label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="is_hard_stop" value="1"> Hard stop when policy requires</label>
        <div><button class="roster-btn roster-btn-primary">Add staffing rule</button></div>
      </form>
    </section>

    <section class="roster-card" style="margin-top:16px">
      <div class="roster-card-head"><div><h2>Active staffing / skill-mix rules</h2><p>Competency rules use the employee competency register and respect competency expiry dates.</p></div></div>
      <div class="roster-table-wrap"><table class="roster-table"><thead><tr><th>Unit</th><th>Day</th><th>Time</th><th>Position</th><th>Competency</th><th>Minimum</th><th>Enforcement</th></tr></thead><tbody>
      @forelse($staffingRules as $rule)<tr><td>{{ $units->firstWhere('id',$rule->unit_id)?->name }}</td><td>{{ $rule->day_of_week===null?'Every day':['Sun','Mon','Tue','Wed','Thu','Fri','Sat'][$rule->day_of_week] }}</td><td>{{ substr($rule->start_time,0,5) }}–{{ substr($rule->end_time,0,5) }}</td><td>{{ $rule->position?->title ?: 'Any' }}</td><td>{{ $rule->competency?->name ?: 'None' }}</td><td>{{ $rule->minimum_staff }}</td><td><span class="status-pill {{ $rule->is_hard_stop?'cross-unit':'home-unit' }}">{{ $rule->is_hard_stop?'Hard stop':'Warning' }}</span></td></tr>@empty<tr><td colspan="7">No safe-staffing rules configured.</td></tr>@endforelse
      </tbody></table></div>
    </section>

    <section class="roster-card" style="margin-top:16px">
      <div class="roster-card-head"><div><h2>30-day roster fairness</h2><p>Visibility aid only; managers remain responsible for context, leave, accommodations and applicable employment rules.</p></div></div>
      <div class="roster-table-wrap"><table class="roster-table"><thead><tr><th>Employee</th><th>Total duties</th><th>Night</th><th>Weekend</th><th>Cross-unit</th></tr></thead><tbody>@forelse($fairness as $row)<tr><td>{{ $row['employee']?->display_name }}</td><td>{{ $row['duties'] }}</td><td>{{ $row['night'] }}</td><td>{{ $row['weekend'] }}</td><td>{{ $row['cross'] }}</td></tr>@empty<tr><td colspan="5">No roster activity in the last 30 days.</td></tr>@endforelse</tbody></table></div>
    </section>
</div>
@endsection
