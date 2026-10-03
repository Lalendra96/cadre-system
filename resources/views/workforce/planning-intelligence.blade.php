@extends('layouts.app')

@section('title', 'Hospital Planning Intelligence')

@section('content')
    <div class="page-header">
        <div>
            <div class="page-eyebrow">Planning Officer · hospital planning decision support</div>
            <h1 class="page-title">Hospital Planning Intelligence</h1>
            <p class="page-subtitle">
                Explore workforce capacity and resource scenarios before preparing a governed planning recommendation.
            </p>
        </div>
        <a class="md-btn md-btn--primary" href="{{ route('planning.assessments.create') }}">+ Start planning assessment</a>
    </div>

    @include('partials.governance-legal-safeguard', [
        'title' => 'Planning intelligence is advisory — not an institutional decision',
        'purpose' => 'Scenario outputs are calculated from available system records and user-entered assumptions. They support hospital planning discussion but do not authorise recruitment, transfer, expenditure, procurement, cadre amendment, service reconfiguration or any other consequential action.',
        'items' => [
            'Check the approved establishment, source period and data-quality status before relying on a result.',
            'Treat recruitment, salary and time-horizon values as planning assumptions unless supported by an approved authority.',
            'Compare more than one reasonable scenario and record material uncertainty.',
            'Document the selected planning recommendation in the Hospital Planning Decision Support worksheet.',
            'Final action must be decided by the competent authority through the applicable official workflow.',
        ],
    ])

    <section class="md-card" style="padding:20px;margin-bottom:16px">
        <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap">
            <div>
                <h2 class="md-title-md" style="margin:0">Scenario assumptions</h2>
                <p class="md-body-sm" style="margin:5px 0 0">
                    Change one or more assumptions to understand possible planning implications. No value entered here changes an employee or official establishment record.
                </p>
            </div>
            <span class="md-badge md-badge--info">What-if analysis only</span>
        </div>

        <form method="GET" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px;margin-top:16px;align-items:end">
            <label>
                <span class="md-body-sm">Approved cadre year</span>
                <input class="md-input" type="number" name="year" min="2000" max="2200" value="{{ request('year', now()->year) }}">
                <small>Year used for establishment comparison.</small>
            </label>
            <label>
                <span class="md-body-sm">Planning horizon · months</span>
                <input class="md-input" type="number" name="months" min="1" max="60" value="{{ request('months', 12) }}">
                <small>Future period used for retirement exposure.</small>
            </label>
            <label>
                <span class="md-body-sm">Illustrative recruitment</span>
                <input class="md-input" type="number" name="recruitment" min="0" max="100000" value="{{ request('recruitment', 0) }}">
                <small>Scenario assumption; not an approved recruitment number.</small>
            </label>
            <label>
                <span class="md-body-sm">Illustrative annual salary cost / recruit</span>
                <input class="md-input" type="number" name="salary_impact" min="0" max="100000000" step="0.01" value="{{ request('salary_impact', 0) }}">
                <small>Use a documented estimate and record its source in the planning worksheet.</small>
            </label>
            <button class="md-btn md-btn--primary" type="submit">Recalculate scenario</button>
        </form>
    </section>

    @php
        $primary = [
            'active_employees' => ['Active workforce', 'Current active employee records included in the planning base.'],
            'approved_establishment' => ['Approved establishment', 'Approved cadre total for the selected year.'],
            'current_establishment_gap' => ['Current calculated gap', 'Calculated difference between approved establishment and active workforce.'],
            'current_fill_rate_pct' => ['Current fill rate', 'Calculated workforce-to-establishment ratio.'],
            'projected_retirements' => ['Projected retirements', 'Employees reaching their recorded retirement date within the selected horizon.'],
            'projected_headcount' => ['Illustrative projected headcount', 'Current workforce less projected retirements plus the recruitment assumption.'],
            'projected_gap' => ['Illustrative projected gap', 'Calculated gap after applying the scenario assumptions.'],
            'estimated_annual_salary_impact' => ['Illustrative salary impact', 'Recruitment assumption multiplied by the entered annual salary estimate.'],
        ];
    @endphp

    <section class="role-kpi-grid" aria-label="Planning scenario indicators" style="margin-bottom:16px">
        @foreach ($primary as $key => [$label, $help])
            <article class="role-kpi">
                <div class="role-kpi__label">{{ $label }}</div>
                <div class="role-kpi__value">
                    @if ($key === 'current_fill_rate_pct')
                        {{ number_format((float) ($summary[$key] ?? 0), 1) }}%
                    @elseif ($key === 'estimated_annual_salary_impact')
                        LKR {{ number_format((float) ($summary[$key] ?? 0), 2) }}
                    @else
                        {{ number_format((float) ($summary[$key] ?? 0), 0) }}
                    @endif
                </div>
                <div class="role-kpi__meta">{{ $help }}</div>
            </article>
        @endforeach
    </section>

    <div class="role-dashboard-grid">
        <section class="role-panel">
            <div class="role-panel__header">
                <h2 class="role-panel__title">Planning readiness checks</h2>
            </div>
            <div class="role-panel__body role-status-list">
                @foreach ([
                    ['Open data-quality issues', $summary['open_data_quality_issues'] ?? 0, 'Verify affected source records before relying on the scenario.'],
                    ['Open reconciliation issues', $summary['open_reconciliation_issues'] ?? 0, 'Resolve unexplained establishment/headcount differences where relevant.'],
                    ['Open HR escalations', $summary['open_hr_escalations'] ?? 0, 'Check unresolved responsibility or workflow risks.'],
                    ['Open retirement projects', $summary['open_retirement_projects'] ?? 0, 'Review succession and replacement implications.'],
                    ['Recruitment vacancies recorded', $summary['recruitment_vacancies_open'] ?? 0, 'Compare recorded vacancies with scenario assumptions.'],
                    ['Official reports in progress', $summary['official_reports_in_progress'] ?? 0, 'Prefer approved/signed reports where formal evidence is required.'],
                ] as [$label, $value, $help])
                    <div class="role-status-row">
                        <div>
                            <strong>{{ $label }}</strong>
                            <small>{{ $help }}</small>
                        </div>
                        <div class="role-status-value">{{ number_format((float) $value, 0) }}</div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="role-panel">
            <div class="role-panel__header">
                <h2 class="role-panel__title">From analysis to a governed decision</h2>
            </div>
            <div class="role-panel__body">
                <ol style="margin:0;padding-left:20px;display:grid;gap:10px">
                    <li><strong>Define the planning question.</strong> State the hospital need and objective without assuming the answer.</li>
                    <li><strong>Verify evidence.</strong> Confirm approved cadre, workload, financial and service data and state the data date.</li>
                    <li><strong>Compare options.</strong> Use scenarios to explore alternatives, not to produce an automatic winner.</li>
                    <li><strong>Document impacts and risks.</strong> Consider workforce, cost, service continuity, quality/safety and equitable access.</li>
                    <li><strong>Record the planning recommendation.</strong> Use the governed assessment worksheet with assumptions and authority references.</li>
                    <li><strong>Refer to the competent authority.</strong> The authorised human workflow makes the actual institutional decision.</li>
                </ol>
                <div style="margin-top:16px;display:flex;gap:8px;flex-wrap:wrap">
                    <a class="md-btn md-btn--primary" href="{{ route('planning.assessments.create') }}">Start decision-support worksheet</a>
                    <a class="md-btn" href="{{ route('planning.assessments.index') }}">Open planning register</a>
                    <a class="md-btn" href="{{ route('workforce.scenario-comparison') }}">Compare scenarios</a>
                </div>
            </div>
        </section>
    </div>

    <div class="alert alert-info" style="margin-top:16px" role="note">
        <strong>Interpretation reminder:</strong> a lower projected gap or lower estimated cost does not by itself make an option appropriate.
        Hospital planning should also consider clinical/service requirements, patient access, quality and safety, workforce feasibility, approved funding,
        infrastructure, legal/administrative authority and implementation risk.
    </div>
@endsection
