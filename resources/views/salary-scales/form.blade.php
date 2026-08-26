@extends('layouts.app')
@section('title', $salaryScale->exists ? 'Edit Salary Scale' : 'New Salary Scale')
@section('content')

<div style="max-width:560px;margin:0 auto;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
        <a href="{{ route('salary-scales.index') }}" class="md-btn md-btn--icon">&#8592;</a>
        <h2 class="md-headline-sm">{{ $salaryScale->exists ? 'Edit Salary Scale' : 'New Salary Scale' }}</h2>
    </div>

    @if($errors->any())
    <div style="background:var(--md-error-container);color:var(--md-on-error-container);
                padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
        <ul style="margin:0;padding-left:16px;">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST"
          action="{{ $salaryScale->exists ? route('salary-scales.update', $salaryScale) : route('salary-scales.store') }}"
          class="md-card md-card--elevated">
        @csrf
        @if($salaryScale->exists) @method('PUT') @endif

        <div class="md-card__body">
            <div style="display:grid;grid-template-columns:1fr 2fr;gap:14px;margin-bottom:14px;">
                <div class="md-field">
                    <label class="md-field__label">
                        Code <span style="color:var(--md-error)">*</span>
                    </label>
                    <input type="text" name="code"
                           class="md-field__input @error('code') md-field--error @enderror"
                           value="{{ old('code', $salaryScale->code) }}"
                           placeholder="e.g. MN-1" maxlength="30" required>
                    @error('code')<div class="md-field__error">{{ $message }}</div>@enderror
                </div>

                <div class="md-field">
                    <label class="md-field__label">
                        Name <span style="color:var(--md-error)">*</span>
                    </label>
                    <input type="text" name="name"
                           class="md-field__input @error('name') md-field--error @enderror"
                           value="{{ old('name', $salaryScale->name) }}"
                           placeholder="e.g. Management Assistant Grade III" maxlength="150" required>
                    @error('name')<div class="md-field__error">{{ $message }}</div>@enderror
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:14px;">
                <div class="md-field">
                    <label class="md-field__label">Min Salary (Rs.)</label>
                    <input type="number" name="min_salary" step="0.01" min="0"
                           class="md-field__input @error('min_salary') md-field--error @enderror"
                           value="{{ old('min_salary', $salaryScale->min_salary) }}">
                    @error('min_salary')<div class="md-field__error">{{ $message }}</div>@enderror
                </div>
                <div class="md-field">
                    <label class="md-field__label">Max Salary (Rs.)</label>
                    <input type="number" name="max_salary" step="0.01" min="0"
                           class="md-field__input @error('max_salary') md-field--error @enderror"
                           value="{{ old('max_salary', $salaryScale->max_salary) }}">
                    @error('max_salary')<div class="md-field__error">{{ $message }}</div>@enderror
                </div>
                <div class="md-field">
                    <label class="md-field__label">Increment Amount (Rs.)</label>
                    <input type="number" name="increment_amount" step="0.01" min="0"
                           class="md-field__input @error('increment_amount') md-field--error @enderror"
                           value="{{ old('increment_amount', $salaryScale->increment_amount) }}">
                    @error('increment_amount')<div class="md-field__error">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="md-field">
                <label class="md-field__label">Notes</label>
                <textarea name="notes" rows="3" maxlength="500"
                          class="md-field__input @error('notes') md-field--error @enderror"
                          placeholder="Optional">{{ old('notes', $salaryScale->notes) }}</textarea>
                @error('notes')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="md-card__footer" style="display:flex;justify-content:flex-end;gap:10px;">
            <a href="{{ route('salary-scales.index') }}" class="md-btn md-btn--outlined">Cancel</a>
            <button type="submit" class="md-btn md-btn--filled">
                {{ $salaryScale->exists ? 'Save Changes' : 'Create Salary Scale' }}
            </button>
        </div>
    </form>
</div>

@endsection
