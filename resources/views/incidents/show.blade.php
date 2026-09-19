@extends('layouts.app')

@section('title', 'Incident ' . $incident->reference_no)

@section('content')
<div class="page-header">
    <div>
        <a href="{{ route('incidents.index') }}">
            ← Incident Register
        </a>

        <div class="page-eyebrow">
            {{ $incident->reference_no }}
        </div>

        <h1 class="page-title">
            {{ $incident->title }}
        </h1>

        <p class="page-subtitle">
            {{ $incident->category_label }}
            ·
            {{ $incident->severity_label }}
            ·
            {{ $incident->status_label }}
        </p>
    </div>
</div>

@if (session('success'))
    <div
        class="workforce-panel"
        style="
            border-left: 4px solid var(--md-success);
            margin-bottom: 16px;
        "
    >
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div
        class="workforce-panel"
        style="
            border-left: 4px solid var(--md-error);
            margin-bottom: 16px;
        "
    >
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div
    style="
        display: grid;
        grid-template-columns: minmax(0, 2fr) minmax(300px, 1fr);
        gap: 16px;
        align-items: start;
    "
>
    <div
        style="
            display: flex;
            flex-direction: column;
            gap: 16px;
        "
    >
        <div class="workforce-panel">
            <div class="panel-title">
                Reported Issue
            </div>

            <p style="white-space: pre-wrap;">
                {{ $incident->description }}
            </p>

            <div class="md-form-row">
                <div>
                    <strong>Reported By</strong>
                    <div>{{ $incident->reporter?->name }}</div>
                </div>

                <div>
                    <strong>Reported At</strong>
                    <div>
                        {{ $incident->reported_at?->format('d M Y H:i') }}
                    </div>
                </div>

                <div>
                    <strong>Detected At</strong>
                    <div>
                        {{ $incident->detected_at?->format('d M Y H:i') ?? 'Not recorded' }}
                    </div>
                </div>

                <div>
                    <strong>Affected Records</strong>
                    <div>
                        {{ $incident->affected_records_count ?? 'Not estimated' }}
                    </div>
                </div>

                <div>
                    <strong>Related Reference</strong>
                    <div>
                        {{ $incident->related_reference ?: 'Not recorded' }}
                    </div>
                </div>

                <div>
                    <strong>Affected Employee</strong>
                    <div>
                        {{ $incident->affectedEmployee?->display_name ?? 'Not linked' }}
                    </div>
                </div>
            </div>

            @if ($incident->impact_summary)
                <h4>Observed / Assessed Impact</h4>
                <p style="white-space: pre-wrap;">
                    {{ $incident->impact_summary }}
                </p>
            @endif

            @if ($incident->immediate_action)
                <h4>Immediate / Containment Action</h4>
                <p style="white-space: pre-wrap;">
                    {{ $incident->immediate_action }}
                </p>
            @endif
        </div>

        @if (
            $incident->root_cause
            || $incident->corrective_action
            || $incident->preventive_action
        )
            <div class="workforce-panel">
                <div class="panel-title">
                    Investigation & Corrective Action
                </div>

                @if ($incident->root_cause)
                    <h4>Root Cause</h4>
                    <p style="white-space: pre-wrap;">
                        {{ $incident->root_cause }}
                    </p>
                @endif

                @if ($incident->corrective_action)
                    <h4>Corrective Action</h4>
                    <p style="white-space: pre-wrap;">
                        {{ $incident->corrective_action }}
                    </p>
                @endif

                @if ($incident->preventive_action)
                    <h4>Preventive Action</h4>
                    <p style="white-space: pre-wrap;">
                        {{ $incident->preventive_action }}
                    </p>
                @endif
            </div>
        @endif

        <div class="workforce-panel">
            <div class="panel-title">
                📜 Immutable Incident / Correction History
            </div>

            <table
                class="md-table"
                style="margin-top: 12px;"
            >
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Event</th>
                        <th>Actor</th>
                        <th>Status</th>
                        <th>Details</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($incident->events as $event)
                        <tr>
                            <td>
                                {{ $event->created_at?->format('d M Y H:i') }}
                            </td>

                            <td>
                                {{
                                    ucwords(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $event->event_type
                                        )
                                    )
                                }}
                            </td>

                            <td>
                                {{ $event->actor?->name ?? 'System' }}
                            </td>

                            <td>
                                @if ($event->from_status || $event->to_status)
                                    {{ $event->from_status ?: '—' }}
                                    →
                                    {{ $event->to_status ?: '—' }}
                                @else
                                    —
                                @endif
                            </td>

                            <td>
                                @if ($event->comment)
                                    <div style="white-space: pre-wrap;">
                                        {{ $event->comment }}
                                    </div>
                                @endif

                                @if ($event->event_type === 'correction_recorded')
                                    <div class="md-body-sm">
                                        <strong>Reference:</strong>
                                        {{ data_get($event->metadata, 'correction_reference') }}
                                    </div>

                                    <div class="md-body-sm">
                                        <strong>Type:</strong>
                                        {{
                                            ucwords(
                                                str_replace(
                                                    '_',
                                                    ' ',
                                                    data_get(
                                                        $event->metadata,
                                                        'correction_type',
                                                        ''
                                                    )
                                                )
                                            )
                                        }}
                                    </div>

                                    <div class="md-body-sm">
                                        <strong>Before:</strong>
                                        {{ data_get($event->metadata, 'before_summary') }}
                                    </div>

                                    <div class="md-body-sm">
                                        <strong>After:</strong>
                                        {{ data_get($event->metadata, 'after_summary') }}
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                No history recorded.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div
        style="
            display: flex;
            flex-direction: column;
            gap: 14px;
        "
    >
        <div class="workforce-panel">
            <div class="panel-title">
                Current Management
            </div>

            <p>
                <strong>Status:</strong>
                {{ $incident->status_label }}
            </p>

            <p>
                <strong>Severity:</strong>
                {{ $incident->severity_label }}
            </p>

            <p>
                <strong>Assigned To:</strong>
                {{ $incident->assignee?->name ?? 'Unassigned' }}
            </p>

            <p>
                <strong>Target Resolution:</strong>
                {{ $incident->target_resolution_date?->format('d M Y') ?? 'Not set' }}
            </p>

            @if ($incident->resolved_at)
                <p>
                    <strong>Resolved:</strong>
                    {{ $incident->resolved_at->format('d M Y H:i') }}
                    by
                    {{ $incident->resolvedBy?->name }}
                </p>
            @endif

            @if ($incident->closed_at)
                <p>
                    <strong>Closed:</strong>
                    {{ $incident->closed_at->format('d M Y H:i') }}
                    by
                    {{ $incident->closedBy?->name }}
                </p>
            @endif
        </div>

        @if ($canManage)
            <details
                class="workforce-panel"
                open
            >
                <summary style="cursor: pointer; font-weight: 700;">
                    1. Triage / Assign
                </summary>

                <form
                    method="POST"
                    action="{{ route('incidents.triage', $incident) }}"
                    style="margin-top: 12px;"
                >
                    @csrf

                    <div class="md-form-group">
                        <label class="md-label">Category *</label>
                        <select
                            class="md-select"
                            name="category"
                            required
                        >
                            @foreach (\App\Models\IncidentReport::CATEGORIES as $value => $label)
                                <option
                                    value="{{ $value }}"
                                    @selected($incident->category === $value)
                                >
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md-form-group">
                        <label class="md-label">Severity *</label>
                        <select
                            class="md-select"
                            name="severity"
                            required
                        >
                            @foreach (\App\Models\IncidentReport::SEVERITIES as $value => $label)
                                <option
                                    value="{{ $value }}"
                                    @selected($incident->severity === $value)
                                >
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md-form-group">
                        <label class="md-label">Assign To</label>
                        <select
                            class="md-select"
                            name="assigned_to"
                        >
                            <option value="">— Unassigned —</option>

                            @foreach ($managers as $manager)
                                <option
                                    value="{{ $manager->id }}"
                                    @selected($incident->assigned_to === $manager->id)
                                >
                                    {{ $manager->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md-form-group">
                        <label class="md-label">
                            Target Resolution Date
                        </label>

                        <input
                            class="md-input"
                            type="date"
                            name="target_resolution_date"
                            value="{{ $incident->target_resolution_date?->format('Y-m-d') }}"
                        >
                    </div>

                    <div class="md-form-group">
                        <label class="md-label">Impact Summary</label>

                        <textarea
                            class="md-input"
                            name="impact_summary"
                            rows="3"
                        >{{ $incident->impact_summary }}</textarea>
                    </div>

                    <div class="md-form-group">
                        <label class="md-label">
                            Immediate / Containment Action
                        </label>

                        <textarea
                            class="md-input"
                            name="immediate_action"
                            rows="3"
                        >{{ $incident->immediate_action }}</textarea>
                    </div>

                    <div class="md-form-group">
                        <label class="md-label">Triage Note *</label>

                        <textarea
                            class="md-input"
                            name="triage_note"
                            rows="3"
                            minlength="10"
                            required
                        ></textarea>
                    </div>

                    <button class="md-btn md-btn--filled">
                        Save Triage
                    </button>
                </form>
            </details>

            <details class="workforce-panel">
                <summary style="cursor: pointer; font-weight: 700;">
                    2. Investigation & Action Plan
                </summary>

                <form
                    method="POST"
                    action="{{ route('incidents.investigation', $incident) }}"
                    style="margin-top: 12px;"
                >
                    @csrf

                    <div class="md-form-group">
                        <label class="md-label">Root Cause *</label>

                        <textarea
                            class="md-input"
                            name="root_cause"
                            rows="4"
                            minlength="10"
                            required
                        >{{ $incident->root_cause }}</textarea>
                    </div>

                    <div class="md-form-group">
                        <label class="md-label">
                            Corrective Action *
                        </label>

                        <textarea
                            class="md-input"
                            name="corrective_action"
                            rows="4"
                            minlength="10"
                            required
                        >{{ $incident->corrective_action }}</textarea>
                    </div>

                    <div class="md-form-group">
                        <label class="md-label">
                            Preventive Action
                        </label>

                        <textarea
                            class="md-input"
                            name="preventive_action"
                            rows="4"
                        >{{ $incident->preventive_action }}</textarea>
                    </div>

                    <div class="md-form-group">
                        <label class="md-label">
                            Investigation Note *
                        </label>

                        <textarea
                            class="md-input"
                            name="investigation_note"
                            rows="3"
                            minlength="10"
                            required
                        ></textarea>
                    </div>

                    <button class="md-btn md-btn--filled">
                        Record Investigation
                    </button>
                </form>
            </details>

            <details class="workforce-panel">
                <summary style="cursor: pointer; font-weight: 700;">
                    3. Record Correction / Verification
                </summary>

                <form
                    method="POST"
                    action="{{ route('incidents.corrections.store', $incident) }}"
                    style="margin-top: 12px;"
                >
                    @csrf

                    <div class="md-form-group">
                        <label class="md-label">
                            Correction Reference *
                        </label>

                        <input
                            class="md-input"
                            name="correction_reference"
                            maxlength="180"
                            required
                            placeholder="Commit, ticket, audit ref., file ref., batch ID, etc."
                        >
                    </div>

                    <div class="md-form-group">
                        <label class="md-label">
                            Correction Type *
                        </label>

                        <select
                            class="md-select"
                            name="correction_type"
                            required
                        >
                            <option value="data_correction">Data Correction</option>
                            <option value="configuration_change">Configuration Change</option>
                            <option value="code_fix">Code Fix</option>
                            <option value="report_regeneration">Report Regeneration</option>
                            <option value="access_correction">Access Correction</option>
                            <option value="process_correction">Process Correction</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="md-form-group">
                        <label class="md-label">
                            Before Correction *
                        </label>

                        <textarea
                            class="md-input"
                            name="before_summary"
                            rows="3"
                            required
                        ></textarea>
                    </div>

                    <div class="md-form-group">
                        <label class="md-label">
                            After Correction *
                        </label>

                        <textarea
                            class="md-input"
                            name="after_summary"
                            rows="3"
                            required
                        ></textarea>
                    </div>

                    <div class="md-form-group">
                        <label class="md-label">
                            Verification Note *
                        </label>

                        <textarea
                            class="md-input"
                            name="verification_note"
                            rows="3"
                            minlength="10"
                            required
                            placeholder="How was the correction checked?"
                        ></textarea>
                    </div>

                    <button class="md-btn md-btn--filled">
                        Add Correction History
                    </button>
                </form>
            </details>

            @if ($incident->status !== 'closed')
                <details class="workforce-panel">
                    <summary style="cursor: pointer; font-weight: 700;">
                        4. Resolve
                    </summary>

                    <form
                        method="POST"
                        action="{{ route('incidents.resolve', $incident) }}"
                        style="margin-top: 12px;"
                    >
                        @csrf

                        <div class="md-form-group">
                            <label class="md-label">
                                Resolution Note *
                            </label>

                            <textarea
                                class="md-input"
                                name="resolution_note"
                                rows="3"
                                minlength="20"
                                required
                            ></textarea>
                        </div>

                        <label
                            style="
                                display: flex;
                                gap: 8px;
                                align-items: flex-start;
                                margin-bottom: 12px;
                            "
                        >
                            <input
                                type="checkbox"
                                name="confirm_correction_verified"
                                value="1"
                                required
                            >

                            <span>
                                I confirm that the correction has been verified
                                against the relevant system/data/source records.
                            </span>
                        </label>

                        <button class="md-btn md-btn--filled">
                            Mark Resolved
                        </button>
                    </form>
                </details>
            @endif

            @if ($incident->status === 'resolved')
                <details class="workforce-panel">
                    <summary style="cursor: pointer; font-weight: 700;">
                        5. Closure Review
                    </summary>

                    <form
                        method="POST"
                        action="{{ route('incidents.close', $incident) }}"
                        style="margin-top: 12px;"
                    >
                        @csrf

                        <div class="md-form-group">
                            <label class="md-label">
                                Closure Notes *
                            </label>

                            <textarea
                                class="md-input"
                                name="closure_notes"
                                rows="3"
                                minlength="20"
                                required
                            ></textarea>
                        </div>

                        <label
                            style="
                                display: flex;
                                gap: 8px;
                                align-items: flex-start;
                                margin-bottom: 12px;
                            "
                        >
                            <input
                                type="checkbox"
                                name="confirm_preventive_review"
                                value="1"
                                required
                            >

                            <span>
                                I confirm the corrective/preventive actions and
                                incident history were reviewed before closure.
                            </span>
                        </label>

                        <button class="md-btn md-btn--filled">
                            Close Incident
                        </button>
                    </form>
                </details>
            @endif

            @if (
                in_array(
                    $incident->status,
                    ['resolved', 'closed'],
                    true
                )
            )
                <details class="workforce-panel">
                    <summary style="cursor: pointer; font-weight: 700;">
                        Reopen Incident
                    </summary>

                    <form
                        method="POST"
                        action="{{ route('incidents.reopen', $incident) }}"
                        style="margin-top: 12px;"
                    >
                        @csrf

                        <div class="md-form-group">
                            <label class="md-label">
                                Reason for Reopening *
                            </label>

                            <textarea
                                class="md-input"
                                name="reopen_reason"
                                rows="3"
                                minlength="15"
                                required
                            ></textarea>
                        </div>

                        <button class="md-btn md-btn--outlined">
                            Reopen for Investigation
                        </button>
                    </form>
                </details>
            @endif
        @endif
    </div>
</div>
@endsection
