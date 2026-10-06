@extends('layouts.app')

@section('title', 'Guided Employee Profile')

@section('content')
<div class="page-header">
    <div>
        <div class="page-eyebrow">
            Guided profile entry
        </div>

        <h1 class="page-title">
            👤 Add Employee — Step by Step
        </h1>

        <p class="page-subtitle">
            Only the information needed to establish the employee record is requested
            now. Optional details can be completed later in Employee 360.
        </p>
    </div>

    <a
        class="md-btn md-btn--outlined"
        href="{{ route('incoming-officers.create') }}"
    >
        🏛 Transferring from another agency?
    </a>
</div>

@if ($errors->any())
    <div
        class="workforce-panel"
        style="
            border-left: 4px solid var(--md-error);
            margin-bottom: 16px;
        "
    >
        <strong>
            We couldn't save this profile yet.
        </strong>

        <div class="md-body-sm">
            Please correct the highlighted information. Your other entries
            have been kept.
        </div>
    </div>
@endif

<form
    method="POST"
    action="{{ route('employees.store') }}"
    id="profileWizard"
    data-error-fields="{{ json_encode($errors->keys()) }}"
>
    @csrf

    <input
        type="hidden"
        name="employment_status"
        value="active"
    >

    <input
        type="hidden"
        name="is_active"
        value="1"
    >

    <div
        style="
            display: flex;
            gap: 7px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        "
    >
        @foreach (
            [
                '👤 Person',
                '📞 Contact details',
                '💼 Position',
                '🏥 Workplace',
                '📅 Service dates',
                '🎖 Career',
                '✅ Review',
            ]
            as $index => $label
        )
            <span
                class="md-chip gw-chip"
                data-step="{{ $index + 1 }}"
            >
                {{ $label }}
            </span>
        @endforeach
    </div>

    <section
        class="workforce-panel gw-step"
        data-step="1"
    >
        <h2>👤 Who is the employee?</h2>

        <div
            style="
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                gap: 14px;
            "
        >
            <div class="md-field">
                <label>Salutation</label>

                <select
                    class="md-field__input"
                    name="salutation"
                >
                    <option value="">—</option>

                    @foreach (
                        [
                            'Mr.',
                            'Mrs.',
                            'Ms.',
                            'Miss',
                            'Dr.',
                            'Rev.',
                            'Prof.',
                            'Eng.',
                        ]
                        as $value
                    )
                        <option
                            value="{{ $value }}"
                            @selected(
                                old('salutation') === $value
                            )
                        >
                            {{ $value }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="md-field">
                <label>Full Name *</label>

                <input
                    class="md-field__input"
                    name="name"
                    required
                    value="{{ old('name') }}"
                    placeholder="As shown on official records"
                >
            </div>

            <div class="md-field">
                <label>Gender *</label>

                <select
                    class="md-field__input"
                    name="gender"
                    required
                >
                    <option value="">—</option>

                    <option
                        value="M"
                        @selected(old('gender') === 'M')
                    >
                        Male
                    </option>

                    <option
                        value="F"
                        @selected(old('gender') === 'F')
                    >
                        Female
                    </option>

                    <option
                        value="O"
                        @selected(old('gender') === 'O')
                    >
                        Other
                    </option>
                </select>
            </div>

            <div class="md-field">
                <label for="guidedPreferredLanguage">Preferred Language *</label>
                <select id="guidedPreferredLanguage" class="md-field__input" name="preferred_language" required>
                    <option value="en" @selected(old('preferred_language', 'en') === 'en')>English</option>
                    <option value="si" @selected(old('preferred_language', 'en') === 'si')>සිංහල</option>
                    <option value="ta" @selected(old('preferred_language', 'en') === 'ta')>தமிழ்</option>
                </select>
                @error('preferred_language')
                    <div class="md-field__error">{{ $message }}</div>
                @enderror
            </div>
            <div class="md-field">
                <label>NIC</label>

                <input
                    class="md-field__input"
                    name="nic_number"
                    value="{{ old('nic_number') }}"
                >
            </div>

            <div class="md-field">
                <label>Pay No.</label>

                <input
                    class="md-field__input"
                    name="pay_no"
                    value="{{ old('pay_no') }}"
                >
            </div>

            <div class="md-field">
                <label>Service File No.</label>

                <input
                    class="md-field__input"
                    name="service_file_no"
                    value="{{ old('service_file_no') }}"
                >
            </div>
        </div>
    </section>

    <section class="workforce-panel gw-step" data-step="2" style="display: none;">
        <h2>📞 Contact details</h2>
        <p class="md-body-sm">All contact details are optional. You can add or update them later in Employee 360.</p>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px">
            <div class="md-field">
                <label for="guided_email">Email address</label>
                <input type="email" id="guided_email" name="email" maxlength="150"
                    class="md-field__input @error('email') md-field--error @enderror"
                    aria-describedby="guided_email_error" value="{{ old('email') }}">
                <div id="guided_email_error">
                    @error('email')
                        <span class="md-field__error">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="md-field">
                <label for="guided_whatsapp_mobile">Mobile / WhatsApp number</label>
                <input type="tel" id="guided_whatsapp_mobile" name="whatsapp_mobile" maxlength="20"
                    class="md-field__input @error('whatsapp_mobile') md-field--error @enderror"
                    aria-describedby="guided_whatsapp_mobile_error" value="{{ old('whatsapp_mobile') }}">
                <div id="guided_whatsapp_mobile_error">
                    @error('whatsapp_mobile')
                        <span class="md-field__error">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="md-field">
                <label for="guided_permanent_address">Permanent address</label>
                <textarea id="guided_permanent_address" name="permanent_address" maxlength="1000"
                    class="md-field__input @error('permanent_address') md-field--error @enderror"
                    aria-describedby="guided_permanent_address_error" rows="3">{{ old('permanent_address') }}</textarea>
                <div id="guided_permanent_address_error">
                    @error('permanent_address')
                        <span class="md-field__error">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="md-field">
                <label for="guided_current_address">Current address</label>
                <textarea id="guided_current_address" name="current_address" maxlength="1000"
                    class="md-field__input @error('current_address') md-field--error @enderror"
                    aria-describedby="guided_current_address_error" rows="3">{{ old('current_address') }}</textarea>
                <div id="guided_current_address_error">
                    @error('current_address')
                        <span class="md-field__error">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="md-field">
                <label for="guided_emergency_contact_name">Emergency contact name</label>
                <input type="text" id="guided_emergency_contact_name" name="emergency_contact_name" maxlength="150"
                    class="md-field__input @error('emergency_contact_name') md-field--error @enderror"
                    aria-describedby="guided_emergency_contact_name_error" value="{{ old('emergency_contact_name') }}">
                <div id="guided_emergency_contact_name_error">
                    @error('emergency_contact_name')
                        <span class="md-field__error">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="md-field">
                <label for="guided_emergency_contact_relationship">Emergency contact relationship</label>
                <input type="text" id="guided_emergency_contact_relationship" name="emergency_contact_relationship" maxlength="80"
                    class="md-field__input @error('emergency_contact_relationship') md-field--error @enderror"
                    aria-describedby="guided_emergency_contact_relationship_error" value="{{ old('emergency_contact_relationship') }}">
                <div id="guided_emergency_contact_relationship_error">
                    @error('emergency_contact_relationship')
                        <span class="md-field__error">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="md-field">
                <label for="guided_emergency_contact_mobile">Emergency contact telephone</label>
                <input type="tel" id="guided_emergency_contact_mobile" name="emergency_contact_mobile" maxlength="30"
                    class="md-field__input @error('emergency_contact_mobile') md-field--error @enderror"
                    aria-describedby="guided_emergency_contact_mobile_error" value="{{ old('emergency_contact_mobile') }}">
                <div id="guided_emergency_contact_mobile_error">
                    @error('emergency_contact_mobile')
                        <span class="md-field__error">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>
    </section>

    <section
        class="workforce-panel gw-step"
        data-step="3"
        style="display: none;"
    >
        <h2>
            💼 What position does the employee hold?
        </h2>

        <div
            style="
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 14px;
            "
        >
            <div class="md-field">
                <label>Subject Code *</label>

                <select
                    class="md-field__input"
                    name="subject_code_id"
                    required
                >
                    <option value="">— Select —</option>

                    @foreach ($assignedCodes as $code)
                        <option
                            value="{{ $code->id }}"
                            @selected(
                                old('subject_code_id') == $code->id
                            )
                        >
                            {{ $code->code }} — {{ $code->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="md-field">
                <label>Position *</label>

                <select
                    class="md-field__input"
                    name="position_id"
                    required
                >
                    <option value="">— Select —</option>

                    @foreach ($positions as $position)
                        <option
                            value="{{ $position->id }}"
                            @selected(
                                old('position_id') == $position->id
                            )
                        >
                            {{ $position->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div
                class="md-field"
                style="grid-column: 1 / -1;"
            >
                <label>
                    Current Service / Cadre
                </label>

                <input
                    class="md-field__input"
                    name="current_service_name"
                    value="{{ old('current_service_name') }}"
                    placeholder="e.g. Nursing Service / Development Officers' Service"
                >
            </div>
        </div>
    </section>

    <section
        class="workforce-panel gw-step"
        data-step="4"
        style="display: none;"
    >
        <h2>
            🏥 Where is the employee currently working?
        </h2>

        <div class="md-field">
            <label>Current Unit / Ward *</label>

            <select
                class="md-field__input"
                name="unit_id"
                required
            >
                <option value="">
                    — Select unit —
                </option>

                @foreach ($units as $unit)
                    <option
                        value="{{ $unit->id }}"
                        @selected(
                            old('unit_id') == $unit->id
                        )
                    >
                        {{ $unit->code }} — {{ $unit->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div
            class="md-body-sm"
            style="margin-top: 8px;"
        >
            If this officer transferred from another government institution,
            use the Incoming Officer workflow instead so previous service
            is preserved.
        </div>
    </section>

    <section
        class="workforce-panel gw-step"
        data-step="5"
        style="display: none;"
    >
        <h2>📅 Important service dates</h2>

        <div
            style="
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 14px;
            "
        >
            <div class="md-field">
                <label>Date of Birth</label>

                <input
                    type="date"
                    class="md-field__input"
                    name="date_of_birth"
                    value="{{ old('date_of_birth') }}"
                >
            </div>

            <div class="md-field">
                <label>Date of Appointment</label>

                <input
                    type="date"
                    class="md-field__input"
                    name="date_of_appointment"
                    value="{{ old('date_of_appointment') }}"
                >
            </div>

            <div class="md-field">
                <label>Date Joined Public Service</label>

                <input
                    type="date"
                    class="md-field__input"
                    name="date_joined_public_service"
                    value="{{ old('date_joined_public_service') }}"
                >
            </div>

            <div class="md-field">
                <label>
                    Date Reported for Duty to this institute
                </label>

                <input
                    type="date"
                    class="md-field__input"
                    name="date_reported_for_duty"
                    value="{{ old('date_reported_for_duty') }}"
                >

                <div class="md-body-sm">
                    Use the assumption-of-duty/reporting letter date.
                </div>
            </div>
        </div>
    </section>

    <section
        class="workforce-panel gw-step"
        data-step="6"
        style="display: none;"
    >
        <h2>🎖 Career continuity</h2>

        <div
            style="
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 14px;
            "
        >
            <div class="md-field">
                <label>Combined Service Name</label>

                <input
                    class="md-field__input"
                    name="combined_service_name"
                    value="{{ old('combined_service_name') }}"
                >
            </div>

            <div class="md-field">
                <label>Date Joined Combined Service</label>

                <input
                    type="date"
                    class="md-field__input"
                    name="date_joined_combined_service"
                    value="{{ old('date_joined_combined_service') }}"
                >
            </div>

            <div class="md-field">
                <label>Date Current Grade Started</label>

                <input
                    type="date"
                    class="md-field__input"
                    name="date_current_grade"
                    value="{{ old('date_current_grade') }}"
                >

                <div class="md-body-sm">
                    This may be earlier than the hospital reporting date.
                </div>
            </div>

            <div class="md-field">
                <label>Retirement Age</label>

                <select
                    class="md-field__input"
                    name="retirement_age"
                >
                    <option value="">
                        — Not set —
                    </option>

                    @foreach ([55, 57, 60, 63, 65] as $age)
                        <option
                            value="{{ $age }}"
                            @selected(
                                old('retirement_age') == $age
                            )
                        >
                            {{ $age }} years
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </section>

    <section
        class="workforce-panel gw-step"
        data-step="7"
        style="display: none;"
    >
        <h2>✅ Review & save</h2>

        <p>
            After saving, Employee 360 will show the new record. You can then add
            Grade History, previous Service Periods, documents, qualifications
            and other records without reopening this long form.
        </p>

        <p class="md-body-sm">
            The system keeps public-service dates, Combined Service dates and
            the date reported to this hospital as separate facts.
        </p>
    </section>

    <div
        style="
            display: flex;
            justify-content: space-between;
            margin-top: 14px;
        "
    >
        <button
            type="button"
            id="gwPrev"
            class="md-btn md-btn--outlined"
        >
            ← Back
        </button>

        <button
            type="button"
            id="gwNext"
            class="md-btn md-btn--filled"
        >
            Continue →
        </button>

        <button
            id="gwSave"
            class="md-btn md-btn--filled"
            style="display: none;"
        >
            Create Employee Profile
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script>
    (() => {
        let step = 1;
        const form = document.getElementById('profileWizard');

        const steps = [
            ...document.querySelectorAll('.gw-step'),
        ];

        const chips = [
            ...document.querySelectorAll('.gw-chip'),
        ];

        const previousButton = document.getElementById('gwPrev');
        const nextButton = document.getElementById('gwNext');
        const saveButton = document.getElementById('gwSave');

        function draw() {
            steps.forEach((element) => {
                element.style.display =
                    Number(element.dataset.step) === step
                        ? 'block'
                        : 'none';
            });

            chips.forEach((element) => {
                element.classList.toggle(
                    'md-chip--selected',
                    Number(element.dataset.step) === step
                );
            });

            previousButton.style.visibility =
                step === 1
                    ? 'hidden'
                    : 'visible';

            nextButton.style.display =
                step === steps.length
                    ? 'none'
                    : 'inline-flex';

            saveButton.style.display =
                step === steps.length
                    ? 'inline-flex'
                    : 'none';
        }

        previousButton.addEventListener('click', () => {
            step = Math.max(1, step - 1);
            draw();
        });

        nextButton.addEventListener('click', () => {
            const current = steps.find(element => Number(element.dataset.step) === step);
            const invalid = [...current.querySelectorAll('input, select, textarea')]
                .find(field => !field.checkValidity());
            if (invalid) {
                invalid.reportValidity();
                return;
            }
            step = Math.min(steps.length, step + 1);
            draw();
        });

        chips.forEach((chip) => {
            chip.addEventListener('click', () => {
                step = Number(chip.dataset.step);
                draw();
            });
        });

        // Reveal hidden steps before the browser focuses an invalid field.
        form.addEventListener('invalid', event => {
            const section = event.target.closest('.gw-step');
            if (section) {
                step = Number(section.dataset.step);
                draw();
            }
        }, true);

        // Preserve values in the same form DOM; never store contact data in browser storage.
        form.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], textarea')
            .forEach(field => {
                field.addEventListener('input', () => {
                    field.value = field.value.replace(/^\\s+/, '');
                });
                field.addEventListener('blur', () => {
                    field.value = field.value.trim();
                });
            });

        // Return to the first field rejected by server-side validation.
        const errorFields = JSON.parse(form.dataset.errorFields || '[]');
        const firstError = [...form.elements].find(field => errorFields.includes(field.name));
        if (firstError && firstError.closest('.gw-step')) {
            step = Number(firstError.closest('.gw-step').dataset.step);
        }

        draw();
        if (firstError) {
            firstError.focus();
        }
    })();
</script>
@endpush
