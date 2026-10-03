@extends('layouts.app')
@section('title', 'Medical Officer Planning Dashboard')
@section('content')
    <div class="page-shell role-dashboard">
        @include('dashboard._role-header', [
            'title' => 'Medical Officer Planning Dashboard',
            'subtitle' =>
                'Operational medical cadre planning, vacancy review, retirement exposure and validated workforce analysis.',
            'asAt' => $asAt,
        ])
        @include('dashboard._decision-support-notice')
        <section class="role-kpi-grid" aria-label="Medical planning indicators">
            <article class="role-kpi">
                <div class="role-kpi__top">
                    <div>
                        <div class="role-kpi__label">Approved Cadre Posts</div>
                        <div class="role-kpi__value">{{ number_format($approvedTotal) }}</div>
                    </div>
                    <div class="role-kpi__icon">⚕</div>
                </div>
                <div class="role-kpi__meta">Hospital-wide approved cadre context available to Medical Officer Planning.</div>
            </article>
            <article class="role-kpi role-kpi--success">
                <div class="role-kpi__top">
                    <div>
                        <div class="role-kpi__label">Recorded Workforce</div>
                        <div class="role-kpi__value">{{ number_format($availableTotal) }}</div>
                    </div>
                    <div class="role-kpi__icon">👤</div>
                </div>
                <div class="role-kpi__meta">{{ number_format($fillRate, 1) }}% system-calculated fill rate.</div>
            </article>
            <article class="role-kpi role-kpi--danger">
                <div class="role-kpi__top">
                    <div>
                        <div class="role-kpi__label">Vacancy Gap</div>
                        <div class="role-kpi__value">{{ number_format($vacancyTotal) }}</div>
                    </div>
                    <div class="role-kpi__icon">⇄</div>
                </div>
                <div class="role-kpi__meta">Calculated indicator. Confirm against approved cadre records before action.
                </div>
            </article>
            <article class="role-kpi role-kpi--purple">
                <div class="role-kpi__top">
                    <div>
                        <div class="role-kpi__label">Planning Reviews</div>
                        <div class="role-kpi__value">{{ number_format($pendingDecisions) }}</div>
                    </div>
                    <div class="role-kpi__icon">☷</div>
                </div>
                <div class="role-kpi__meta">Pending consequential HR proposals in the formal approval workflow.</div>
            </article>
            <article class="role-kpi role-kpi--warning">
                <div class="role-kpi__top">
                    <div>
                        <div class="role-kpi__label">Upcoming Retirements</div>
                        <div class="role-kpi__value">{{ number_format($retirements24m) }}</div>
                    </div>
                    <div class="role-kpi__icon">◷</div>
                </div>
                <div class="role-kpi__meta">System-projected exposure over the next 24 months.</div>
            </article>
            <article class="role-kpi role-kpi--danger">
                <div class="role-kpi__top">
                    <div>
                        <div class="role-kpi__label">Data Quality Alerts</div>
                        <div class="role-kpi__value">{{ number_format($qualityOpen) }}</div>
                    </div>
                    <div class="role-kpi__icon">!</div>
                </div>
                <div class="role-kpi__meta">Records requiring correction or verification.</div>
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
                    <h2 class="role-panel__title">☑ Medical Workforce Planning Priorities</h2>
                    <a class="role-panel__link" href="{{ route('workforce.action-center') }}">View Action Centre →</a>
                </div>
                <div class="role-panel__body role-action-list">
                    <div class="role-action-row">
                        <div>
                            <strong>Review vacancy concentration</strong>
                            <small>Prioritise posts with the largest approved-to-recorded gaps.</small>
                        </div>
                        <span class="role-badge role-badge--danger">{{ $vacancyTotal }}</span>
                    </div>
                    <div class="role-action-row">
                        <div>
                            <strong>Review retirement exposure</strong>
                            <small>Check succession implications for posts due to become vacant.</small>
                        </div>
                        <span class="role-badge role-badge--warning">{{ $retirements24m }}</span>
                    </div>
                    <div class="role-action-row">
                        <div>
                            <strong>Check acting / transfer planning</strong>
                            <small>Use formal workflows for any consequential employee action.</small>
                        </div>
                        <span class="role-badge role-badge--info">Workflow</span>
                    </div>
                    <div class="role-action-row">
                        <div>
                            <strong>Resolve planning data issues</strong>
                            <small>Do not base recommendations on unresolved material errors.</small>
                        </div>
                        <span class="role-badge role-badge--danger">{{ $qualityOpen }}</span>
                    </div>
                    <div class="role-action-row">
                        <div>
                            <strong>Review authority sources</strong>
                            <small>Validate rule references before applying workforce criteria.</small>
                        </div>
                        <span class="role-badge role-badge--warning">{{ $rulesDueForReview }}</span>
                    </div>
                </div>
            </article>
            <article class="role-panel">
                <div class="role-panel__header">
                    <h2 class="role-panel__title">🛡 Planning Governance Snapshot</h2>
                    <a class="role-panel__link" href="{{ route('governance.index') }}">View Details →</a>
                </div>
                <div class="role-panel__body role-status-list">
                    <div class="role-status-row">
                        <div>
                            <strong>Institutional Decision Authority</strong>
                            <small>Final authority remains with authorised institutional officers.</small>
                        </div>
                        <span class="role-badge role-badge--success">Human</span>
                    </div>
                    <div class="role-status-row">
                        <div>
                            <strong>Business Rule Verification</strong>
                            <small>Source-verified active rules.</small>
                        </div>
                        <div class="role-status-value">{{ $ruleVerificationPct }}%</div>
                    </div>
                    <div class="role-status-row">
                        <div>
                            <strong>Rules Due for Review</strong>
                            <small>Institutional review due within 3 months.</small>
                        </div>
                        <div class="role-status-value">{{ $rulesDueForReview }}</div>
                    </div>
                    <div class="role-status-row">
                        <div>
                            <strong>AI Decision Boundary</strong>
                            <small>AI cannot approve or apply final HR decisions.</small>
                        </div>
                        <span class="role-badge role-badge--success">Advisory</span>
                    </div>
                    <div class="role-status-row">
                        <div>
                            <strong>Open Incident Register</strong>
                            <small>Operational, data and governance issues under follow-up.</small>
                        </div>
                        <div class="role-status-value">{{ $openIncidents }}</div>
                    </div>
                </div>
            </article>
            <article class="role-panel">
                <div class="role-panel__header">
                    <h2 class="role-panel__title">▥ Vacancy Watchlist</h2>
                    <a class="role-panel__link" href="{{ route('approved-carders.index') }}">Approved Cadre →</a>
                </div>
                <div class="role-panel__body" style="padding:0;">
                    <table class="role-mini-table">
                        <thead>
                            <tr>
                                <th>Position</th>
                                <th>Approved</th>
                                <th>Recorded</th>
                                <th>Gap</th>
                                <th>Fill</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($topVacancyPositions->take(7) as $row)
                                <tr>
                                    <td>{{ $row['position'] }}</td>
                                    <td>{{ $row['approved'] }}</td>
                                    <td>{{ $row['available'] }}</td>
                                    <td>
                                        <span
                                            class="role-badge {{ $row['gap'] > 0 ? 'role-badge--danger' : 'role-badge--success' }}">{{ $row['gap'] }}</span>
                                    </td>
                                    <td>{{ number_format($row['fill_rate'], 1) }}%</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">No vacancy data is available for this period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @include('dashboard._privacy-note')
            </article>
        </section>
        <section class="role-dashboard-grid role-dashboard-grid--bottom">
            <article class="role-panel">
                <div class="role-panel__header">
                    <h2 class="role-panel__title">▥ Cadre Distribution by Highest Vacancy Gap</h2>
                    <a class="role-panel__link" href="{{ route('reports.summary') }}">View Analytics →</a>
                </div>
                <div class="role-panel__body">
                    @php
                        $vacancyMax = max(1, (int) $topVacancyPositions->max('gap'));
                    @endphp
                    <div class="role-chart" aria-label="Vacancy gap by position">
                        @foreach ($topVacancyPositions->take(7) as $row)
                            <div class="role-chart__column">
                                <div class="role-chart__value">{{ $row['gap'] }}</div>
                                <div class="role-chart__bar"
                                    style="height: {{ max(4, round(($row['gap'] / $vacancyMax) * 145)) }}px;">
                                </div>
                                <div class="role-chart__label">{{ \Illuminate\Support\Str::limit($row['position'], 16) }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </article>
            <article class="role-panel">
                <div class="role-panel__header">
                    <h2 class="role-panel__title">◷ Retirement Exposure & Succession Signal</h2>
                    <a class="role-panel__link" href="{{ route('reports.retirement-projections') }}">View Projection →</a>
                </div>
                <div class="role-panel__body">
                    @php
                        $retirementMax = max(1, (int) $retirementTrend->max());
                    @endphp
                    <div class="role-chart">
                        @foreach ($retirementTrend as $year => $count)
                            <div class="role-chart__column">
                                <div class="role-chart__value">{{ $count }}</div>
                                <div class="role-chart__bar"
                                    style="height: {{ max(4, round(($count / $retirementMax) * 145)) }}px;">
                                </div>
                                <div class="role-chart__label">{{ $year }}</div>
                            </div>
                        @endforeach
                    </div>
                    <p class="md-caption" style="margin-top:10px;">Projection only. Confirm employee dates, applicable
                        retirement age and authoritative rules before decisions.</p>
                </div>
            </article>
            <article class="role-panel">
                <div class="role-panel__header">
                    <h2 class="role-panel__title">⚡ Quick Actions</h2>
                </div>
                <div class="role-panel__body role-quick-actions">
                    <a class="role-quick-action" href="{{ route('planning-intelligence.index') }}">
                        <span class="role-quick-action__icon">▥</span>Planning Intelligence</a>
                    <a class="role-quick-action" href="{{ route('approved-carders.index') }}">
                        <span class="role-quick-action__icon">☷</span>Approved Cadre</a>
                    <a class="role-quick-action role-quick-action--warning" href="{{ route('data-quality.index') }}">
                        <span class="role-quick-action__icon">!</span>Data Quality</a>
                    <a class="role-quick-action role-quick-action--danger" href="{{ route('incidents.create') }}">
                        <span class="role-quick-action__icon">⚠</span>Report Incident</a>
                </div>
            </article>
        </section>
    </div>
@endsection
