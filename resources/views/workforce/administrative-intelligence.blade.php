@extends('layouts.app')
@section('title', 'Administrative Intelligence')
@section('content')
    <div class="page-shell">
        <div class="page-header">
            <div>
                <div class="page-eyebrow">Administrative workforce & governance intelligence</div>
                <h1 class="page-title">Administrative Decision Intelligence</h1>
                <p class="page-subtitle">Aggregate workforce capacity, operational risk, governance workload and financial-control signals for authorised administrative decision making. Employee identities are intentionally excluded.</p>
            </div>
        </div>

        @include('partials.governance-legal-safeguard', [
            'title' => 'Interpretation safeguard — intelligence supports, humans decide',
            'purpose' => 'Scenario outputs and risk counts are planning aids. They must not be used as the sole basis for appointment, transfer, promotion, discipline, retirement processing, payment approval or another consequential administrative decision.',
            'items' => [
                'Validate the underlying establishment, employee, financial and governance records before acting.',
                'Treat projections as assumptions-based scenarios, not promises or forecasts of an individual outcome.',
                'Document the authoritative rule/circular/minute and human rationale in the governed workflow.',
                'Do not use aggregate risk indicators to profile an employee or infer conduct.',
                'Use only the minimum personal information required when moving from aggregate intelligence to a case record.',
            ],
        ])

        <section class="md-card" style="padding:16px;margin-bottom:16px">
            <form method="GET" action="{{ route('administrative-intelligence.index') }}" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;align-items:end">
                <label class="md-field"><span>Establishment year</span><input type="number" name="year" min="2000" max="2200" value="{{ $year }}"></label>
                <label class="md-field"><span>Projection months</span><input type="number" name="months" min="1" max="60" value="{{ $months }}"></label>
                <label class="md-field"><span>Recruitment scenario</span><input type="number" name="recruitment" min="0" max="100000" value="{{ $recruitment }}"></label>
                <label class="md-field"><span>Annual salary / recruit (LKR)</span><input type="number" name="salary_impact" min="0" step="0.01" value="{{ $salaryImpact }}"></label>
                <button class="md-btn md-btn--filled" type="submit">Recalculate Scenario</button>
            </form>
        </section>

        <section style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:16px">
            @php
                $headline = [
                    ['Approved establishment', $summary['approved_establishment'], 'Authorised posts'],
                    ['Active workforce', $summary['active_employees'], 'Current active employee records'],
                    ['Current gap', $summary['current_establishment_gap'], 'Approved less active'],
                    ['Fill rate', number_format($summary['current_fill_rate_pct'], 1).'%', 'Current staffing coverage'],
                    ['Projected retirements', $summary['projected_retirements'], $months.'-month horizon'],
                    ['Projected gap', $summary['projected_gap'], 'After retirement + scenario recruitment'],
                    ['Open HR escalations', $summary['open_hr_escalations'], 'Workflow exceptions'],
                    ['Utility outstanding', 'LKR '.number_format($summary['utility_outstanding_lkr'], 2), 'Unpaid / partly paid bills'],
                ];
            @endphp
            @foreach ($headline as [$label, $value, $note])
                <article class="md-card" style="padding:14px">
                    <div class="md-body-sm" style="color:var(--md-on-surface-variant)">{{ $label }}</div>
                    <div class="md-headline-sm" style="margin-top:5px">{{ is_numeric($value) ? number_format($value) : $value }}</div>
                    <div class="md-body-sm" style="margin-top:5px;color:var(--md-on-surface-variant)">{{ $note }}</div>
                </article>
            @endforeach
        </section>

        <section style="display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:16px;margin-bottom:16px">
            <article class="md-card" style="padding:18px">
                <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;margin-bottom:12px">
                    <div><h2 class="md-title-md" style="margin:0">Workforce Capacity Scenario</h2><div class="md-body-sm">Approved vs current and projected workforce.</div></div>
                    <a class="md-btn md-btn--text" href="{{ route('workforce.scenario-comparison') }}">Compare scenarios →</a>
                </div>
                <div style="height:270px"><canvas id="adminWorkforceChart"></canvas></div>
            </article>
            <article class="md-card" style="padding:18px">
                <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;margin-bottom:12px">
                    <div><h2 class="md-title-md" style="margin:0">Governance & Operational Risk</h2><div class="md-body-sm">Open issues that can affect administrative decisions.</div></div>
                    <a class="md-btn md-btn--text" href="{{ route('governance-control.dashboard') }}">Governance control →</a>
                </div>
                <div style="height:270px"><canvas id="adminRiskChart"></canvas></div>
            </article>
        </section>

        <section style="display:grid;grid-template-columns:minmax(0,1.25fr) minmax(300px,.75fr);gap:16px;margin-bottom:16px">
            <article class="md-card" style="overflow:hidden">
                <div style="padding:16px 18px;border-bottom:1px solid var(--md-outline-variant)">
                    <h2 class="md-title-md" style="margin:0">Administrative Attention Queue</h2>
                    <p class="md-body-sm" style="margin:5px 0 0">Ranked by current open count. This is decision support, not an automated decision.</p>
                </div>
                <div style="padding:8px 18px 14px">
                    @foreach ($priorities as $item)
                        <a href="{{ route($item['route']) }}" style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid var(--md-outline-variant);text-decoration:none;color:inherit">
                            <div><strong>{{ $item['label'] }}</strong><div class="md-body-sm" style="color:var(--md-on-surface-variant)">Open the governed workflow for evidence and follow-up.</div></div>
                            <span class="role-badge role-badge--{{ $item['severity'] }}">{{ number_format($item['value']) }}</span>
                        </a>
                    @endforeach
                </div>
            </article>

            <article class="md-card" style="padding:18px">
                <h2 class="md-title-md" style="margin-top:0">Decision Context</h2>
                <div class="role-status-list">
                    <div class="role-status-row"><div><strong>Recruitment vacancies</strong><small>Open vacancy pipeline count.</small></div><div class="role-status-value">{{ number_format($summary['recruitment_vacancies_open']) }}</div></div>
                    <div class="role-status-row"><div><strong>Retirement projects</strong><small>Not yet completed.</small></div><div class="role-status-value">{{ number_format($summary['open_retirement_projects']) }}</div></div>
                    <div class="role-status-row"><div><strong>Increment workflows</strong><small>Not granted / withheld.</small></div><div class="role-status-value">{{ number_format($summary['open_increments']) }}</div></div>
                    <div class="role-status-row"><div><strong>Official reports</strong><small>Still in preparation / review chain.</small></div><div class="role-status-value">{{ number_format($summary['official_reports_in_progress']) }}</div></div>
                    <div class="role-status-row"><div><strong>Overdue utility bills</strong><small>Financial / continuity attention.</small></div><div class="role-status-value">{{ number_format($summary['utility_overdue_bills']) }}</div></div>
                    <div class="role-status-row"><div><strong>Scenario salary impact</strong><small>Recruitment count × entered annual salary.</small></div><div class="role-status-value">LKR {{ number_format($summary['estimated_annual_salary_impact'], 2) }}</div></div>
                </div>
            </article>
        </section>

        <section class="md-card" style="padding:18px">
            <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap">
                <div><h2 class="md-title-md" style="margin:0">Decision Workspaces</h2><p class="md-body-sm" style="margin:5px 0 0">Move from aggregate signal to the relevant governed workflow.</p></div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:10px;margin-top:14px">
                <a class="md-btn md-btn--outlined" href="{{ route('hr-intelligence.index') }}">🧭 HR Responsibility Intelligence</a>
                <a class="md-btn md-btn--outlined" href="{{ route('enterprise.intelligence') }}">📈 Workforce Intelligence</a>
                <a class="md-btn md-btn--outlined" href="{{ route('workforce.reconciliation') }}">🔎 Reconciliation</a>
                <a class="md-btn md-btn--outlined" href="{{ route('data-quality.index') }}">🛡 Data Quality</a>
                <a class="md-btn md-btn--outlined" href="{{ route('retirement-projects.index') }}">🌿 Retirement Project</a>
                <a class="md-btn md-btn--outlined" href="{{ route('official-reports.index') }}">📑 Official Reports</a>
                <a class="md-btn md-btn--outlined" href="{{ route('utility-bills.index') }}">💡 Utility Bill Monitoring</a>
                <a class="md-btn md-btn--outlined" href="{{ route('advanced-governance.index') }}">⚖ Governance Intelligence</a>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('vendor/chartjs/chart.umd.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const workforce = @json($workforceChart);
            const risks = @json($riskChart);
            const workforceCanvas = document.getElementById('adminWorkforceChart');
            const riskCanvas = document.getElementById('adminRiskChart');

            if (window.Chart && workforceCanvas) {
                new Chart(workforceCanvas, {
                    type: 'bar',
                    data: {
                        labels: workforce.map(row => row.label),
                        datasets: [{ label: 'Posts / employees', data: workforce.map(row => row.value), borderWidth: 1 }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
                });
            }
            if (window.Chart && riskCanvas) {
                new Chart(riskCanvas, {
                    type: 'bar',
                    data: {
                        labels: risks.map(row => row.label),
                        datasets: [{ label: 'Open items', data: risks.map(row => row.value), borderWidth: 1 }]
                    },
                    options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true } } }
                });
            }
        });
    </script>
@endpush
