@extends('layouts.app')

@php
    $draft = $serviceLetter ?? null;
@endphp

@section(
    'title',
    $draft
        ? 'Edit Service Letter'
        : __('ui.new_service_letter')
)

@section('content')
<div
    style="
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: flex-start;
        margin-bottom: 16px;
        flex-wrap: wrap;
    "
>
    <div>
        <h2 class="md-headline-sm">
            ✉️ {{ $draft ? 'Edit Service Letter' : __('ui.new_service_letter') }}
        </h2>

        <p
            class="md-body-sm"
            style="color: var(--md-on-surface-variant);"
        >
            Guided workflow: employee → purpose/language → template →
            letterhead → review → approval.
        </p>
    </div>

    <a
        href="{{ route('service-letters.index') }}"
        class="md-btn md-btn--outlined"
    >
        ← {{ __('ui.service_letters') }}
    </a>
</div>

@if ($errors->any())
    <div
        class="md-card"
        style="
            padding: 12px;
            margin-bottom: 12px;
            border: 1px solid var(--md-error);
        "
    >
        <strong>Please correct the following:</strong>

        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if ($employees->isEmpty())
    <div class="workforce-panel">
        <h3>No allocated employees available</h3>

        <p>
            You can only create a service letter for an Employee Profile
            allocated to you.
        </p>
    </div>
@else
    <form
        method="POST"
        action="{{
            $draft
                ? route('service-letters.update', $draft)
                : route('service-letters.store')
        }}"
        id="letterForm"
    >
        @csrf

        @if ($draft)
            @method('PUT')
        @endif

        <div class="workforce-panel">
            <div class="panel-title-row">
                <div>
                    <div class="panel-title">
                        1. Employee & purpose
                    </div>

                    <div class="panel-subtitle">
                        Choose the employee first. Only records within your
                        authorised scope are shown.
                    </div>
                </div>
            </div>

            <div
                class="md-form-row"
                style="margin-top: 12px;"
            >
                <div class="md-form-group">
                    <label class="md-label">
                        {{ __('ui.employee') }} *
                    </label>

                    <select
                        class="md-select"
                        name="employee_id"
                        id="employee_select"
                        required
                    >
                        <option value="">
                            — Select employee —
                        </option>

                        @foreach ($employees as $employee)
                            <option
                                value="{{ $employee->id }}"
                                {{
                                    (string) old(
                                        'employee_id',
                                        $draft?->employee_id
                                            ?? $selectedEmployeeId
                                    ) === (string) $employee->id
                                        ? 'selected'
                                        : ''
                                }}
                            >
                                {{ $employee->display_name }}
                                ({{ $employee->pay_no ?: 'no pay no.' }})
                                — {{ $employee->position?->title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md-form-group">
                    <label class="md-label">
                        {{ __('ui.purpose') }} *
                    </label>

                    <select
                        class="md-select"
                        name="purpose"
                        id="purpose_select"
                        required
                    >
                        @foreach (
                            [
                                'service_confirmation' => 'Service Confirmation',
                                'experience' => 'Experience / Detailed Service',
                                'salary_employment' => 'Salary / Employment Verification',
                                'visa_embassy' => 'Embassy / Visa',
                                'foreign_employment' => 'Foreign Employment',
                                'higher_studies' => 'Higher Studies / University',
                                'scholarship' => 'Scholarship / Fellowship',
                                'professional_registration' => 'Professional Registration',
                                'bank' => 'Bank / Financial Institution',
                                'transfer_release' => 'Transfer / Release',
                                'retirement' => 'Retirement / Pension',
                                'other' => 'Other',
                            ]
                            as $value => $label
                        )
                            <option
                                value="{{ $value }}"
                                {{
                                    old(
                                        'purpose',
                                        $draft?->purpose
                                    ) === $value
                                        ? 'selected'
                                        : ''
                                }}
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md-form-group">
                    <label class="md-label">
                        {{ __('ui.language') }} *
                    </label>

                    <select
                        class="md-select"
                        name="language"
                        id="language_select"
                        required
                    >
                        <option
                            value="en"
                            {{
                                old(
                                    'language',
                                    $draft?->language
                                        ?? auth()->user()->locale
                                        ?? 'en'
                                ) === 'en'
                                    ? 'selected'
                                    : ''
                            }}
                        >
                            English
                        </option>

                        <option
                            value="si"
                            {{
                                old('language', $draft?->language) === 'si'
                                    ? 'selected'
                                    : ''
                            }}
                        >
                            සිංහල
                        </option>

                        <option
                            value="ta"
                            {{
                                old('language', $draft?->language) === 'ta'
                                    ? 'selected'
                                    : ''
                            }}
                        >
                            தமிழ்
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <div
            class="workforce-panel"
            style="margin-top: 16px;"
        >
            <div class="panel-title-row">
                <div>
                    <div class="panel-title">
                        2. Template & official header
                    </div>

                    <div class="panel-subtitle">
                        Templates are filtered by the selected letter language.
                    </div>
                </div>
            </div>

            <div
                class="md-form-row"
                style="margin-top: 12px;"
            >
                <div class="md-form-group">
                    <label class="md-label">
                        {{ __('ui.template') }}
                    </label>

                    <select
                        class="md-select"
                        name="template_id"
                        id="template_select"
                    >
                        <option value="">
                            — Write from scratch / AI-assisted —
                        </option>

                        @foreach ($templates as $template)
                            <option
                                value="{{ $template->id }}"
                                data-language="{{ $template->language }}"
                                {{
                                    old(
                                        'template_id',
                                        $draft?->template_id
                                    ) == $template->id
                                        ? 'selected'
                                        : ''
                                }}
                            >
                                {{ $template->name }}
                                (
                                {{
                                    \App\Models\ServiceLetterTemplate::LANGUAGES[$template->language]
                                        ?? $template->language
                                }}
                                )
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md-form-group">
                    <label class="md-label">
                        {{ __('ui.letterhead') }} *
                    </label>

                    <select
                        class="md-select"
                        name="letterhead_id"
                        required
                    >
                        @foreach ($letterheads as $letterhead)
                            <option
                                value="{{ $letterhead->id }}"
                                {{
                                    old(
                                        'letterhead_id',
                                        $draft?->letterhead_id
                                            ?? optional(
                                                $letterheads->firstWhere(
                                                    'is_default',
                                                    true
                                                )
                                            )->id
                                    ) == $letterhead->id
                                        ? 'selected'
                                        : ''
                                }}
                            >
                                {{ $letterhead->name }}
                                {{ $letterhead->is_default ? ' — Default' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md-form-group">
                    <label class="md-label">
                        Copy / marking
                    </label>

                    <select
                        class="md-select"
                        name="copy_type"
                    >
                        @foreach (
                            \App\Models\ServiceLetter::COPY_TYPES
                            as $value => $label
                        )
                            <option
                                value="{{ $value }}"
                                {{
                                    old(
                                        'copy_type',
                                        $draft?->copy_type ?? 'original'
                                    ) === $value
                                        ? 'selected'
                                        : ''
                                }}
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div
                style="
                    display: flex;
                    gap: 8px;
                    flex-wrap: wrap;
                "
            >
                <button
                    type="button"
                    id="generateBtn"
                    class="md-btn md-btn--outlined"
                >
                    ⚡ {{ __('ui.generate_template') }}
                </button>

                @if (
                    \App\Services\FeatureToggleService::enabled(
                        'ai_service_letter_assistant'
                    )
                )
                    <button
                        type="button"
                        id="aiBtn"
                        class="md-btn md-btn--tonal"
                    >
                        ✨ {{ __('ui.ai_assist') }}
                    </button>
                @endif

                <span
                    id="genStatus"
                    class="md-body-sm"
                    style="align-self: center;"
                ></span>
            </div>
        </div>

        <div
            class="workforce-panel"
            style="margin-top: 16px;"
        >
            <div class="panel-title-row">
                <div>
                    <div class="panel-title">
                        3. Recipient & reference
                    </div>

                    <div class="panel-subtitle">
                        Optional. Use exactly as shown on the official
                        correspondence/file.
                    </div>
                </div>
            </div>

            <div
                class="md-form-row"
                style="margin-top: 12px;"
            >
                <div class="md-form-group">
                    <label class="md-label">
                        {{ __('ui.recipient') }}
                    </label>

                    <input
                        class="md-input"
                        name="recipient_name"
                        value="{{ old('recipient_name', $draft?->recipient_name) }}"
                        placeholder="e.g. Visa Officer / Director"
                    >
                </div>

                <div class="md-form-group">
                    <label class="md-label">
                        Recipient address / institution
                    </label>

                    <input
                        class="md-input"
                        name="recipient_address"
                        value="{{
                            old(
                                'recipient_address',
                                $draft?->recipient_address
                            )
                        }}"
                    >
                </div>

                <div class="md-form-group">
                    <label class="md-label">
                        {{ __('ui.reference') }}
                    </label>

                    <input
                        class="md-input"
                        name="reference_no"
                        value="{{ old('reference_no', $draft?->reference_no) }}"
                        placeholder="Official file/reference number"
                    >
                </div>
            </div>
        </div>

        <div
            class="workforce-panel"
            style="margin-top: 16px;"
        >
            <div class="panel-title-row">
                <div>
                    <div class="panel-title">
                        4. Review the letter
                    </div>

                    <div class="panel-subtitle">
                        Generated or AI-assisted text is only a draft.
                        Verify every statement before approval.
                    </div>
                </div>
            </div>

            <div
                class="md-form-group"
                style="margin-top: 12px;"
            >
                <label class="md-label">
                    {{ __('ui.subject') }} *
                </label>

                <input
                    class="md-input"
                    name="subject"
                    id="subject_input"
                    maxlength="200"
                    required
                    value="{{ old('subject', $draft?->subject) }}"
                >
            </div>

            <div class="md-form-group">
                <label class="md-label">
                    {{ __('ui.letter_body') }} *
                </label>

                <textarea
                    class="md-input"
                    name="rendered_body"
                    id="body_textarea"
                    rows="18"
                    required
                >{{ old('rendered_body', $draft?->rendered_body) }}</textarea>

                <div class="md-field-hint">
                    Do not approve placeholders such as [EDIT: …] until they
                    are completed or deliberately removed.
                </div>
            </div>

            <div class="md-form-group">
                <label class="md-label">
                    Optional AI drafting instruction
                </label>

                <input
                    class="md-input"
                    id="ai_instructions"
                    maxlength="500"
                    placeholder="e.g. Address to the Australian High Commission; keep the wording concise"
                >
            </div>
        </div>

        <div
            style="
                display: flex;
                justify-content: flex-end;
                gap: 10px;
                margin-top: 16px;
            "
        >
            <a
                href="{{ route('service-letters.index') }}"
                class="md-btn md-btn--outlined"
            >
                Cancel
            </a>

            <button class="md-btn md-btn--filled">
                💾 {{ $draft ? 'Update Draft' : __('ui.save_draft') }}
            </button>
        </div>
    </form>
@endif
@endsection

@push('scripts')
<script>
    (() => {
        const employee = document.getElementById('employee_select');
        const template = document.getElementById('template_select');
        const language = document.getElementById('language_select');
        const body = document.getElementById('body_textarea');
        const subject = document.getElementById('subject_input');
        const status = document.getElementById('genStatus');
        const generateButton = document.getElementById('generateBtn');
        const aiButton = document.getElementById('aiBtn');
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');

        if (!employee) {
            return;
        }

        function filterTemplates() {
            const selectedLanguage = language.value;

            Array.from(template.options).forEach((option, index) => {
                if (index === 0) {
                    return;
                }

                option.hidden =
                    option.dataset.language !== selectedLanguage;

                if (option.hidden && option.selected) {
                    template.value = '';
                }
            });
        }

        language.addEventListener(
            'change',
            filterTemplates
        );

        filterTemplates();

        template.addEventListener('change', () => {
            const option =
                template.options[template.selectedIndex];

            if (
                option
                && option.value
                && !subject.value
            ) {
                subject.value = option.text.replace(
                    /\s*\([^)]*\)\s*$/,
                    ''
                );
            }
        });

        generateButton.addEventListener(
            'click',
            async () => {
                if (!employee.value || !template.value) {
                    status.textContent =
                        'Select an employee and matching template first.';
                    return;
                }

                status.textContent = 'Generating…';

                try {
                    const response = await fetch(
                        '{{ route('service-letters.preview') }}',
                        {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                employee_id: employee.value,
                                template_id: template.value,
                            }),
                        }
                    );

                    if (!response.ok) {
                        throw new Error(
                            'Template generation failed.'
                        );
                    }

                    const data = await response.json();

                    body.value = data.rendered_body;
                    status.textContent =
                        '✓ Template generated. Review all facts.';
                } catch (error) {
                    status.textContent =
                        'Could not generate the template.';
                }
            }
        );

        if (aiButton) {
            aiButton.addEventListener(
                'click',
                async () => {
                    if (!employee.value) {
                        status.textContent =
                            'Select an employee first.';
                        return;
                    }

                    status.textContent =
                        'Preparing privacy-preserving assisted draft…';

                    try {
                        const response = await fetch(
                            '{{ route('service-letters.ai-draft') }}',
                            {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({
                                    employee_id: employee.value,
                                    template_id:
                                        template.value || null,
                                    purpose:
                                        document.getElementById(
                                            'purpose_select'
                                        ).value,
                                    language: language.value,
                                    instructions:
                                        document.getElementById(
                                            'ai_instructions'
                                        ).value,
                                }),
                            }
                        );

                        if (!response.ok) {
                            throw new Error(
                                'AI drafting failed.'
                            );
                        }

                        const data = await response.json();

                        body.value = data.body;
                        status.textContent =
                            '✨ Draft prepared ('
                            + (
                                data.mode === 'lan_ai'
                                    ? 'local LAN AI'
                                    : 'offline assistant'
                            )
                            + '). Human review required.';
                    } catch (error) {
                        status.textContent =
                            'Assistant unavailable. You can continue using the template/manual workflow.';
                    }
                }
            );
        }
    })();
</script>
@endpush
