@extends('layouts.app')
@section('title', 'Utility Bill Administration')
@section('content')
<div style="margin-bottom:16px">
<h2 class="md-headline-sm">💡 Utility Bill Administration</h2>
<p class="md-body-sm">Assign the responsible Subject Officer and configure monitoring thresholds. Module enable/disable is managed under Feature Management.</p>
</div>
@include('partials.governance-legal-safeguard', [
    'title' => 'Responsibility assignment safeguard',
    'purpose' => 'Assign utility-bill responsibility only through an authorised administrative instruction and keep a traceable reference. Reassignment ends the previous responsibility; it must not erase the history.',
    'items' => [
        'Confirm the selected officer is the current authorised Subject Officer for this function.',
        'Record the appointment/file/minute reference and effective date.',
        'Use the module disable switch for controlled suspension; do not remove historical bills or payments.',
        'Review assignment history when responsibility changes so accountability remains continuous.',
    ],
])
<div class="workforce-panel">
<div class="panel-title">Responsibility & Monitoring</div>
<form method="POST" action="{{ route('utility-bills.admin.governance') }}" style="margin-top:14px">@csrf
<div class="md-form-row">
<div class="md-form-group">
<label class="md-label">Responsible Subject Officer *</label>
<select class="md-input" name="subject_officer_id" required>
<option value="">Select officer</option>
@foreach($subjectOfficers as $officer)<option value="{{ $officer->id }}" @selected((string)old('subject_officer_id', $currentAssignment?->subject_officer_id)===(string)$officer->id)>{{ $officer->name }} · {{ $officer->email }}</option>@endforeach
</select>
</div>
<div class="md-form-group">
<label class="md-label">Effective From *</label>
<input class="md-input" type="date" name="effective_from" value="{{ old('effective_from', $currentAssignment?->effective_from?->format('Y-m-d') ?? now()->toDateString()) }}" required>
</div>
</div>
<div class="md-form-row">
<div class="md-form-group">
<label class="md-label">Authority / Appointment Reference *</label>
<input class="md-input" name="reference_no" value="{{ old('reference_no', $currentAssignment?->reference_no) }}" required>
<small>Minute, file, appointment or other traceable institutional authority.</small>
</div>
<div class="md-form-group">
<label class="md-label">Due Warning Days *</label>
<input class="md-input" type="number" min="1" max="60" name="warning_days" value="{{ old('warning_days', $warningDays) }}" required>
</div>
</div>
<div class="md-form-group">
<label class="md-label">Assignment Reason / Authority *</label>
<textarea class="md-input" name="reason" rows="3" required>{{ old('reason', $currentAssignment?->reason) }}</textarea>
</div>
<div class="alert alert-warning">
<strong>Confirm assignment controls</strong>
<br>
<label>
<input type="checkbox" name="authority_confirmed" value="1" required> I verified the authority/reference for this responsibility assignment.</label>
<br>
<label>
<input type="checkbox" name="continuity_confirmed" value="1" required> I understand this change preserves prior assignment history and transfers current responsibility from the effective date.</label>
</div>
<div style="text-align:right">
<button class="md-btn md-btn--filled">Save Governed Assignment</button>
</div>
</form>
</div>
<div class="workforce-panel" style="margin-top:16px">
<div class="panel-title">Assignment History</div>
<div style="overflow:auto">
<table class="md-table" style="width:100%">
<thead>
<tr>
<th>Officer</th>
<th>Effective From</th>
<th>Effective To</th>
<th>Reference</th>
<th>Assigned By</th>
<th>Status</th>
</tr>
</thead>
<tbody>
@forelse($assignmentHistory as $a)<tr>
<td>{{ $a->subjectOfficer?->name }}</td>
<td>{{ $a->effective_from?->format('d M Y') }}</td>
<td>{{ $a->effective_to?->format('d M Y') ?: '—' }}</td>
<td>{{ $a->reference_no ?: '—' }}</td>
<td>{{ $a->assignedBy?->name }}</td>
<td>{{ $a->ended_at ? 'Ended' : 'Current' }}</td>
</tr>@empty
<tr>
<td colspan="6">No assignment history.</td>
</tr>@endforelse
</tbody>
</table>
</div>
</div>
@endsection
