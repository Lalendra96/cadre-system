@extends('layouts.app')
@section('title', $employee->exists ? 'Edit Employee Profile' : 'New Employee Profile')

@push('head')
<style>
/* Retirement age auto-fill indicator */
.retirement-auto { color: var(--md-on-surface-variant); font-size: 11px; margin-top: 4px; }
</style>
@endpush

@section('content')

<div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
    <a href="{{ route('employees.index') }}" class="md-btn md-btn--icon" title="Back">&#8592;</a>
    <div>
        <h2 class="md-headline-sm">
            {{ $employee->exists ? 'Edit Employee Profile' : 'New Employee Profile' }}
        </h2>
        @if($employee->exists)
        <p class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:2px;">
            Pay No: <strong>{{ $employee->pay_no ?? '—' }}</strong>
            &nbsp;·&nbsp; Last updated: {{ $employee->updated_at?->format('d M Y H:i') ?? '—' }}
        </p>
        @endif
    </div>
</div>

@if($errors->any())
<div style="background:var(--md-error-container);color:var(--md-on-error-container);
            padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
    <div style="font-weight:600;margin-bottom:4px;">Please correct the following:</div>
    <ul style="margin:0;padding-left:18px;">
        @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
    </ul>
</div>
@endif

<form method="POST"
      action="{{ $employee->exists ? route('employees.update', $employee) : route('employees.store') }}"
      class="md-card md-card--elevated">
    @csrf
    @if($employee->exists) @method('PUT') @endif

    {{-- ── SECTION: Identity ──────────────────────────────────────────── --}}
    <div style="padding:16px 20px 0;border-bottom:1px solid var(--md-outline-variant);margin-bottom:16px;">
        <div class="md-title-sm" style="margin-bottom:12px;color:var(--md-primary);">
            👤 Personal Details
        </div>
        <div style="display:grid;grid-template-columns:120px 1fr 120px 140px 155px 140px;gap:12px;margin-bottom:16px;">

            {{-- Salutation --}}
            <div class="md-field">
                <label class="md-field__label">
                    Salutation
                </label>
                <select name="salutation"
                        class="md-field__input @error('salutation') md-field--error @enderror">
                    <option value="">—</option>
                    @foreach(['Mr.','Mrs.','Ms.','Miss','Dr.','Rev.','Prof.','Eng.'] as $s)
                        <option value="{{ $s }}"
                            {{ old('salutation', $employee->salutation) === $s ? 'selected' : '' }}>
                            {{ $s }}
                        </option>
                    @endforeach
                </select>
                @error('salutation')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>

            {{-- Full Name --}}
            <div class="md-field">
                <label class="md-field__label">
                    Full Name <span style="color:var(--md-error)">*</span>
                </label>
                <input type="text" name="name"
                       class="md-field__input @error('name') md-field--error @enderror"
                       value="{{ old('name', $employee->name) }}"
                       placeholder="As on National ID / appointment letter"
                       maxlength="150" required>
                @error('name')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>

            {{-- Gender --}}
            <div class="md-field">
                <label class="md-field__label">
                    Gender <span style="color:var(--md-error)">*</span>
                </label>
                <select name="gender"
                        class="md-field__input @error('gender') md-field--error @enderror"
                        required>
                    <option value="">—</option>
                    <option value="M" {{ old('gender', $employee->gender) === 'M' ? 'selected' : '' }}>Male</option>
                    <option value="F" {{ old('gender', $employee->gender) === 'F' ? 'selected' : '' }}>Female</option>
                    <option value="O" {{ old('gender', $employee->gender) === 'O' ? 'selected' : '' }}>Other</option>
                </select>
                @error('gender')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>

            {{-- Pay Number --}}
            <div class="md-field">
                <label class="md-field__label">Pay No.</label>
                <input type="text" name="pay_no"
                       class="md-field__input @error('pay_no') md-field--error @enderror"
                       value="{{ old('pay_no', $employee->pay_no) }}"
                       placeholder="e.g. PN-10001"
                       maxlength="50">
                @error('pay_no')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>

            {{-- NIC Number — sensitive PII: full value shown here only,
                 masked everywhere this employee appears in a list/report. --}}
            <div class="md-field">
                <label class="md-field__label">NIC Number</label>
                <input type="text" name="nic_number"
                       class="md-field__input @error('nic_number') md-field--error @enderror"
                       value="{{ old('nic_number', $employee->nic_number) }}"
                       placeholder="e.g. 851234567V or 200012345678"
                       maxlength="12"
                       autocomplete="off">
                @error('nic_number')<div class="md-field__error">{{ $message }}</div>@enderror
                <div class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:4px;font-size:10.5px;">
                    Old (9 digits + V/X) or new (12 digits) format.
                </div>
            </div>

            {{-- W&OP Number --}}
            <div class="md-field">
                <label class="md-field__label">W&amp;OP No.</label>
                <input type="text" name="wop_number"
                       class="md-field__input @error('wop_number') md-field--error @enderror"
                       value="{{ old('wop_number', $employee->wop_number) }}"
                       placeholder="Widows' & Orphans' Pension no."
                       maxlength="20">
                @error('wop_number')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>
        </div>

        {{-- Date of Birth / Date of Appointment / Date Reported for Duty / Retirement Age --}}
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:14px;margin-bottom:16px;">

            <div class="md-field">
                <label class="md-field__label">Date of Birth</label>
                <input type="date" name="date_of_birth"
                       id="date_of_birth"
                       class="md-field__input @error('date_of_birth') md-field--error @enderror"
                       value="{{ old('date_of_birth', $employee->date_of_birth?->format('Y-m-d')) }}"
                       max="{{ now()->subYears(18)->format('Y-m-d') }}"
                       min="1940-01-01"
                       onchange="calcRetirement()">
                @error('date_of_birth')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>

            <div class="md-field">
                <label class="md-field__label">Date of Appointment</label>
                <input type="date" name="date_of_appointment"
                       class="md-field__input @error('date_of_appointment') md-field--error @enderror"
                       value="{{ old('date_of_appointment', $employee->date_of_appointment?->format('Y-m-d')) }}"
                       max="{{ now()->format('Y-m-d') }}"
                       min="1980-01-01">
                @error('date_of_appointment')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>

            <div class="md-field">
                <label class="md-field__label">Date Reported for Duty</label>
                <input type="date" name="date_reported_for_duty"
                       class="md-field__input @error('date_reported_for_duty') md-field--error @enderror"
                       value="{{ old('date_reported_for_duty', $employee->date_reported_for_duty?->format('Y-m-d')) }}"
                       max="{{ now()->format('Y-m-d') }}"
                       min="1980-01-01">
                @error('date_reported_for_duty')<div class="md-field__error">{{ $message }}</div>@enderror
                <div class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:4px;font-size:10.5px;">
                    If different from appointment date.
                </div>
            </div>

            <div class="md-field">
                <label class="md-field__label">
                    Retirement Age <span style="color:var(--md-error)">*</span>
                </label>
                <select name="retirement_age"
                        id="retirement_age"
                        class="md-field__input @error('retirement_age') md-field--error @enderror">
                    <option value="">— Select —</option>
                    @foreach([55, 57, 60, 63, 65] as $age)
                        <option value="{{ $age }}"
                            {{ old('retirement_age', $employee->retirement_age) == $age ? 'selected' : '' }}>
                            {{ $age }} years
                        </option>
                    @endforeach
                </select>
                <div class="retirement-auto" id="retire_hint"></div>
                @error('retirement_age')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>
        </div>

        {{-- Salary Scale --}}
        <div class="md-field" style="margin-bottom:16px;max-width:340px;">
            <label class="md-field__label">Salary Scale</label>
            <select name="salary_scale_id"
                    class="md-field__input @error('salary_scale_id') md-field--error @enderror">
                <option value="">— Not set —</option>
                @foreach($salaryScales as $scale)
                    <option value="{{ $scale->id }}"
                        {{ old('salary_scale_id', $employee->salary_scale_id) == $scale->id ? 'selected' : '' }}>
                        {{ $scale->code }} — {{ $scale->name }}
                    </option>
                @endforeach
            </select>
            @error('salary_scale_id')<div class="md-field__error">{{ $message }}</div>@enderror
            @if($salaryScales->isEmpty())
                <div class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:4px;">
                    No salary scales configured yet.
                    @if(auth()->user()->isSuperAdmin() || auth()->user()->isPlanningOfficer())
                        <a href="{{ route('salary-scales.create') }}" style="color:var(--md-primary);">Add one →</a>
                    @endif
                </div>
            @endif
        </div>

        {{-- Confirmation in Service --}}
        <div style="background:var(--md-surface-container);border-radius:var(--md-shape-sm);padding:14px 16px;margin-bottom:8px;">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-bottom:{{ old('is_confirmed', $employee->is_confirmed) ? '12px' : '0' }};">
                <input type="checkbox" name="is_confirmed" value="1" id="is_confirmed_check"
                       {{ old('is_confirmed', $employee->is_confirmed) ? 'checked' : '' }}
                       onchange="document.getElementById('confirmationDetails').style.display = this.checked ? 'grid' : 'none';">
                <span class="md-label-md">Confirmed in Service</span>
            </label>
            <div id="confirmationDetails" style="display:{{ old('is_confirmed', $employee->is_confirmed) ? 'grid' : 'none' }};grid-template-columns:1fr 1fr;gap:14px;">
                <div class="md-field">
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
    </div>

    {{-- ── SECTION: Contact ───────────────────────────────────────────── --}}
    <div style="padding:0 20px 0;margin-bottom:16px;">
        <div class="md-title-sm" style="margin-bottom:12px;color:var(--md-primary);">
            📞 Contact Details
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px;">

            <div class="md-field">
                <label class="md-field__label">Email Address</label>
                <input type="email" name="email"
                       class="md-field__input @error('email') md-field--error @enderror"
                       value="{{ old('email', $employee->email) }}"
                       placeholder="official@health.gov.lk"
                       maxlength="150">
                @error('email')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>

            <div class="md-field">
                <label class="md-field__label">WhatsApp / Mobile</label>
                <input type="tel" name="whatsapp_mobile"
                       class="md-field__input @error('whatsapp_mobile') md-field--error @enderror"
                       value="{{ old('whatsapp_mobile', $employee->whatsapp_mobile) }}"
                       placeholder="e.g. 077 123 4567"
                       maxlength="20">
                @error('whatsapp_mobile')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    {{-- ── SECTION: Organisational ─────────────────────────────────────── --}}
    <div style="padding:0 20px 0;border-top:1px solid var(--md-outline-variant);
                padding-top:16px;margin-bottom:16px;">
        <div class="md-title-sm" style="margin-bottom:12px;color:var(--md-primary);">
            🏥 Organisational Details
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">

            {{-- Subject Code --}}
            <div class="md-field">
                <label class="md-field__label">
                    Subject Code <span style="color:var(--md-error)">*</span>
                </label>
                <select name="subject_code_id"
                        id="subject_code_select"
                        class="md-field__input @error('subject_code_id') md-field--error @enderror"
                        required
                        onchange="filterPositions()">
                    <option value="">— Select subject code —</option>
                    @foreach($assignedCodes as $sc)
                        <option value="{{ $sc->id }}"
                                data-positions="{{ $sc->positions->pluck('id')->join(',') }}"
                                {{ old('subject_code_id', $employee->subject_code_id) == $sc->id ? 'selected' : '' }}>
                            {{ $sc->code }} — {{ $sc->name }}
                        </option>
                    @endforeach
                </select>
                @error('subject_code_id')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>

            {{-- Position — filters based on selected subject code --}}
            <div class="md-field">
                <label class="md-field__label">
                    Position / Grade <span style="color:var(--md-error)">*</span>
                </label>
                <select name="position_id"
                        id="position_select"
                        class="md-field__input @error('position_id') md-field--error @enderror"
                        required>
                    <option value="">— Select subject code first —</option>
                    @foreach($positions as $pos)
                        <option value="{{ $pos->id }}"
                                class="pos-option"
                                {{ old('position_id', $employee->position_id) == $pos->id ? 'selected' : '' }}>
                            {{ $pos->title }}
                        </option>
                    @endforeach
                </select>
                <div class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:4px;">
                    Positions are filtered to those linked to the selected subject code.
                </div>
                @error('position_id')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>
        </div>

        {{-- Unit --}}
        <div class="md-field" style="margin-bottom:14px;">
            <label class="md-field__label">
                Current Unit / Ward <span style="color:var(--md-error)">*</span>
            </label>
            <select name="unit_id"
                    class="md-field__input @error('unit_id') md-field--error @enderror"
                    required>
                <option value="">— Select unit —</option>
                @foreach($units->groupBy('unit_type_id') as $typeId => $typeUnits)
                    @php $typeName = $typeUnits->first()->unitType?->name ?? 'Other' @endphp
                    <optgroup label="{{ $typeName }}">
                        @foreach($typeUnits as $u)
                            <option value="{{ $u->id }}"
                                {{ old('unit_id', $employee->unit_id) == $u->id ? 'selected' : '' }}>
                                {{ $u->code }} — {{ $u->name }}
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            @error('unit_id')<div class="md-field__error">{{ $message }}</div>@enderror
        </div>

        {{-- Notes --}}
        <div class="md-field" style="margin-bottom:16px;">
            <label class="md-field__label">Notes / Remarks</label>
            <textarea name="notes"
                      class="md-field__input @error('notes') md-field--error @enderror"
                      rows="3" maxlength="1000"
                      placeholder="Any relevant notes about this employee's posting, leave history, acting arrangements, etc.">{{ old('notes', $employee->notes) }}</textarea>
            @error('notes')<div class="md-field__error">{{ $message }}</div>@enderror
        </div>

        {{-- Active --}}
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" id="is_active" value="1"
                   {{ old('is_active', $employee->is_active ?? true) ? 'checked' : '' }}
                   style="width:18px;height:18px;cursor:pointer;">
            <label for="is_active" class="md-label-md" style="cursor:pointer;">
                Active — include in carder counts and reports
            </label>
        </div>
    </div>

    {{-- ── Footer ──────────────────────────────────────────────────────── --}}
    <div style="display:flex;justify-content:space-between;align-items:center;
                padding:14px 20px;border-top:1px solid var(--md-outline-variant);">
        @if($employee->exists)
        <div class="md-body-sm" style="color:var(--md-on-surface-variant);">
            Estimated retirement:
            @if($employee->retire_date)
                <strong>{{ $employee->retire_date->format('M Y') }}</strong>
                @if($employee->months_to_retirement !== null)
                    ({{ abs($employee->months_to_retirement) }} months
                    {{ $employee->months_to_retirement < 0 ? 'overdue' : 'away' }})
                @endif
            @else
                <em>— enter date of birth</em>
            @endif
        </div>
        @else
        <div></div>
        @endif
        <div style="display:flex;gap:10px;">
            <a href="{{ route('employees.index') }}" class="md-btn md-btn--outlined">Cancel</a>
            <button type="submit" class="md-btn md-btn--filled">
                {{ $employee->exists ? '💾 Save Changes' : '+ Create Profile' }}
            </button>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
// ── Position filter ────────────────────────────────────────────────────────
// When the officer selects a subject code, show only the positions that
// belong to that code (stored as data-positions on each <option>).

var allPositionOptions = Array.from(
    document.querySelectorAll('#position_select option.pos-option')
).map(function(opt) {
    return { value: opt.value, text: opt.text, el: opt };
});

function filterPositions() {
    var scSelect   = document.getElementById('subject_code_select');
    var posSelect  = document.getElementById('position_select');
    var selected   = scSelect.options[scSelect.selectedIndex];
    var currentPos = posSelect.value;

    // Reset position list
    posSelect.innerHTML = '';

    if (! selected || ! selected.value) {
        var placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = '— Select subject code first —';
        posSelect.appendChild(placeholder);
        return;
    }

    var allowed = (selected.dataset.positions || '').split(',').filter(Boolean);

    var placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = allowed.length ? '— Select position —' : '— No positions linked —';
    posSelect.appendChild(placeholder);

    allPositionOptions.forEach(function(opt) {
        if (allowed.length === 0 || allowed.indexOf(opt.value) !== -1) {
            var el = document.createElement('option');
            el.value = opt.value;
            el.textContent = opt.text;
            if (opt.value === currentPos) el.selected = true;
            posSelect.appendChild(el);
        }
    });

    // Auto-select if only one position
    if (posSelect.options.length === 2) {
        posSelect.options[1].selected = true;
    }
}

// ── Retirement date calculator ─────────────────────────────────────────────
function calcRetirement() {
    var dob     = document.getElementById('date_of_birth').value;
    var retAge  = document.getElementById('retirement_age');
    var hint    = document.getElementById('retire_hint');

    if (! dob || ! retAge.value) {
        hint.textContent = '';
        return;
    }

    var retireYear  = new Date(dob).getFullYear() + parseInt(retAge.value, 10);
    var retireMonth = new Date(dob).getMonth();
    var retireDate  = new Date(retireYear, retireMonth, new Date(dob).getDate());
    var now         = new Date();
    var diffMonths  = (retireDate.getFullYear() - now.getFullYear()) * 12
                    + (retireDate.getMonth() - now.getMonth());

    var monthStr = retireDate.toLocaleString('default', { month: 'long', year: 'numeric' });

    if (diffMonths < 0) {
        hint.textContent = '⚠ Retirement overdue — ' + monthStr + ' (' + Math.abs(diffMonths) + ' months past)';
        hint.style.color = 'var(--md-error)';
    } else if (diffMonths <= 12) {
        hint.textContent = '⚠ Retiring soon — ' + monthStr + ' (' + diffMonths + ' months)';
        hint.style.color = 'var(--md-warning, orange)';
    } else {
        hint.textContent = 'Estimated retirement: ' + monthStr + ' (' + diffMonths + ' months away)';
        hint.style.color = 'var(--md-on-surface-variant)';
    }
}

document.getElementById('retirement_age').addEventListener('change', calcRetirement);

// Run on page load for edit mode
document.addEventListener('DOMContentLoaded', function () {
    filterPositions();
    calcRetirement();
});
</script>
@endpush
