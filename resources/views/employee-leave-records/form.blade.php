@extends('layouts.app')
@section('title', 'Propose Leave Record — ' . $employee->display_name)
@section('content')
<div style="max-width:600px;margin:0 auto;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
        <a href="{{ route('employee-leave-records.index', $employee) }}" class="md-btn md-btn--icon">&#8592;</a>
        <h2 class="md-headline-sm">Add Leave Record</h2>
    </div>
    @if($errors->any())
    <div style="background:var(--md-error-container);color:var(--md-on-error-container);padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
        <ul style="margin:0;padding-left:16px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif
    <form method="POST" action="{{ route('employee-leave-records.store', $employee) }}" class="md-card md-card--elevated">
        @csrf
        <div class="md-card__body">
            <div class="md-field" style="margin-bottom:14px;">
                <label class="md-field__label">Leave Type <span style="color:var(--md-error)">*</span></label>
                <select name="leave_type" class="md-field__input @error('leave_type') md-field--error @enderror" required>
                    <option value="no_pay_leave" {{ old('leave_type','no_pay_leave')==='no_pay_leave'?'selected':'' }}>No-Pay Leave</option>
                    <option value="other" {{ old('leave_type')==='other'?'selected':'' }}>Other</option>
                </select>
                @error('leave_type')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                <div class="md-field">
                    <label class="md-field__label">Start Date <span style="color:var(--md-error)">*</span></label>
                    <input type="date" name="start_date" class="md-field__input @error('start_date') md-field--error @enderror" value="{{ old('start_date') }}" required>
                    @error('start_date')<div class="md-field__error">{{ $message }}</div>@enderror
                </div>
                <div class="md-field">
                    <label class="md-field__label">Expected Return Date</label>
                    <input type="date" name="expected_return_date" class="md-field__input @error('expected_return_date') md-field--error @enderror" value="{{ old('expected_return_date') }}">
                    @error('expected_return_date')<div class="md-field__error">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="md-field" style="margin-bottom:14px;">
                <label class="md-field__label">Reference No.</label>
                <input type="text" name="reference_no" class="md-field__input @error('reference_no') md-field--error @enderror" value="{{ old('reference_no') }}" maxlength="100">
                @error('reference_no')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>
            <div class="md-field">
                <label class="md-field__label">Notes</label>
                <textarea name="notes" rows="2" maxlength="500" class="md-field__input @error('notes') md-field--error @enderror">{{ old('notes') }}</textarea>
                @error('notes')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="md-card__footer" style="display:flex;justify-content:flex-end;gap:10px;">
            <a href="{{ route('employee-leave-records.index', $employee) }}" class="md-btn md-btn--outlined">Cancel</a>
            <button type="submit" class="md-btn md-btn--filled">✅ Submit for Independent Approval</button>
        </div>
    </form>
</div>
@endsection
