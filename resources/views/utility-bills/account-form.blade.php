@extends('layouts.app')
@section('title', 'Add Utility Account')
@section('content')
<div style="max-width:900px;margin:auto">
    <h2 class="md-headline-sm">Add Utility Account</h2>
    <p class="md-body-sm">Register the provider account once, then attach monthly/periodic bills to it.</p>
    @include('partials.governance-legal-safeguard', [
        'title' => 'Utility account master-data safeguard',
        'purpose' => 'Account details are reused across future bills and reports. Verify them against the official provider/account source before saving.',
        'items' => [
            'Confirm provider, account/service number and service location.',
            'Link a unit only when the account genuinely belongs to that unit; otherwise keep it institution-wide.',
            'Do not store online-banking passwords, PINs, card details or other credentials in this form.',
        ],
    ])
    <form method="POST" action="{{ route('utility-bills.accounts.store') }}" class="workforce-panel" style="margin-top:16px">@csrf
        <div class="md-form-row">
            <div class="md-form-group">
<label class="md-label">Utility Type *</label>
<select class="md-input" name="utility_type" required>
@foreach(['electricity','water','telephone','internet','sewerage','gas','other'] as $type)<option value="{{ $type }}" @selected(old('utility_type')===$type)>{{ ucfirst($type) }}</option>@endforeach
</select>
</div>
            <div class="md-form-group">
<label class="md-label">Provider *</label>
<input class="md-input" name="provider_name" value="{{ old('provider_name') }}" required>
</div>
        </div>
        <div class="md-form-row">
            <div class="md-form-group">
<label class="md-label">Account No *</label>
<input class="md-input" name="account_no" value="{{ old('account_no') }}" required>
</div>
            <div class="md-form-group">
<label class="md-label">Meter / Service No</label>
<input class="md-input" name="meter_no" value="{{ old('meter_no') }}">
</div>
        </div>
        <div class="md-form-row">
            <div class="md-form-group">
<label class="md-label">Service Location *</label>
<input class="md-input" name="service_location" value="{{ old('service_location') }}" required>
</div>
            <div class="md-form-group">
<label class="md-label">Linked Unit</label>
<select class="md-input" name="unit_id">
<option value="">Institution-wide / Not linked</option>
@foreach($units as $unit)<option value="{{ $unit->id }}" @selected((string)old('unit_id')===(string)$unit->id)>{{ $unit->name }} ({{ $unit->code }})</option>@endforeach
</select>
</div>
        </div>
        <div class="md-form-group">
<label class="md-label">Provider Contact / Reference</label>
<input class="md-input" name="contact_reference" value="{{ old('contact_reference') }}">
</div>
        <div class="md-form-group">
<label class="md-label">Notes</label>
<textarea class="md-input" name="notes" rows="3">{{ old('notes') }}</textarea>
</div>
        <div class="md-form-group">
<label class="md-label">Official Administrative Purpose *</label>
<input class="md-input" name="administrative_purpose" maxlength="500" value="{{ old('administrative_purpose', 'Register utility account for institutional bill monitoring') }}" required>
</div>
        <div class="alert alert-info">
<strong>Master-data confirmation</strong>
<br>
<label>
<input type="checkbox" name="source_verified" value="1" required> I verified the account details against an official source.</label>
<br>
<label>
<input type="checkbox" name="accuracy_confirmed" value="1" required> I checked the provider, account/service number and location for accuracy.</label>
</div>
        <div style="display:flex;justify-content:flex-end;gap:8px">
<a class="md-btn md-btn--text" href="{{ route('utility-bills.index') }}">Cancel</a>
<button class="md-btn md-btn--filled">Save Verified Account</button>
</div>
    </form>
</div>
@endsection
