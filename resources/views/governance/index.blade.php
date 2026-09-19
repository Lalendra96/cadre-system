@extends('layouts.app')

@section('title', 'Governance & Official Authority')

@section('content')
@php
    $isSuperAdmin = auth()->user()->isSuperAdmin();
@endphp

<div class="page-header">
    <div>
        <div class="page-eyebrow">
            Governance
        </div>

        <h1 class="page-title">
            ⚖ Governance & Official Authority
        </h1>

        <p class="page-subtitle">
            Separate software behaviour, technical maintenance and system-generated
            indicators from institutional authority and human administrative decisions.
        </p>
    </div>
</div>

<div
    class="workforce-panel"
    style="
        border-left: 4px solid var(--md-primary);
        margin-bottom: 16px;
    "
>
    <h3 style="margin-top: 0;">
        Internal Administrative and Decision-Support Notice
    </h3>

    <p>
        Carder Management is intended solely to support internal workforce
        administration, record management, planning, monitoring and
        administrative decision support.
    </p>

    <p>
        Information, calculations, forecasts, alerts, recommendations,
        AI-assisted content, workflow configurations and reports produced by
        the system do <strong>not</strong> constitute Government of Sri Lanka
        policy, a government circular, regulation, Establishments Code
        provision, legal interpretation, service minute, Public Service
        Commission decision or other official authority.
    </p>

    <p>
        Before taking administrative action, the responsible officer must
        verify the applicable official authority and underlying source record.
        Where system output conflicts with an applicable official authority,
        <strong>the applicable official authority prevails</strong>.
    </p>

    <p class="md-body-sm">
        Decision-support notice version:
        {{ $profile['disclaimer_version'] }}
    </p>
</div>

<div
    class="workforce-panel"
    style="margin-bottom: 16px;"
>
    <div class="panel-title">
        Institutional Responsibility Profile
    </div>

    <div
        class="md-body-sm"
        style="margin-top: 6px;"
    >
        This profile records the institution's own governance designation.
        It does not by itself create or determine a legal controller/processor
        relationship.
    </div>

    @if ($profile['confirmed_by'])
        <div
            style="
                margin-top: 12px;
                padding: 10px 12px;
                border-radius: var(--md-shape-sm);
                background: var(--md-surface-container-high);
            "
        >
            <strong>Institutional confirmation recorded</strong>

            <div class="md-body-sm">
                {{ $profile['confirmed_by'] }}
                ·
                {{ $profile['confirmed_designation'] ?: 'Designation not recorded' }}
                ·
                {{ $profile['confirmed_at'] ?: 'Date not recorded' }}

                @if ($profile['confirmation_reference'])
                    · Ref: {{ $profile['confirmation_reference'] }}
                @endif
            </div>
        </div>
    @else
        <div
            style="
                margin-top: 12px;
                padding: 10px 12px;
                border-left: 4px solid var(--md-warning);
                background: var(--md-surface-container-high);
            "
        >
            <strong>Institutional confirmation pending</strong>

            <div class="md-body-sm">
                The system owner/data-controller/decision-authority fields must
                be confirmed by the authorised institution before being relied on
                as the institution's governance record.
            </div>
        </div>
    @endif

    @if ($isSuperAdmin)
        <form
            method="POST"
            action="{{ route('governance.profile.update') }}"
            style="margin-top: 14px;"
        >
            @csrf

            <div class="md-form-row">
                <div class="md-form-group">
                    <label class="md-label">System Owner</label>
                    <input
                        class="md-input"
                        name="system_owner"
                        required
                        value="{{ old('system_owner', $profile['system_owner']) }}"
                    >
                </div>

                <div class="md-form-group">
                    <label class="md-label">Data Controller</label>
                    <input
                        class="md-input"
                        name="data_controller"
                        required
                        value="{{ old('data_controller', $profile['data_controller']) }}"
                    >
                </div>

                <div class="md-form-group">
                    <label class="md-label">
                        Administrative Decision Authority
                    </label>
                    <input
                        class="md-input"
                        name="decision_authority"
                        required
                        value="{{ old('decision_authority', $profile['decision_authority']) }}"
                    >
                </div>

                <div class="md-form-group">
                    <label class="md-label">Technical Maintainer</label>
                    <input
                        class="md-input"
                        name="technical_maintainer"
                        required
                        value="{{ old('technical_maintainer', $profile['technical_maintainer']) }}"
                    >
                </div>

                <div class="md-form-group">
                    <label class="md-label">
                        Governance / DPO / Legal Contact
                    </label>
                    <input
                        class="md-input"
                        name="governance_contact"
                        value="{{ old('governance_contact', $profile['governance_contact']) }}"
                    >
                </div>

                <div class="md-form-group">
                    <label class="md-label">Notice Version</label>
                    <input
                        class="md-input"
                        name="disclaimer_version"
                        required
                        value="{{ old('disclaimer_version', $profile['disclaimer_version']) }}"
                    >
                </div>

                <div class="md-form-group">
                    <label class="md-label">
                        Institutionally Confirmed By *
                    </label>
                    <input
                        class="md-input"
                        name="confirmed_by"
                        required
                        value="{{ old('confirmed_by', $profile['confirmed_by']) }}"
                    >
                </div>

                <div class="md-form-group">
                    <label class="md-label">
                        Confirmer Designation *
                    </label>
                    <input
                        class="md-input"
                        name="confirmed_designation"
                        required
                        value="{{ old('confirmed_designation', $profile['confirmed_designation']) }}"
                    >
                </div>

                <div class="md-form-group">
                    <label class="md-label">
                        Confirmation / Minute / File Reference
                    </label>
                    <input
                        class="md-input"
                        name="confirmation_reference"
                        value="{{ old('confirmation_reference', $profile['confirmation_reference']) }}"
                    >
                </div>
            </div>

            <label
                style="
                    display: flex;
                    gap: 8px;
                    align-items: flex-start;
                    margin: 12px 0;
                "
            >
                <input
                    type="checkbox"
                    name="confirm_institutional_review"
                    value="1"
                    required
                >

                <span>
                    I confirm that these responsibility designations were reviewed
                    with the authorised institution and are being recorded as the
                    institution's governance configuration. This application entry
                    does not itself determine legal status.
                </span>
            </label>

            <button class="md-btn md-btn--filled">
                Save Confirmed Governance Profile
            </button>
        </form>
    @else
        <div style="margin-top: 12px;">
            <p>
                <strong>System Owner:</strong>
                {{ $profile['system_owner'] }}
            </p>

            <p>
                <strong>Data Controller:</strong>
                {{ $profile['data_controller'] }}
            </p>

            <p>
                <strong>Decision Authority:</strong>
                {{ $profile['decision_authority'] }}
            </p>

            <p>
                <strong>Technical Maintainer:</strong>
                {{ $profile['technical_maintainer'] }}
            </p>

            <p>
                <strong>Governance Contact:</strong>
                {{ $profile['governance_contact'] ?: 'Not recorded' }}
            </p>
        </div>
    @endif
</div>

<div
    class="workforce-panel"
    style="margin-bottom: 16px;"
>
    <div class="panel-title-row">
        <div>
            <div class="panel-title">
                📚 Business Rule Register
            </div>

            <div class="panel-subtitle">
                Record the configured system behaviour, official/source reference,
                institutional approval and version history. A rule is not official
                merely because it exists in the application.
            </div>
        </div>
    </div>

    @if ($isSuperAdmin)
        <details
            style="
                margin: 14px 0 18px;
                padding: 12px;
                border: 1px solid var(--md-outline-variant);
                border-radius: var(--md-shape-sm);
            "
        >
            <summary style="cursor: pointer; font-weight: 600;">
                + Add Business Rule
            </summary>

            <form
                method="POST"
                action="{{ route('governance.rules.store') }}"
                style="margin-top: 14px;"
            >
                @csrf

                @include(
                    'governance.rule-fields',
                    ['rule' => null]
                )

                <button class="md-btn md-btn--filled">
                    Add to Register
                </button>
            </form>
        </details>
    @endif

    <div style="overflow-x: auto;">
        <table class="md-table">
            <thead>
                <tr>
                    <th>Rule</th>
                    <th>System Behaviour</th>
                    <th>Authority / Source</th>
                    <th>Approval</th>
                    <th>Status</th>

                    @if ($isSuperAdmin)
                        <th>Actions</th>
                    @endif
                </tr>
            </thead>

            <tbody>
                @forelse ($rules as $rule)
                    <tr>
                        <td>
                            <strong>{{ $rule->name }}</strong>
                            <div class="md-body-sm">{{ $rule->code }}</div>
                            <div class="md-body-sm">
                                Versions: {{ $rule->versions->count() }}
                            </div>
                        </td>

                        <td>{{ $rule->system_behavior }}</td>

                        <td>
                            <strong>
                                {{
                                    \App\Models\BusinessRule::AUTHORITY_TYPES[$rule->authority_type]
                                        ?? $rule->authority_type
                                }}
                            </strong>

                            <div class="md-body-sm">
                                {{ $rule->authority_reference ?: 'Reference not recorded' }}
                            </div>

                            @if ($rule->authority_title)
                                <div class="md-body-sm">
                                    {{ $rule->authority_title }}
                                </div>
                            @endif

                            @if ($rule->authority_url)
                                <a
                                    href="{{ $rule->authority_url }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    Open recorded source ↗
                                </a>
                            @endif
                        </td>

                        <td>
                            {{ $rule->approved_by_name ?: 'Not recorded' }}

                            @if ($rule->approved_by_designation)
                                <div class="md-body-sm">
                                    {{ $rule->approved_by_designation }}
                                </div>
                            @endif

                            @if ($rule->approval_reference)
                                <div class="md-body-sm">
                                    Ref: {{ $rule->approval_reference }}
                                </div>
                            @endif
                        </td>

                        <td>
                            {{
                                \App\Models\BusinessRule::STATUSES[$rule->status]
                                    ?? $rule->status
                            }}

                            @if (! $rule->is_active)
                                <div class="md-body-sm">
                                    Disabled
                                </div>
                            @endif
                        </td>

                        @if ($isSuperAdmin)
                            <td>
                                <a
                                    class="md-btn md-btn--text"
                                    href="{{ route('governance.rules.history', $rule) }}"
                                >
                                    History
                                </a>

                                <details>
                                    <summary style="cursor: pointer;">
                                        Edit
                                    </summary>

                                    <form
                                        method="POST"
                                        action="{{ route('governance.rules.update', $rule) }}"
                                        style="
                                            margin-top: 10px;
                                            min-width: 560px;
                                        "
                                    >
                                        @csrf
                                        @method('PUT')

                                        @include(
                                            'governance.rule-fields',
                                            ['rule' => $rule]
                                        )

                                        <button class="md-btn md-btn--filled">
                                            Save New Version
                                        </button>
                                    </form>
                                </details>

                                <details style="margin-top: 8px;">
                                    <summary style="cursor: pointer;">
                                        {{ $rule->is_active ? 'Disable' : 'Enable' }}
                                    </summary>

                                    <form
                                        method="POST"
                                        action="{{ route('governance.rules.toggle', $rule) }}"
                                        style="margin-top: 8px;"
                                    >
                                        @csrf
                                        @method('PATCH')

                                        <div class="md-form-group">
                                            <label class="md-label">
                                                Reason for status change *
                                            </label>

                                            <textarea
                                                class="md-input"
                                                name="change_reason"
                                                rows="2"
                                                minlength="10"
                                                maxlength="1000"
                                                required
                                            ></textarea>
                                        </div>

                                        <button class="md-btn md-btn--text">
                                            Confirm
                                            {{ $rule->is_active ? 'Disable' : 'Enable' }}
                                        </button>
                                    </form>
                                </details>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td
                            colspan="{{ $isSuperAdmin ? 6 : 5 }}"
                            class="md-table__empty"
                        >
                            No business rules have been registered yet.
                            Treat configured calculations as unverified until
                            the responsible institution records the applicable
                            source and approval.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($isSuperAdmin)
    <div class="workforce-panel">
        <div class="panel-title">
            🧾 Configuration Change History
        </div>

        <div class="panel-subtitle">
            Central audit of SystemSetting changes, including governance,
            feature-toggle and other administrative configuration changes.
            Secret-like setting values are redacted.
        </div>

        <div style="overflow-x: auto; margin-top: 12px;">
            <table class="md-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Setting</th>
                        <th>Changed By</th>
                        <th>Previous</th>
                        <th>New</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($configurationChanges as $change)
                        <tr>
                            <td>{{ $change->changed_at?->format('d M Y H:i') }}</td>
                            <td>{{ $change->setting_key }}</td>
                            <td>{{ $change->changedBy?->name ?? 'System / migration' }}</td>
                            <td>{{ $change->old_value ?? '—' }}</td>
                            <td>{{ $change->new_value ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                No configuration changes recorded after the
                                governance audit upgrade.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
