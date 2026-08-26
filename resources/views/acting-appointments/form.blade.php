@extends('layouts.app')
@section('title', $appointment->exists ? 'Edit Acting Appointment' : 'New Acting Appointment')
@section('content')
<h2 class="md-headline-sm" style="margin-bottom:16px;">
    {{ $appointment->exists ? 'Edit Acting Appointment' : 'New Acting Appointment' }}
</h2>

<div class="md-card md-card--elevated" style="max-width:640px;">
    <form method="POST"
          action="{{ $appointment->exists ? route('acting-appointments.update', $appointment) : route('acting-appointments.store') }}">
        @csrf
        @if($appointment->exists) @method('PUT') @endif

        <div class="md-card__body" style="display:flex;flex-direction:column;gap:18px;">

            @if($errors->any())
                <div style="background:var(--md-error-container);color:var(--md-on-error-container);
                            padding:12px 16px;border-radius:var(--md-shape-sm);font-size:13px;">
                    <ul style="margin:0;padding-left:16px;">
                        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <div class="md-field">
                <label class="md-field__label">Officer <span style="color:var(--md-error)">*</span></label>
                <select name="employee_id"
                        class="md-field__input @error('employee_id') md-field--error @enderror"
                        {{ $appointment->exists ? 'disabled' : '' }} required>
                    <option value="">— Select officer —</option>
                    @foreach($employees as $e)
                        <option value="{{ $e->id }}"
                                {{ old('employee_id', $appointment->employee_id) == $e->id ? 'selected' : '' }}>
                            {{ $e->name }} {{ $e->pay_no ? "({$e->pay_no})" : '' }}
                        </option>
                    @endforeach
                </select>
                @if($appointment->exists)
                    <input type="hidden" name="employee_id" value="{{ $appointment->employee_id }}">
                @endif
                @error('employee_id')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="md-field">
                    <label class="md-field__label">Acting Position <span style="color:var(--md-error)">*</span></label>
                    <select name="acting_position_id"
                            class="md-field__input @error('acting_position_id') md-field--error @enderror"
                            {{ $appointment->exists ? 'disabled' : '' }} required>
                        <option value="">— Select position —</option>
                        @foreach($positions as $p)
                            <option value="{{ $p->id }}"
                                    {{ old('acting_position_id', $appointment->acting_position_id) == $p->id ? 'selected' : '' }}>
                                {{ $p->title }}
                            </option>
                        @endforeach
                    </select>
                    @if($appointment->exists)
                        <input type="hidden" name="acting_position_id" value="{{ $appointment->acting_position_id }}">
                    @endif
                    @error('acting_position_id')<div class="md-field__error">{{ $message }}</div>@enderror
                </div>

                <div class="md-field">
                    <label class="md-field__label">Substantive Position <span style="color:var(--md-error)">*</span></label>
                    <select name="substantive_position_id"
                            class="md-field__input @error('substantive_position_id') md-field--error @enderror"
                            {{ $appointment->exists ? 'disabled' : '' }} required>
                        <option value="">— Select position —</option>
                        @foreach($positions as $p)
                            <option value="{{ $p->id }}"
                                    {{ old('substantive_position_id', $appointment->substantive_position_id) == $p->id ? 'selected' : '' }}>
                                {{ $p->title }}
                            </option>
                        @endforeach
                    </select>
                    @if($appointment->exists)
                        <input type="hidden" name="substantive_position_id" value="{{ $appointment->substantive_position_id }}">
                    @endif
                    @error('substantive_position_id')<div class="md-field__error">{{ $message }}</div>@enderror
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="md-field">
                    <label class="md-field__label">Start Date <span style="color:var(--md-error)">*</span></label>
                    <input type="date" name="start_date"
                           class="md-field__input @error('start_date') md-field--error @enderror"
                           value="{{ old('start_date', $appointment->start_date?->format('Y-m-d')) }}"
                           {{ $appointment->exists ? 'readonly' : '' }} required>
                    @error('start_date')<div class="md-field__error">{{ $message }}</div>@enderror
                </div>

                <div class="md-field">
                    <label class="md-field__label">End Date <span class="md-body-sm" style="color:var(--md-on-surface-variant);">(leave blank = ongoing)</span></label>
                    <input type="date" name="end_date"
                           class="md-field__input @error('end_date') md-field--error @enderror"
                           value="{{ old('end_date', $appointment->end_date?->format('Y-m-d')) }}">
                    @error('end_date')<div class="md-field__error">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="md-field">
                <label class="md-field__label">Appointment Order No.</label>
                <input type="text" name="appointment_order_no"
                       class="md-field__input @error('appointment_order_no') md-field--error @enderror"
                       value="{{ old('appointment_order_no', $appointment->appointment_order_no) }}"
                       placeholder="e.g. MoH/HRM/2026/0123">
                @error('appointment_order_no')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>

            <div class="md-field">
                <label class="md-field__label">Remarks</label>
                <textarea name="remarks" class="md-field__input" rows="3"
                          placeholder="Any additional notes…">{{ old('remarks', $appointment->remarks) }}</textarea>
            </div>

            @if($appointment->exists)
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                <input type="checkbox" name="is_active" value="1"
                       {{ old('is_active', $appointment->is_active) ? 'checked' : '' }}>
                <span class="md-label-lg">Appointment is active</span>
            </label>
            @endif

        </div>

        <div class="md-card__footer">
            <a href="{{ route('acting-appointments.index') }}" class="md-btn md-btn--text">Cancel</a>
            <button type="submit" class="md-btn md-btn--filled">
                {{ $appointment->exists ? 'Update' : 'Record Appointment' }}
            </button>
        </div>
    </form>
</div>
@endsection
