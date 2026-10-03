@extends('layouts.app')

@section('title', 'Edit Car Pass')

@section('content')
    <div style="margin-bottom:16px;">
        <h2 class="md-headline-sm">Edit Car Pass {{ $carPass->reference_no }}</h2>
        <p class="md-body-sm">Only draft/returned requests can be edited. Approved/issued records are immutable.</p>
    </div>

    @if ($errors->any())
        <div class="md-card" style="padding:14px;margin-bottom:16px;background:#ffebee;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('car-passes.update', $carPass) }}">
        @csrf
        @method('PUT')
        <div class="workforce-panel" style="margin-bottom:16px;">
            <div class="panel-title">Employee</div>
            <p><strong>{{ $carPass->employee_name_snapshot }}</strong> ·
                {{ $carPass->position_snapshot ?: 'No post recorded' }} ·
                {{ $carPass->unit_snapshot ?: 'No unit recorded' }}</p>
        </div>
        <div class="workforce-panel">
            <div class="md-form-row">
                <div class="md-form-group">
                    <label class="md-label">Pass Format *</label>
                    <select name="template_id" class="md-input" required>
                        @foreach ($templates as $template)
                            <option value="{{ $template->id }}" @selected(old('template_id', $carPass->template_id) == $template->id)>{{ $template->name }} ·
                                v{{ $template->version }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md-form-group">
                    <label class="md-label">Vehicle Registration Number *</label>
                    <input name="vehicle_registration_no" class="md-input" required
                        value="{{ old('vehicle_registration_no', $carPass->vehicle_registration_no) }}">
                </div>
                <div class="md-form-group">
                    <label class="md-label">Vehicle Type *</label>
                    <select name="vehicle_type" class="md-input" required>
                        @foreach (['car' => 'Car', 'van' => 'Van', 'motorcycle' => 'Motorcycle', 'three_wheeler' => 'Three Wheeler', 'other' => 'Other'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('vehicle_type', $carPass->vehicle_type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="md-form-row">
                <div class="md-form-group"><label class="md-label">Valid From *</label><input type="date"
                        name="valid_from" class="md-input" required
                        value="{{ old('valid_from', $carPass->valid_from?->toDateString()) }}"></div>
                <div class="md-form-group"><label class="md-label">Valid To *</label><input type="date" name="valid_to"
                        class="md-input" required value="{{ old('valid_to', $carPass->valid_to?->toDateString()) }}"></div>
                <div class="md-form-group"><label class="md-label">Purpose</label><input name="purpose" class="md-input"
                        value="{{ old('purpose', $carPass->purpose) }}"></div>
            </div>
            <div class="md-form-group"><label class="md-label">Notes</label>
                <textarea name="notes" class="md-input" rows="3">{{ old('notes', $carPass->notes) }}</textarea>
            </div>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px;">
            <a href="{{ route('car-passes.show', $carPass) }}" class="md-btn md-btn--text">Cancel</a>
            <button class="md-btn md-btn--filled">Save Changes</button>
        </div>
    </form>
@endsection
