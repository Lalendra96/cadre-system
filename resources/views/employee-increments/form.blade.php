@extends('layouts.app')
@section('title', 'Add Increment — ' . $employee->display_name)
@section('content')

<div style="max-width:560px;margin:0 auto;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
        <a href="{{ route('employee-increments.index', $employee) }}" class="md-btn md-btn--icon">&#8592;</a>
        <div>
            <h2 class="md-headline-sm">Propose Increment Record</h2>
            <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
                {{ $employee->display_name }} ({{ $employee->pay_no ?? '—' }})
            </p>
        </div>
    </div>

    @if($errors->any())
    <div style="background:var(--md-error-container);color:var(--md-on-error-container);
                padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
        <ul style="margin:0;padding-left:16px;">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('employee-increments.store', $employee) }}" class="md-card md-card--elevated">
        @csrf

        <div class="md-card__body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                <div class="md-field">
                    <label class="md-field__label">
                        Increment Date <span style="color:var(--md-error)">*</span>
                    </label>
                    <input type="date" name="increment_date" autofocus
                           class="md-field__input @error('increment_date') md-field--error @enderror"
                           value="{{ old('increment_date') }}" required>
                    @error('increment_date')<div class="md-field__error">{{ $message }}</div>@enderror
                </div>

                <div class="md-field">
                    <label class="md-field__label">Amount (Rs.)</label>
                    <input type="number" name="amount" step="0.01" min="0"
                           class="md-field__input @error('amount') md-field--error @enderror"
                           value="{{ old('amount') }}" placeholder="Optional">
                    @error('amount')<div class="md-field__error">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="md-field" style="margin-bottom:14px;">
                <label class="md-field__label">Reference No.</label>
                <input type="text" name="reference_no"
                       class="md-field__input @error('reference_no') md-field--error @enderror"
                       value="{{ old('reference_no') }}" placeholder="e.g. circular / gazette reference" maxlength="60">
                @error('reference_no')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>

            <div class="md-field">
                <label class="md-field__label">Notes</label>
                <textarea name="notes" rows="2" maxlength="500"
                          class="md-field__input @error('notes') md-field--error @enderror"
                          placeholder="Optional">{{ old('notes') }}</textarea>
                @error('notes')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="md-card__footer" style="display:flex;justify-content:flex-end;gap:10px;">
            <a href="{{ route('employee-increments.index', $employee) }}" class="md-btn md-btn--outlined">Cancel</a>
            <button type="submit" class="md-btn md-btn--filled">✅ Submit for Independent Approval</button>
        </div>
    </form>

    <p class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:12px;text-align:center;">
        A reminder will be sent automatically to the Subject Officer 30 days before this date.
    </p>
</div>

@endsection
