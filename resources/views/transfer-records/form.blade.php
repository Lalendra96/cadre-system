@extends('layouts.app')
@section('title', $record->exists ? 'Edit Transfer Record' : 'New Transfer Record')
@section('content')
<h2 class="md-headline-sm" style="margin-bottom:16px;">
    {{ $record->exists ? 'Edit Transfer Record' : 'Record Transfer' }}
</h2>

<div class="md-card md-card--elevated" style="max-width:640px;">
    <form method="POST"
          action="{{ $record->exists ? route('transfer-records.update', $record) : route('transfer-records.store') }}">
        @csrf
        @if ($record->exists)
            @method('PUT')
        @endif

        @if($entry)
            <input type="hidden" name="carder_entry_id" value="{{ $entry->id }}">
        @endif

        <div class="md-card__body" style="display:flex;flex-direction:column;gap:18px;">

            @if($errors->any())
                <div style="background:var(--md-error-container);color:var(--md-on-error-container);
                            padding:12px 16px;border-radius:var(--md-shape-sm);font-size:13px;">
                    <ul style="margin:0;padding-left:16px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($entry)
                <div style="background:var(--md-surface-container);padding:10px 14px;border-radius:var(--md-shape-sm);font-size:13px;">
                    Linked to entry:
                    <strong>{{ $entry->subjectCode->code ?? '' }}
                    {{ \DateTime::createFromFormat('!m', $entry->month)->format('M') }} {{ $entry->year }}</strong>
                </div>
            @endif

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="md-field">
                    <label class="md-field__label">Officer Name <span style="color:var(--md-error)">*</span></label>
                    <input type="text" name="employee_name"
                           class="md-field__input @error('employee_name') md-field--error @enderror"
                           value="{{ old('employee_name', $record->employee_name) }}"
                           list="employeeNamesList" required
                           placeholder="Name as it appears in the register">
                    <datalist id="employeeNamesList">
                        @foreach($employees as $e)
                            <option value="{{ $e->name }}">
                        @endforeach
                    </datalist>
                    @error('employee_name')
                        <div class="md-field__error">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="md-field">
                    <label class="md-field__label">Designation</label>
                    <input type="text" name="designation"
                           class="md-field__input @error('designation') md-field--error @enderror"
                           value="{{ old('designation', $record->designation) }}"
                           placeholder="e.g. Staff Nurse Grade II">
                    @error('designation')
                        <div class="md-field__error">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="md-field">
                    <label class="md-field__label">Direction <span style="color:var(--md-error)">*</span></label>
                    <select name="direction"
                            class="md-field__input @error('direction') md-field--error @enderror" required>
                        <option value="">— Select —</option>
                        <option value="in"  {{ old('direction', $record->direction) === 'in'  ? 'selected' : '' }}>▶ Transfer In</option>
                        <option value="out" {{ old('direction', $record->direction) === 'out' ? 'selected' : '' }}>◀ Transfer Out</option>
                    </select>
                    @error('direction')
                        <div class="md-field__error">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="md-field">
                    <label class="md-field__label">Transfer Type <span style="color:var(--md-error)">*</span></label>
                    <select name="transfer_type"
                            class="md-field__input @error('transfer_type') md-field--error @enderror" required>
                        @foreach(['permanent'=>'Permanent','temporary'=>'Temporary','deputation'=>'Deputation','secondment'=>'Secondment'] as $val => $lbl)
                            <option value="{{ $val }}"
                                    {{ old('transfer_type', $record->transfer_type ?? 'permanent') === $val ? 'selected' : '' }}>
                                {{ $lbl }}
                            </option>
                        @endforeach
                    </select>
                    @error('transfer_type')
                        <div class="md-field__error">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="md-field">
                    <label class="md-field__label">From (Institution / Ward)</label>
                    <input type="text" name="from_location"
                           class="md-field__input"
                           value="{{ old('from_location', $record->from_location) }}"
                           placeholder="e.g. District General Hospital, Kandy">
                </div>
                <div class="md-field">
                    <label class="md-field__label">To (Institution / Ward)</label>
                    <input type="text" name="to_location"
                           class="md-field__input"
                           value="{{ old('to_location', $record->to_location) }}"
                           placeholder="e.g. Ward 7 — Orthopaedics">
                </div>
            </div>

            <div class="md-field">
                <label class="md-field__label">Effective Date <span style="color:var(--md-error)">*</span></label>
                <input type="date" name="effective_date"
                       class="md-field__input @error('effective_date') md-field--error @enderror"
                       value="{{ old('effective_date', $record->effective_date?->format('Y-m-d') ?? today()->format('Y-m-d')) }}"
                       required style="max-width:200px;">
                @error('effective_date')
                    <div class="md-field__error">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div style="border-top:1px solid var(--md-outline-variant);margin:16px 0;padding-top:16px;">
                <div class="md-label-sm" style="color:var(--md-on-surface-variant);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;">
                    Transfer Board / PSC Authorisation (optional)
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                    <div class="md-field">
                        <label class="md-field__label">PSC Circular No.</label>
                        <input type="text" name="psc_circular_no"
                               class="md-field__input @error('psc_circular_no') md-field--error @enderror"
                               value="{{ old('psc_circular_no', $record->psc_circular_no) }}" maxlength="100">
                        @error('psc_circular_no')
                            <div class="md-field__error">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    <div class="md-field">
                        <label class="md-field__label">Transfer Board Ref. No.</label>
                        <input type="text" name="transfer_board_ref_no"
                               class="md-field__input @error('transfer_board_ref_no') md-field--error @enderror"
                               value="{{ old('transfer_board_ref_no', $record->transfer_board_ref_no) }}" maxlength="100">
                        @error('transfer_board_ref_no')
                            <div class="md-field__error">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>
                <div class="md-field">
                    <label class="md-field__label">Transfer Board Decision Date</label>
                    <input type="date" name="transfer_board_decision_date"
                           class="md-field__input @error('transfer_board_decision_date') md-field--error @enderror"
                           value="{{ old('transfer_board_decision_date', $record->transfer_board_decision_date?->format('Y-m-d')) }}"
                           style="max-width:220px;">
                    @error('transfer_board_decision_date')
                        <div class="md-field__error">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
            </div>

            <div class="md-field">
                <label class="md-field__label">Notes</label>
                <textarea name="notes" class="md-field__input" rows="2"
                          placeholder="Transfer order reference, MoH circular number, etc.">{{ old('notes', $record->notes) }}</textarea>
            </div>

        </div>

        <div class="md-card__footer">
            <a href="{{ route('transfer-records.index') }}" class="md-btn md-btn--text">Cancel</a>
            <button type="submit" class="md-btn md-btn--filled">{{ $record->exists ? 'Save Audited Correction' : 'Submit Transfer for Independent Approval' }}</button>
        </div>
    </form>
</div>
@endsection
