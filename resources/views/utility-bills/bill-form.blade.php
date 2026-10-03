@extends('layouts.app')
@section('title', 'Record Utility Bill')
@section('content')
<div style="max-width:980px;margin:auto">
    <h2 class="md-headline-sm">Record Utility Bill</h2>
    <p class="md-body-sm">Capture the official bill values and due date. Payment transactions are recorded after the bill is created.</p>
    @include('partials.governance-legal-safeguard', [
        'title' => 'Before recording this bill',
        'purpose' => 'Create the record from the official bill or authorised source document. This entry becomes part of the institutional monitoring and audit trail.',
        'items' => [
            'Match provider, account number, billing period, total and due date against the source bill.',
            'Use remarks only for information necessary to explain the institutional record.',
            'If the source bill is disputed or unclear, do not guess; record it only after the responsible process confirms the value.',
        ],
    ])
    <form method="POST" action="{{ route('utility-bills.store') }}" class="workforce-panel" style="margin-top:16px">@csrf
        <div class="md-form-group">
<label class="md-label">Utility Account *</label>
<select class="md-input" name="utility_account_id" required>
<option value="">Select account</option>
@foreach($accounts as $a)<option value="{{ $a->id }}" @selected((string)old('utility_account_id')===(string)$a->id)>{{ ucfirst($a->utility_type) }} · {{ $a->provider_name }} · {{ $a->account_no }} · {{ $a->service_location }}</option>@endforeach
</select>
</div>
        <div class="md-form-row">
<div class="md-form-group">
<label class="md-label">Billing Period From *</label>
<input class="md-input" type="date" name="billing_period_start" value="{{ old('billing_period_start') }}" required>
</div>
<div class="md-form-group">
<label class="md-label">Billing Period To *</label>
<input class="md-input" type="date" name="billing_period_end" value="{{ old('billing_period_end') }}" required>
</div>
</div>
        <div class="md-form-row">
<div class="md-form-group">
<label class="md-label">Bill Date *</label>
<input class="md-input" type="date" name="bill_date" value="{{ old('bill_date', now()->toDateString()) }}" required>
</div>
<div class="md-form-group">
<label class="md-label">Due Date *</label>
<input class="md-input" type="date" name="due_date" value="{{ old('due_date') }}" required>
</div>
<div class="md-form-group">
<label class="md-label">Bill Reference</label>
<input class="md-input" name="bill_reference" value="{{ old('bill_reference') }}">
</div>
</div>
        <div class="md-form-row">
<div class="md-form-group">
<label class="md-label">Previous Balance</label>
<input class="md-input" type="number" step="0.01" min="0" name="previous_balance" value="{{ old('previous_balance', 0) }}" required>
</div>
<div class="md-form-group">
<label class="md-label">Current Charges *</label>
<input class="md-input" type="number" step="0.01" min="0.01" name="current_charges" value="{{ old('current_charges') }}" required>
</div>
<div class="md-form-group">
<label class="md-label">Adjustments (+/-)</label>
<input class="md-input" type="number" step="0.01" name="adjustments" value="{{ old('adjustments', 0) }}" required>
</div>
<div class="md-form-group">
<label class="md-label">Total Bill Amount *</label>
<input class="md-input" type="number" step="0.01" min="0.01" name="bill_amount" value="{{ old('bill_amount') }}" required>
</div>
</div>
        <div class="md-form-row">
<div class="md-form-group">
<label class="md-label">Consumption</label>
<input class="md-input" name="consumption_value" value="{{ old('consumption_value') }}" placeholder="e.g. 18420">
</div>
<div class="md-form-group">
<label class="md-label">Consumption Unit</label>
<input class="md-input" name="consumption_unit" value="{{ old('consumption_unit') }}" placeholder="kWh / m³ / units">
</div>
</div>
        <div class="md-form-group">
<label class="md-label">Remarks</label>
<textarea class="md-input" name="remarks" rows="3">{{ old('remarks') }}</textarea>
</div>
        <div class="md-form-group">
<label class="md-label">Official Administrative Purpose *</label>
<input class="md-input" name="administrative_purpose" maxlength="500" value="{{ old('administrative_purpose', 'Utility bill monitoring and payment control') }}" required>
<small>State why this record is required for official work.</small>
</div>
        <div class="alert alert-info" style="margin-top:10px">
<strong>Source verification</strong>
<br>
<label>
<input type="checkbox" name="source_verified" value="1" required> I verified this entry against the official bill/source record.</label>
<br>
<label>
<input type="checkbox" name="accuracy_confirmed" value="1" required> I checked the material values and dates for accuracy.</label>
</div>
        <div style="display:flex;justify-content:flex-end;gap:8px">
<a class="md-btn md-btn--text" href="{{ route('utility-bills.index') }}">Cancel</a>
<button class="md-btn md-btn--filled">Save Verified Bill</button>
</div>
    </form>
</div>
@endsection
