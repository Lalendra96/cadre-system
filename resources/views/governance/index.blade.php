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
            Separate software behaviour from institutional authority, policy,
            administrative decisions and source documents.
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
        verify the applicable Acts, regulations, Establishments Code provisions,
        Public Administration circulars, Ministry instructions, service
        minutes, Public Service Commission decisions and other authoritative
        documents. Where a system output conflicts with an applicable official
        authority, <strong>the applicable official authority prevails</strong>.
    </p>

    <p class="md-body-sm">
        Disclaimer version: {{ $profile['disclaimer_version'] }}
    </p>
</div>

<div class="workforce-panel" style="margin-bottom: 16px;">
    <div class="panel-title">
        Institutional Responsibility Profile
    </div>

    @if ($isSuperAdmin)
        <form
            method="POST"
            action="{{ route('governance.profile.update') }}"
            style="margin-top: 14px;"
        >
            @csrf

            <div class="md-form-row">
                <div class="md-form-group">
                    <label class="md-label">
                        System Owner
                    </label>

                    <input
                        class="md-input"
                        name="system_owner"
                        required
                        value="{{ old('system_owner', $profile['system_owner']) }}"
                    >
                </div>

                <div class="md-form-group">
                    <label class="md-label">
                        Data Controller
                    </label>

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
                    <label class="md-label">
                        Technical Maintainer
                    </label>

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
                    <label class="md-label">
                        Notice Version
                    </label>

                    <input
                        class="md-input"
                        name="disclaimer_version"
                        required
                        value="{{ old('disclaimer_version', $profile['disclaimer_version']) }}"
                    >
                </div>
            </div>

            <p class="md-body-sm">
                Confirm these designations with the authorised institutional,
                legal and data-protection officers. Software configuration
                alone does not determine legal roles.
            </p>

            <button class="md-btn md-btn--filled">
                Save Governance Profile
            </button>
        </form>
    @else
        <div style="margin-top: 12px;">
            <p><strong>System Owner:</strong> {{ $profile['system_owner'] }}</p>
            <p><strong>Data Controller:</strong> {{ $profile['data_controller'] }}</p>
            <p><strong>Decision Authority:</strong> {{ $profile['decision_authority'] }}</p>
            <p><strong>Technical Maintainer:</strong> {{ $profile['technical_maintainer'] }}</p>
            <p><strong>Governance Contact:</strong> {{ $profile['governance_contact'] ?: 'Not recorded' }}</p>
        </div>
    @endif
</div>

<div class="workforce-panel">
    <div class="panel-title-row">
        <div>
            <div class="panel-title">
                📚 Business Rule Register
            </div>

            <div class="panel-subtitle">
                Record who approved a system rule and which authoritative source
                it is based on. A rule is not official merely because it exists
                in the application.
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
                    [
                        'rule' => null,
                    ]
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
                        </td>

                        <td>
                            {{ $rule->system_behavior }}
                        </td>

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
                                <details>
                                    <summary style="cursor: pointer;">
                                        Edit
                                    </summary>

                                    <form
                                        method="POST"
                                        action="{{ route('governance.rules.update', $rule) }}"
                                        style="margin-top: 10px; min-width: 560px;"
                                    >
                                        @csrf
                                        @method('PUT')

                                        @include(
                                            'governance.rule-fields',
                                            [
                                                'rule' => $rule,
                                            ]
                                        )

                                        <button class="md-btn md-btn--filled">
                                            Save Rule
                                        </button>
                                    </form>
                                </details>

                                <form
                                    method="POST"
                                    action="{{ route('governance.rules.toggle', $rule) }}"
                                    style="margin-top: 8px;"
                                >
                                    @csrf
                                    @method('PATCH')

                                    <button class="md-btn md-btn--text">
                                        {{ $rule->is_active ? 'Disable' : 'Enable' }}
                                    </button>
                                </form>
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
@endsection
