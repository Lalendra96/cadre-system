@extends('layouts.app')

@section('title', 'Report Incident / Error')

@section('content')
<div class="page-header">
    <div>
        <a href="{{ route('incidents.index') }}">
            ← Incident Register
        </a>

        <h1 class="page-title">
            ⚠ Report Incident / Error
        </h1>

        <p class="page-subtitle">
            Record what you observed. Do not guess the root cause. Investigation
            and correction details are recorded separately by authorised managers.
        </p>
    </div>
</div>

@if ($errors->any())
    <div
        class="workforce-panel"
        style="
            border-left: 4px solid var(--md-error);
            margin-bottom: 16px;
        "
    >
        <strong>We could not save the incident yet.</strong>

        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form
    method="POST"
    action="{{ route('incidents.store') }}"
    class="workforce-panel"
>
    @csrf

    <div class="md-form-row">
        <div class="md-form-group">
            <label class="md-label">
                Short Title *
            </label>

            <input
                class="md-input"
                name="title"
                maxlength="200"
                required
                value="{{ old('title') }}"
                placeholder="e.g. Retirement projection shows incorrect date"
            >
        </div>

        <div class="md-form-group">
            <label class="md-label">
                Category *
            </label>

            <select
                class="md-select"
                name="category"
                required
            >
                @foreach (\App\Models\IncidentReport::CATEGORIES as $value => $label)
                    <option
                        value="{{ $value }}"
                        @selected(old('category') === $value)
                    >
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="md-form-group">
            <label class="md-label">
                Observed Severity *
            </label>

            <select
                class="md-select"
                name="severity"
                required
            >
                @foreach (\App\Models\IncidentReport::SEVERITIES as $value => $label)
                    <option
                        value="{{ $value }}"
                        @selected(
                            old('severity', 'medium') === $value
                        )
                    >
                        {{ $label }}
                    </option>
                @endforeach
            </select>

            <div class="md-field-hint">
                The incident manager may reclassify severity during triage.
            </div>
        </div>
    </div>

    <div class="md-form-group">
        <label class="md-label">
            What happened? *
        </label>

        <textarea
            class="md-input"
            name="description"
            rows="6"
            minlength="20"
            maxlength="5000"
            required
            placeholder="Describe what you expected, what actually happened, and how you noticed it."
        >{{ old('description') }}</textarea>
    </div>

    <div class="md-form-row">
        <div class="md-form-group">
            <label class="md-label">
                When was it detected?
            </label>

            <input
                class="md-input"
                type="datetime-local"
                name="detected_at"
                value="{{ old('detected_at') }}"
            >
        </div>

        <div class="md-form-group">
            <label class="md-label">
                Approx. affected records
            </label>

            <input
                class="md-input"
                type="number"
                min="0"
                name="affected_records_count"
                value="{{ old('affected_records_count') }}"
            >
        </div>

        <div class="md-form-group">
            <label class="md-label">
                Related employee (optional)
            </label>

            <select
                class="md-select"
                name="affected_employee_id"
            >
                <option value="">
                    — No employee / not applicable —
                </option>

                @foreach ($employees as $employee)
                    <option
                        value="{{ $employee->id }}"
                        @selected(
                            old('affected_employee_id') == $employee->id
                        )
                    >
                        {{ $employee->name }}
                        {{ $employee->pay_no ? '· ' . $employee->pay_no : '' }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="md-form-group">
        <label class="md-label">
            Related report / screen / reference
        </label>

        <input
            class="md-input"
            name="related_reference"
            maxlength="180"
            value="{{ old('related_reference') }}"
            placeholder="Report name, URL path, batch reference, employee file reference, etc."
        >
    </div>

    <div class="md-form-group">
        <label class="md-label">
            Observed impact
        </label>

        <textarea
            class="md-input"
            name="impact_summary"
            rows="3"
            maxlength="3000"
            placeholder="What could be affected? Do not make a legal conclusion."
        >{{ old('impact_summary') }}</textarea>
    </div>

    <div class="md-form-group">
        <label class="md-label">
            Immediate action already taken
        </label>

        <textarea
            class="md-input"
            name="immediate_action"
            rows="3"
            maxlength="3000"
            placeholder="e.g. stopped using the report, informed AO, corrected a source record, no action yet"
        >{{ old('immediate_action') }}</textarea>
    </div>

    <div
        style="
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        "
    >
        <a
            class="md-btn md-btn--outlined"
            href="{{ route('incidents.index') }}"
        >
            Cancel
        </a>

        <button class="md-btn md-btn--filled">
            Submit Incident for Triage
        </button>
    </div>
</form>
@endsection
