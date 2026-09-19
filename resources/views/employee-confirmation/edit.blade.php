@extends('layouts.app')
@section('title', 'Confirmation in Service — ' . $employee->display_name)
@section('content')

<div style="max-width:560px;margin:0 auto;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
        <a href="{{ url()->previous() }}" class="md-btn md-btn--icon">&#8592;</a>
        <div>
            <h2 class="md-headline-sm">Confirmation in Service</h2>
            <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
                {{ $employee->display_name }} ({{ $employee->pay_no ?? '—' }}) — {{ $employee->position?->title }}
            </p>
        </div>
    </div>

    @if(session('success'))
    <div
        style="
            background: var(--md-success-container, #1b3a2d);
            color: var(--md-on-success-container, #9ef0b3);
            padding: 12px 18px;
            border-radius: var(--md-shape-sm);
            margin-bottom: 16px;
            font-size: 13px;
        "
    >
        ✓ {{ session('success') }}
    </div>
    @endif
    @if($errors->any())
    <div style="background:var(--md-error-container);color:var(--md-on-error-container);padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
        <ul style="margin: 0; padding-left: 16px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('employee-confirmation.update', $employee) }}" class="md-card md-card--elevated">
        @csrf
        @method('PUT')

        <div class="md-card__body">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-bottom:16px;">
                <input type="checkbox" name="is_confirmed" value="1" id="is_confirmed_check"
                       {{ old('is_confirmed', $employee->is_confirmed) ? 'checked' : '' }}
                       onchange="document.getElementById('confirmationFields').style.display = this.checked ? 'block' : 'none';">
                <span class="md-label-md">Confirmed in Service</span>
            </label>

            <div id="confirmationFields" style="display:{{ old('is_confirmed', $employee->is_confirmed) ? 'block' : 'none' }};">
                <div class="md-field" style="margin-bottom:14px;">
                    <label class="md-field__label">Date Confirmed</label>
                    <input type="date" name="date_confirmed"
                           class="md-field__input @error('date_confirmed') md-field--error @enderror"
                           value="{{ old('date_confirmed', $employee->date_confirmed?->format('Y-m-d')) }}">
                    @error('date_confirmed')<div class="md-field__error">{{ $message }}</div>@enderror
                </div>
                <div class="md-field">
                    <label class="md-field__label">Confirmation Reference No.</label>
                    <input type="text" name="confirmation_reference_no"
                           class="md-field__input @error('confirmation_reference_no') md-field--error @enderror"
                           value="{{ old('confirmation_reference_no', $employee->confirmation_reference_no) }}" maxlength="60">
                    @error('confirmation_reference_no')<div class="md-field__error">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        <div class="md-card__footer" style="display:flex;justify-content:flex-end;gap:10px;">
            <button type="submit" class="md-btn md-btn--filled">✅ Submit Confirmation Decision for Approval</button>
        </div>
    </form>
</div>
@endsection
