@extends('layouts.app')
@section('title', 'Planning Officer Dashboard')
@section('content')
    <div class="page-shell role-dashboard">
        @include('dashboard._role-header', [
            'title' => 'Planning Officer Dashboard',
            'subtitle' => 'Hospital-wide planning intelligence, evidence review and governed recommendations. Support system only.',
            'asAt' => $asAt,
        ])
        @include('dashboard._decision-support-notice')
        <section class="role-kpi-grid" aria-label="Planning indicators">
            <article class="role-kpi">
                <div class="role-kpi__top">
                    <div>
                        <div class="role-kpi__label">Approved Cadre</div>
                        <div class="role-kpi__value">{{ number_format($approvedTotal) }}</div>
                    </div>
                    <div class="role-kpi__icon">▦</div>
                </div>
                <div class="role-kpi__meta">Current approved posts for {{ $asAt->year }}.</div>
            </article>
            <article class="role-kpi role-kpi--success">
                <div class="role-kpi__top">
                    <div>
                        <div class="role-kpi__label">Recorded Workforce</div>
                        <div class="role-kpi__value">{{ number_format($availableTotal) }}</div>
                    </div>
                    <div class="role-kpi__icon">👥</div>
                </div>
                <div class="role-kpi__meta">{{ number_format($fillRate, 1) }}% system-calculated fill rate.</div>
            </article>
            <article class="role-kpi role-kpi--danger">
                <div class="role-kpi__top">
                    <div>
                        <div class="role-kpi__label">System-Calculated Vacancy Gap</div>
                        <div class="role-kpi__value">{{ number_format($vacancyTotal) }}</div>
                    </div>
                    <div class="role-kpi__icon">△</div>
                </div>
                <div class="role-kpi__meta">Indicator only; validate before workforce action.</div>
            </article>
            <article class="role-kpi role-kpi--purple">
                <div class="role-kpi__top">
                    <div>
                        <div class="role-kpi__label">Rules Due for Review</div>
                        <div class="role-kpi__value">{{ number_format($rulesDueForReview) }}</div>
                    </div>
                    <div class="role-kpi__icon">⚖</div>
                </div>
                <div class="role-kpi__meta">Institutional source review due within 3 months.</div>
            </article>
            <article class="role-kpi role-kpi--warning">
                <div class="role-kpi__top">
                    <div>
                        <div class="role-kpi__label">Retirement Exposure</div>
                        <div class="role-kpi__value">{{ number_format($retirements24m) }}</div>
                    </div>
                    <div class="role-kpi__icon">◷</div>
                </div>
                <div class="role-kpi__meta">System-projected retirements in the next 24 months.</div>
            </article>
            <article class="role-kpi role-kpi--danger">
                <div class="role-kpi__top">
                    <div>
                        <div class="role-kpi__label">Open Data Quality Issues</div>
                        <div class="role-kpi__value">{{ number_format($qualityOpen) }}</div>
                    </div>
                    <div class="role-kpi__icon">!</div>
                </div>
                <div class="role-kpi__meta">Resolve or verify before relying on affected indicators.</div>
            </article>
        </section>
        <section class="role-dashboard-grid">

            <article class="role-panel">
                <div class="role-panel__header">
                    <h2 class="role-panel__title">📊 Management Reports for Hospital Planning</h2>
                    <span class="role-badge role-badge--info">Decision support only</span>
                </div>
                <div class="role-panel__body role-quick-actions">
                    <a class="role-quick-action" href="{{ route('reports.summary') }}">
                        <span class="role-quick-action__icon">▦</span>Summary & Charts
                    </a>
                    <a class="role-quick-action" href="{{ route('reports.officer-submissions') }}">
                        <span class="role-quick-action__icon">✓</span>Submission Monitoring
                    </a>
                    <a class="role-quick-action" href="{{ route('reports.trend') }}">
                        <span class="role-quick-action__icon">↗</span>Trend Analysis
                    </a>
                    <a class="role-quick-action" href="{{ route('reports.yoy') }}">
                        <span class="role-quick-action__icon">≋</span>Year-on-Year
                    </a>
                    <a class="role-quick-action" href="{{ route('reports.snapshot') }}">
                        <span class="role-quick-action__icon">◷</span>Historical Snapshot
                    </a>
                    <a class="role-quick-action" href="{{ route('reports.unit-breakdown') }}">
                        <span class="role-quick-action__icon">🏥</span>Unit Post Availability
                    </a>
                    <a class="role-quick-action" href="{{ route('unit-allocations.index') }}">
                        <span class="role-quick-action__icon">▤</span>Unit Allocations
                    </a>
                    <a class="role-quick-action" href="{{ route('reports.retirement-projections') }}">
                        <span class="role-quick-action__icon">◷</span>Retirement Projections
                    </a>
                    <a class="role-quick-action" href="{{ route('official-reports.index') }}">
                        <span class="role-quick-action__icon">📑</span>Official Report Status
                    </a>
                </div>
                <div class="role-panel__body" style="padding-top:0;">
                    <p class="md-caption" style="margin:0;">
                        These reports support planning analysis and management discussion. Figures must be checked against the authoritative source record and applicable institutional approvals before recruitment, transfer, procurement, expenditure, service reconfiguration, or other consequential action.
                    </p>
                </div>
            </article>
            <article class="role-panel">
                <div class="role-panel__header">
                    <h2 class="role-panel__title">🏥 Hospital Planning Decision Workspace</h2>
                    <a class="role-panel__link" href="{{ route('planning.assessments.index') }}">Open Planning Register →</a>
                </div>
                <div class="role-panel__body">
                    <p class="md-body-sm" style="margin-top:0;">
                        Structure a hospital planning question, verify evidence, compare reasonable options, document impacts and risks,
                        then refer a recommendation to the competent authority. <strong>This workspace does not approve the decision.</strong>
                    </p>
                    <div class="role-kpi-grid" style="margin-top:12px;">
                        <article class="role-kpi">
                            <div class="role-kpi__label">Draft Assessments</div>
                            <div class="role-kpi__value">{{ number_format($planningAssessmentStats['draft'] ?? 0) }}</div>
                            <div class="role-kpi__meta">Planning analysis still being prepared.</div>
                        </article>
                        <article class="role-kpi role-kpi--warning">
                            <div class="role-kpi__label">Ready for Review</div>
                            <div class="role-kpi__value">{{ number_format($planningAssessmentStats['ready'] ?? 0) }}</div>
                            <div class="role-kpi__meta">Evidence and safeguards confirmed for review.</div>
                        </article>
                        <article class="role-kpi role-kpi--success">
                            <div class="role-kpi__label">Referred Recommendations</div>
                            <div class="role-kpi__value">{{ number_format($planningAssessmentStats['referred'] ?? 0) }}</div>
                            <div class="role-kpi__meta">Advisory records referred onward; not final approvals.</div>
                        </article>
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:14px;">
                        <a class="md-btn md-btn--primary" href="{{ route('planning.assessments.create') }}">Start Planning Assessment</a>
                        <a class="md-btn" href="{{ route('planning-intelligence.index') }}">Run What-if Analysis</a>
                    </div>
                </div>
            </article>
            <article class="role-panel">
                <div class="role-panel__header">
                    <h2 class="role-panel__title">☑ Planning Action Centre</h2>
                    <a class="role-panel__link" href="{{ route('workforce.action-center') }}">Open Action Centre →</a>
                </div>
                <div class="role-panel__body role-action-list">
                    <div class="role-action-row">
                        <div>
                            <strong>Validate vacancy and cadre assumptions</strong>
                            <small>Review calculated gaps against approved cadre and latest returns.</small>
                        </div>
                        <span class="role-badge role-badge--danger">{{ $vacancyTotal }} gap</span>
                    </div>
                    <div class="role-action-row">
                        <div>
                            <strong>Review retirement projections</strong>
                            <small>Confirm dates and applicable rules before succession planning.</small>
                        </div>
                        <span class="role-badge role-badge--warning">{{ $retirements24m }}</span>
                    </div>
                    <div class="role-action-row">
                        <div>
                            <strong>Resolve data-quality exceptions</strong>
                            <small>Planning analysis should not rely on unresolved critical records.</small>
                        </div>
                        <span class="role-badge role-badge--danger">{{ $qualityOpen }}</span>
                    </div>
                    <div class="role-action-row">
                        <div>
                            <strong>Review source authority</strong>
                            <small>Check business rules approaching institutional review dates.</small>
                        </div>
                        <span class="role-badge role-badge--warning">{{ $rulesDueForReview }}</span>
                    </div>
                    <div class="role-action-row">
                        <div>
                            <strong>Follow up open incidents</strong>
                            <small>Use the Incident & Correction Register for data or workflow issues.</small>
                        </div>
                        <span class="role-badge role-badge--info">{{ $openIncidents }}</span>
                    </div>
                </div>
            </article>
            <article class="role-panel">
                <div class="role-panel__header">
                    <h2 class="role-panel__title">🛡 Governance & Legal Safeguards</h2>
                    <a class="role-panel__link" href="{{ route('governance.index') }}">View Governance →</a>
                </div>
                <div class="role-panel__body role-status-list">
                    <div class="role-status-row">
                        <div>
                            <strong>Business Rule Register</strong>
                            <small>Source-verified active rules.</small>
                        </div>
                        <div class="role-status-value">{{ $verifiedRules }}/{{ $activeRules }}</div>
                    </div>
                    <div class="role-status-row">
                        <div>
                            <strong>Source Verification Coverage</strong>
                            <small>Institution-recorded source verification status.</small>
                        </div>
                        <div class="role-status-value">{{ $ruleVerificationPct }}%</div>
                    </div>
                    <div class="role-status-row">
                        <div>
                            <strong>Human Approval Control</strong>
                            <small>Consequential HR changes require authorised approval.</small>
                        </div>
                        <span class="role-badge role-badge--success">Enabled</span>
                    </div>
                    <div class="role-status-row">
                        <div>
                            <strong>AI Advisory-Only Boundary</strong>
                            <small>AI may assist with summaries/drafts, not final HR decisions.</small>
                        </div>
                        <span class="role-badge role-badge--success">Active</span>
                    </div>
                    <div class="role-status-row">
                        <div>
                            <strong>Audit Activity This Month</strong>
                            <small>Recorded audit events across system workflows.</small>
                        </div>
                        <div class="role-status-value">{{ number_format($auditThisMonth) }}</div>
                    </div>
                </div>
            </article>
            <article class="role-panel">
                <div class="role-panel__header">
                    <h2 class="role-panel__title">▥ Cadre Forecast & Allocation Summary</h2>
                    <a class="role-panel__link" href="{{ route('workforce.forecast') }}">View Forecast →</a>
                </div>
                <div class="role-panel__body" style="padding:0;">
                    <table class="role-mini-table">
                        <thead>
                            <tr>
                                <th>Position</th>
                                <th>Approved</th>
                                <th>Recorded</th>
                                <th>Gap</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($topVacancyPositions->take(6) as $row)
                                <tr>
                                    <td>{{ $row['position'] }}</td>
                                    <td>{{ number_format($row['approved']) }}</td>
                                    <td>{{ number_format($row['available']) }}</td>
                                    <td>
                                        <span
                                            class="role-badge {{ $row['gap'] > 0 ? 'role-badge--danger' : 'role-badge--success' }}">{{ number_format($row['gap']) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">No cadre data is available for this period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @include('dashboard._privacy-note')
            </article>
        </section>
        <section class="role-panel" style="margin-top:18px;">
            <div class="role-panel__header">
                <h2 class="role-panel__title">▦ Administrative Operations Pulse for Planning</h2>
                <span class="role-badge role-badge--info">Aggregate only</span>
            </div>
            <div class="role-panel__body">
                <div class="role-kpi-grid">
                    <article class="role-kpi">
                        <div class="role-kpi__label">Professional Registrations — 90 Days</div>
                        <div class="role-kpi__value">{{ number_format($decisionPulse['registrationExpiry90']) }}</div>
                        <div class="role-kpi__meta">Institution-wide expiry exposure for planning continuity.</div>
                    </article>
                    <article class="role-kpi role-kpi--warning">
                        <div class="role-kpi__label">Increments — 30 Days</div>
                        <div class="role-kpi__value">{{ number_format($decisionPulse['increments30']) }}</div>
                        <div class="role-kpi__meta">Upcoming administrative workload signal.</div>
                    </article>
                    <article class="role-kpi role-kpi--danger">
                        <div class="role-kpi__label">Incomplete Core Data</div>
                        <div class="role-kpi__value">{{ number_format($decisionPulse['missingCoreData']) }}</div>
                        <div class="role-kpi__meta">Records missing DOB, position or Subject Code.</div>
                    </article>
                    <article class="role-kpi role-kpi--purple">
                        <div class="role-kpi__label">Net Transfers — 90 Days</div>
                        <div class="role-kpi__value">
                            {{ $decisionPulse['netTransfer90'] >= 0 ? '+' : '' }}{{ number_format($decisionPulse['netTransfer90']) }}
                        </div>
                        <div class="role-kpi__meta">Aggregate movement indicator for capacity planning.</div>
                    </article>
                </div>
                <p class="md-caption" style="margin:12px 0 0;">These signals mirror the aggregate administrative
                    intelligence available to management roles, adapted for planning. They do not expose employee identities
                    and must be verified against official records before consequential decisions.</p>
            </div>
        </section>

        <section class="role-dashboard-grid role-dashboard-grid--bottom">
            <article class="role-panel">
                <div class="role-panel__header">
                    <h2 class="role-panel__title">▥ Forecasting & Retirement Trend</h2>
                    <a class="role-panel__link" href="{{ route('reports.retirement-projections') }}">Retirement Analysis
                        →</a>
                </div>
                <div class="role-panel__body">
                    @php
                        $trendMax = max(1, (int) $retirementTrend->max());
                    @endphp
                    <div class="role-chart" aria-label="System-projected retirement trend">
                        @foreach ($retirementTrend as $year => $count)
                            <div class="role-chart__column">
                                <div class="role-chart__value">{{ $count }}</div>
                                <div class="role-chart__bar"
                                    style="height: {{ max(4, round(($count / $trendMax) * 145)) }}px;">
                                </div>
                                <div class="role-chart__label">{{ $year }}</div>
                            </div>
                        @endforeach
                    </div>
                    <p class="md-caption" style="margin-top:10px;">System-projected values based on recorded dates and
                        configured retirement rules. Verify before formal planning action.</p>
                </div>
            </article>
            <article class="role-panel">
                <div class="role-panel__header">
                    <h2 class="role-panel__title">⚠ Planning Risk Signals</h2>
                    <a class="role-panel__link" href="{{ route('data-quality.index') }}">Data Quality →</a>
                </div>
                <div class="role-panel__body role-status-list">
                    @foreach ($planningSignals as $signal)
                        <div class="role-status-row">
                            <div>
                                <strong>{{ $signal['label'] }}</strong>
                                <small>System-generated planning signal.</small>
                            </div>
                            <div class="role-status-value">{{ number_format($signal['value']) }}</div>
                        </div>
                    @endforeach
                </div>
            </article>
            <article class="role-panel">
                <div class="role-panel__header">
                    <h2 class="role-panel__title">⚡ Quick Actions</h2>
                </div>
                <div class="role-panel__body role-quick-actions">
                    <a class="role-quick-action" href="{{ route('planning.assessments.index') }}">
                        <span class="role-quick-action__icon">🏥</span>Planning Decision Support</a>
                    <a class="role-quick-action" href="{{ route('planning-intelligence.index') }}">
                        <span class="role-quick-action__icon">▥</span>Planning Intelligence</a>
                    <a class="role-quick-action" href="{{ route('workforce.scenario-comparison') }}">
                        <span class="role-quick-action__icon">◫</span>Scenario Comparison</a>
                    <a class="role-quick-action role-quick-action--warning" href="{{ route('governance.index') }}">
                        <span class="role-quick-action__icon">⚖</span>Review Business Rules</a>
                    <a class="role-quick-action role-quick-action--danger" href="{{ route('incidents.create') }}">
                        <span class="role-quick-action__icon">!</span>Report an Incident</a>
                </div>
            </article>
        </section>
    </div>
@endsection
