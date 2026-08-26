@extends('layouts.app')
@section('title', 'New Service Letter')
@section('content')

<div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
    <a href="{{ route('service-letters.index') }}" class="md-btn md-btn--icon">&#8592;</a>
    <div>
        <h2 class="md-headline-sm">New Service Letter</h2>
        <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
            Draft a letter for one of your assigned employees. It will be saved as a draft —
            submit it for AO approval from the letter's detail page when you're ready.
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

@if($employees->isEmpty())
<div class="md-card md-card--elevated" style="padding:40px;text-align:center;color:var(--md-on-surface-variant);">
    No employees are available under your assigned subject codes.
</div>
@else

<form method="POST" action="{{ route('service-letters.store') }}" class="md-card md-card--elevated" id="slForm">
    @csrf

    <div class="md-card__body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">

            <div class="md-field">
                <label class="md-field__label">
                    Employee <span style="color:var(--md-error)">*</span>
                </label>
                <select name="employee_id" id="employee_select" class="md-field__input" required>
                    <option value="">— Select employee —</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>
                            {{ $emp->display_name }} ({{ $emp->pay_no ?? 'no pay no.' }}) — {{ $emp->position->title ?? '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="md-field">
                <label class="md-field__label">Template (optional)</label>
                <select name="template_id" id="template_select" class="md-field__input">
                    <option value="">— Write from scratch —</option>
                    @foreach($templates as $t)
                        <option value="{{ $t->id }}" data-language="{{ $t->language }}">
                            {{ $t->name }} ({{ \App\Models\ServiceLetterTemplate::LANGUAGES[$t->language] ?? $t->language }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="md-field" style="margin-bottom:14px;">
            <label class="md-field__label">
                Subject <span style="color:var(--md-error)">*</span>
            </label>
            <input type="text" name="subject" id="subject_input"
                   class="md-field__input"
                   value="{{ old('subject') }}"
                   placeholder="e.g. Service Confirmation Letter"
                   maxlength="200" required>
        </div>

        <div class="md-field">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                <label class="md-field__label" style="margin:0;">
                    Letter Body <span style="color:var(--md-error)">*</span>
                </label>
                <button type="button" id="generateBtn" class="md-btn md-btn--outlined" style="font-size:12px;" disabled>
                    ⚡ Generate from Template
                </button>
            </div>
            <textarea name="rendered_body" id="body_textarea" rows="14"
                      class="md-field__input" style="line-height:1.6;" required
                      placeholder="Select an employee and template, then click 'Generate from Template' — or write the letter directly here.">{{ old('rendered_body') }}</textarea>
            <div id="genStatus" class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:4px;min-height:16px;"></div>
        </div>
    </div>

    <div class="md-card__footer" style="display:flex;justify-content:flex-end;gap:10px;">
        <a href="{{ route('service-letters.index') }}" class="md-btn md-btn--outlined">Cancel</a>
        <button type="submit" class="md-btn md-btn--filled">💾 Save as Draft</button>
    </div>
</form>
@endif

@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const employeeSelect = document.getElementById('employee_select');
    const templateSelect = document.getElementById('template_select');
    const generateBtn    = document.getElementById('generateBtn');
    const bodyTextarea   = document.getElementById('body_textarea');
    const genStatus      = document.getElementById('genStatus');
    const subjectInput   = document.getElementById('subject_input');

    if (!employeeSelect) return; // no employees available — form not rendered

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    function updateGenerateButton() {
        const hasEmployee = !!employeeSelect.value;
        const hasTemplate = !!templateSelect.value;
        generateBtn.disabled = !(hasEmployee && hasTemplate);
    }

    employeeSelect.addEventListener('change', updateGenerateButton);
    templateSelect.addEventListener('change', function () {
        updateGenerateButton();
        // Suggest a subject line from the template name if the officer hasn't typed one yet.
        const opt = templateSelect.options[templateSelect.selectedIndex];
        if (opt && opt.value && !subjectInput.value) {
            subjectInput.value = opt.text.replace(/\s*\([^)]*\)\s*$/, '');
        }
    });

    generateBtn.addEventListener('click', function () {
        genStatus.textContent = 'Generating…';
        genStatus.style.color = 'var(--md-on-surface-variant)';

        fetch('{{ route('service-letters.preview') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                employee_id: employeeSelect.value,
                template_id: templateSelect.value,
            }),
        })
            .then(function (r) {
                if (!r.ok) throw new Error('Request failed');
                return r.json();
            })
            .then(function (data) {
                bodyTextarea.value = data.rendered_body;
                genStatus.textContent = '✓ Generated — review and edit before saving.';
                genStatus.style.color = 'var(--md-primary)';
            })
            .catch(function () {
                genStatus.textContent = 'Could not generate — please check the employee and template, or write the letter manually.';
                genStatus.style.color = 'var(--md-error)';
            });
    });
})();
</script>
@endpush
