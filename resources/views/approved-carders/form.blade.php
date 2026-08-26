@extends('layouts.app')
@section('title', $carder->exists ? 'Edit Approved Carder' : 'New Approved Carder')
@section('content')

<div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
    <a href="{{ route('approved-carders.index') }}" class="md-btn md-btn--icon">&#8592;</a>
    <div>
        <h2 class="md-headline-sm">{{ $carder->exists ? 'Edit Approved Carder' : 'New Approved Carder Record' }}</h2>
        <div style="display:flex;align-items:center;gap:8px;margin-top:4px;">
            <span class="md-badge md-badge--warning">🔑 Director / Super Admin only</span>
            <span class="md-body-sm" style="color:var(--md-on-surface-variant);">
                These are official Ministry-approved figures. Changes are fully audit-logged.
            </span>
        </div>
    </div>
</div>

<form method="POST" action="{{ $carder->exists ? route('approved-carders.update', $carder) : route('approved-carders.store') }}" class="md-card md-card--elevated">
    @csrf
    @if($carder->exists) @method('PUT') @endif
    <div class="md-card__body">
        <div class="md-form-row">
            <div class="md-form-group">
                <label class="md-label md-label--required">Position</label>
                <select name="position_id" class="md-select @error('position_id') md-input--error @enderror" required>
                    <option value="">Select position…</option>
                    @foreach($positions as $p)
                        <option value="{{ $p->id }}" {{ old('position_id', $carder->position_id) == $p->id ? 'selected' : '' }}>{{ $p->title }}</option>
                    @endforeach
                </select>
                <div class="md-field-hint">The Designation total is the sum of all its Positions' approved amounts.</div>
                @error('position_id')<div class="md-field-error">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="md-form-row">
            <div class="md-form-group">
                <label class="md-label md-label--required">Year</label>
                <input type="number" name="year" class="md-input @error('year') md-input--error @enderror" value="{{ old('year', $carder->year ?? now()->year) }}" min="2000" max="2100" required>
                @error('year')<div class="md-field-error">{{ $message }}</div>@enderror
            </div>
            <div class="md-form-group">
                <label class="md-label md-label--required">Approved Amount</label>
                <input type="number" name="approved_amount" class="md-input @error('approved_amount') md-input--error @enderror" value="{{ old('approved_amount', $carder->approved_amount ?? 0) }}" min="0" required>
                @error('approved_amount')<div class="md-field-error">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="md-form-row">
            <div class="md-form-group">
                <label class="md-label">Ministry Reference No.</label>
                <input type="text" name="ministry_reference_no" class="md-input" value="{{ old('ministry_reference_no', $carder->ministry_reference_no) }}">
            </div>
            <div class="md-form-group">
                <label class="md-label">Approved Date</label>
                <input type="date" name="approved_date" class="md-input" value="{{ old('approved_date', optional($carder->approved_date)->format('Y-m-d')) }}">
            </div>
        </div>

        <div class="md-form-group">
            <label class="md-label">Remarks</label>
            <textarea name="remarks" class="md-textarea" rows="3">{{ old('remarks', $carder->remarks) }}</textarea>
        </div>
    </div>
    <div class="md-card__footer">
        <a href="{{ route('approved-carders.index') }}" class="md-btn md-btn--ghost">Cancel</a>
        <button type="submit" class="md-btn md-btn--primary">Save</button>
    </div>
</form>
@endsection
