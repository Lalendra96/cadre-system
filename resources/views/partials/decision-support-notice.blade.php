@php
    $showDecisionSupportNotice = request()->routeIs(
        'reports.*',
        'planning.*',
        'workforce.*',
        'data-quality.*',
        'retirement-projects.*',
        'hr-intelligence.*',
        'administrative-intelligence.*',
        'planning-intelligence.*',
        'increments.*',
        'employee-grades.*'
    );
@endphp

@if ($showDecisionSupportNotice)
    <aside
        style="
            margin-bottom: 16px;
            padding: 12px 14px;
            border-left: 4px solid var(--md-primary);
            border-radius: var(--md-shape-sm);
            background: color-mix(
                in srgb,
                var(--md-primary) 8%,
                var(--md-surface-container)
            );
        "
        aria-label="Internal decision-support notice"
    >
        <div
            class="md-label-md"
            style="margin-bottom: 4px;"
        >
            ⚖ For Internal Administrative Decision Support Only
        </div>

        <div
            class="md-body-sm"
            style="color: var(--md-on-surface-variant);"
        >
            System-generated calculations, forecasts, alerts, recommendations,
            AI-assisted content and reports assist administrative review only.
            They do not constitute or replace Government of Sri Lanka policy,
            a circular, regulation, Establishments Code provision, legal
            interpretation, service minute, PSC decision or other official
            authority. The responsible officer must verify the applicable
            authoritative source before administrative action. Where there is
            any inconsistency, the applicable official authority prevails.

            <a
                href="{{ route('governance.index') }}"
                style="
                    margin-left: 6px;
                    color: var(--md-primary);
                    font-weight: 600;
                "
            >
                Governance & source register →
            </a>

            <a
                href="{{ route('incidents.create') }}"
                style="
                    margin-left: 12px;
                    color: var(--md-error);
                    font-weight: 600;
                "
            >
                Report incorrect output / data issue →
            </a>
        </div>
    </aside>
@endif
