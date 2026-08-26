@extends('layouts.app')
@section('title', 'Add Qualification — ' . $employee->display_name)
@section('content')
<div style="max-width:560px;margin:0 auto;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
        <a href="{{ route('employee-qualifications.index', $employee) }}" class="md-btn md-btn--icon">&#8592;</a>
        <h2 class="md-headline-sm">Add Qualification</h2>
    </div>
    @if($errors->any())
    <div style="background:var(--md-error-container);color:var(--md-on-error-container);padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
        <ul style="margin:0;padding-left:16px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif
    <form method="POST" action="{{ route('employee-qualifications.store', $employee) }}" class="md-card md-card--elevated">
        @csrf
        <div class="md-card__body">
            <div class="md-field" style="margin-bottom:14px;">
                <label class="md-field__label">Qualification <span style="color:var(--md-error)">*</span></label>
                <input type="text" name="qualification_name" autofocus class="md-field__input @error('qualification_name') md-field--error @enderror"
                       value="{{ old('qualification_name') }}" placeholder="e.g. BSc Nursing, Diploma in Pharmacy" maxlength="200" required>
                @error('qualification_name')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>
            <div style="display:grid;grid-template-columns:2fr 1fr;gap:14px;margin-bottom:14px;">
                <div class="md-field">
                    <label class="md-field__label">Institution</label>
                    <input type="text" name="institution" class="md-field__input @error('institution') md-field--error @enderror"
                           value="{{ old('institution') }}" maxlength="200">
                    @error('institution')<div class="md-field__error">{{ $message }}</div>@enderror
                </div>
                <div class="md-field">
                    <label class="md-field__label">Year Obtained</label>
                    <input type="number" name="year_obtained" min="1960" max="{{ now()->year }}"
                           class="md-field__input @error('year_obtained') md-field--error @enderror" value="{{ old('year_obtained') }}">
                    @error('year_obtained')<div class="md-field__error">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="md-field" style="margin-bottom:14px;">
                <label class="md-field__label">Reference No.</label>
                <input type="text" name="reference_no" class="md-field__input @error('reference_no') md-field--error @enderror"
                       value="{{ old('reference_no') }}" placeholder="Certificate number" maxlength="60">
                @error('reference_no')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>
            <div class="md-field">
                <label class="md-field__label">Notes</label>
                <textarea name="notes" rows="2" maxlength="500" class="md-field__input @error('notes') md-field--error @enderror">{{ old('notes') }}</textarea>
                @error('notes')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="md-card__footer" style="display:flex;justify-content:flex-end;gap:10px;">
            <a href="{{ route('employee-qualifications.index', $employee) }}" class="md-btn md-btn--outlined">Cancel</a>
            <button type="submit" class="md-btn md-btn--filled">💾 Save</button>
        </div>
    </form>
</div>
@endsection
