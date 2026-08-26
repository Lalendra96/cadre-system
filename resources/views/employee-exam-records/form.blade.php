@extends('layouts.app')
@section('title', 'Add Exam Record — ' . $employee->display_name)
@section('content')
<div style="max-width:560px;margin:0 auto;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
        <a href="{{ route('employee-exam-records.index', $employee) }}" class="md-btn md-btn--icon">&#8592;</a>
        <h2 class="md-headline-sm">Add Exam Record</h2>
    </div>
    @if($errors->any())
    <div style="background:var(--md-error-container);color:var(--md-on-error-container);padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
        <ul style="margin:0;padding-left:16px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif
    <form method="POST" action="{{ route('employee-exam-records.store', $employee) }}" class="md-card md-card--elevated">
        @csrf
        <div class="md-card__body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                <div class="md-field">
                    <label class="md-field__label">Exam Type <span style="color:var(--md-error)">*</span></label>
                    <select name="exam_type" class="md-field__input @error('exam_type') md-field--error @enderror" required>
                        <option value="">— Select —</option>
                        @foreach(\App\Models\EmployeeExamRecord::TYPE_LABELS as $key => $label)
                            <option value="{{ $key }}" {{ old('exam_type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('exam_type')<div class="md-field__error">{{ $message }}</div>@enderror
                </div>
                <div class="md-field">
                    <label class="md-field__label">Result <span style="color:var(--md-error)">*</span></label>
                    <select name="result" class="md-field__input @error('result') md-field--error @enderror" required>
                        @foreach(\App\Models\EmployeeExamRecord::RESULT_LABELS as $key => $label)
                            <option value="{{ $key }}" {{ old('result', 'pending') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('result')<div class="md-field__error">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="md-field" style="margin-bottom:14px;">
                <label class="md-field__label">Exam Name</label>
                <input type="text" name="exam_name" class="md-field__input @error('exam_name') md-field--error @enderror"
                       value="{{ old('exam_name') }}" placeholder="e.g. Efficiency Bar Exam - Group A" maxlength="200">
                @error('exam_name')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                <div class="md-field">
                    <label class="md-field__label">Exam Date</label>
                    <input type="date" name="exam_date" class="md-field__input @error('exam_date') md-field--error @enderror" value="{{ old('exam_date') }}">
                    @error('exam_date')<div class="md-field__error">{{ $message }}</div>@enderror
                </div>
                <div class="md-field">
                    <label class="md-field__label">Reference No.</label>
                    <input type="text" name="reference_no" class="md-field__input @error('reference_no') md-field--error @enderror"
                           value="{{ old('reference_no') }}" maxlength="60">
                    @error('reference_no')<div class="md-field__error">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="md-field">
                <label class="md-field__label">Notes</label>
                <textarea name="notes" rows="2" maxlength="500" class="md-field__input @error('notes') md-field--error @enderror">{{ old('notes') }}</textarea>
                @error('notes')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="md-card__footer" style="display:flex;justify-content:flex-end;gap:10px;">
            <a href="{{ route('employee-exam-records.index', $employee) }}" class="md-btn md-btn--outlined">Cancel</a>
            <button type="submit" class="md-btn md-btn--filled">💾 Save</button>
        </div>
    </form>
</div>
@endsection
