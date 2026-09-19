@extends('layouts.app')
@section('title', 'Propose Interdiction / Disciplinary Record — ' . $employee->display_name)
@section('content')
<div style="max-width:600px;margin:0 auto;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
        <a href="{{ route('employee-interdictions.index', $employee) }}" class="md-btn md-btn--icon">&#8592;</a>
        <h2 class="md-headline-sm">Add Interdiction Record</h2>
    </div>
    @if($errors->any())
    <div style="background:var(--md-error-container);color:var(--md-on-error-container);padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
        <ul style="margin: 0; padding-left: 16px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif
    <form method="POST" action="{{ route('employee-interdictions.store', $employee) }}" class="md-card md-card--elevated">
        @csrf
        <div class="md-card__body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                <div class="md-field">
                    <label class="md-field__label">Interdiction Date <span style="color:var(--md-error)">*</span></label>
                    <input
                        type="date"
                        name="interdiction_date"
                        class="md-field__input @error('interdiction_date') md-field--error @enderror"
                        value="{{ old('interdiction_date') }}"
                        required
                    >
                    @error('interdiction_date')
                        <div class="md-field__error">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="md-field">
                    <label class="md-field__label">Inquiry Status <span style="color:var(--md-error)">*</span></label>
                    <select
                        name="inquiry_status"
                        class="md-field__input @error('inquiry_status') md-field--error @enderror"
                        required
                    >
                        <option value="ongoing" {{ old('inquiry_status','ongoing')==='ongoing'?'selected':'' }}>Ongoing</option>
                        <option value="concluded" {{ old('inquiry_status')==='concluded'?'selected':'' }}>Concluded</option>
                    </select>
                    @error('inquiry_status')
                        <div class="md-field__error">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
            </div>
            <div class="md-field" style="margin-bottom:14px;">
                <label class="md-field__label">Reason</label>
                <textarea
                    name="reason"
                    rows="2"
                    maxlength="500"
                    class="md-field__input @error('reason') md-field--error @enderror"
                >{{ old('reason') }}</textarea>
                @error('reason')
                    <div class="md-field__error">
                        {{ $message }}
                    </div>
                @enderror
            </div>
            <div class="md-field" style="margin-bottom:14px;">
                <label class="md-field__label">Inquiry Reference No.</label>
                <input
                    type="text"
                    name="inquiry_reference_no"
                    class="md-field__input @error('inquiry_reference_no') md-field--error @enderror"
                    value="{{ old('inquiry_reference_no') }}"
                    maxlength="100"
                >
                @error('inquiry_reference_no')
                    <div class="md-field__error">
                        {{ $message }}
                    </div>
                @enderror
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                <div class="md-field">
                    <label class="md-field__label">Reinstatement Date</label>
                    <input
                        type="date"
                        name="reinstatement_date"
                        class="md-field__input @error('reinstatement_date') md-field--error @enderror"
                        value="{{ old('reinstatement_date') }}"
                    >
                    @error('reinstatement_date')
                        <div class="md-field__error">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="md-field">
                    <label class="md-field__label">Outcome</label>
                    <select
                        name="outcome"
                        class="md-field__input @error('outcome') md-field--error @enderror"
                    >
                        <option value="">— Not yet determined —</option>
                        @foreach(\App\Models\EmployeeInterdiction::OUTCOME_LABELS as $key => $label)
                            <option value="{{ $key }}" {{ old('outcome')===$key?'selected':'' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('outcome')
                        <div class="md-field__error">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
            </div>
            <div class="md-field">
                <label class="md-field__label">Notes</label>
                <textarea
                    name="notes"
                    rows="2"
                    maxlength="500"
                    class="md-field__input @error('notes') md-field--error @enderror"
                >{{ old('notes') }}</textarea>
                @error('notes')
                    <div class="md-field__error">
                        {{ $message }}
                    </div>
                @enderror
            </div>
        </div>
        <div class="md-card__footer" style="display:flex;justify-content:flex-end;gap:10px;">
            <a href="{{ route('employee-interdictions.index', $employee) }}" class="md-btn md-btn--outlined">Cancel</a>
            <button type="submit" class="md-btn md-btn--filled">✅ Submit for Independent Approval</button>
        </div>
    </form>
</div>
@endsection
