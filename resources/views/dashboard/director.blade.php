@extends('layouts.app')
@section('title', 'Director Dashboard')
@section('content')
    <div class="page-shell role-dashboard">
        @include('dashboard._role-header', [
            'title' => 'Director Dashboard',
            'subtitle' =>
                'Executive oversight of cadre, governance, administrative decisions and institutional risk.',
            'asAt' => $asAt,
        ])
        @include('dashboard._decision-support-notice')
        <section class="role-kpi-grid">
            <article class="role-kpi">
                <div class="role-kpi__top">
                    <div>
                        <div class="role-kpi__label">Recorded Workforce</div>
                        <div class="role-kpi__value">{{ number_format($activeEmployees) }}</div>
                    </div>
                    <div class="role-kpi__icon">👥</div>
                </div>
                <div class="role-kpi__meta">Aggregate active employee count.</div>
            </article>
            <article class="role-kpi role-kpi--danger">
                <div class="role-kpi__top">
                    <div>
                        <div class="role-kpi__label">Pending Decisions</div>
                        <div class="role-kpi__value">{{ number_format($pendingDecisions) }}</div>
                    </div>
                    <div class="role-kpi__icon">☷</div>
                </div>
                <div class="role-kpi__meta">Consequential proposals awaiting authorised human review.</div>
            </article>
            <article class="role-kpi role-kpi--warning">
                <div class="role-kpi__top">
                    <div>
                        <div class="role-kpi__label">Retirement Exposure</div>
                        <div class="role-kpi__value">{{ number_format($retirements12m) }}</div>
                    </div>
                    <div class="role-kpi__icon">◷</div>
                </div>
                <div class="role-kpi__meta">System-projected retirements in the next 12 months.</div>
            </article>
            <article class="role-kpi role-kpi--danger">
                <div class="role-kpi__top">
                    <div>
                        <div class="role-kpi__label">Data Quality Issues</div>
                        <div class="role-kpi__value">{{ number_format($qualityOpen) }}</div>
                    </div>
                    <div class="role-kpi__icon">!</div>
                </div>
                <div class="role-kpi__meta">Open issues requiring correction or verification.</div>
            </article>
            <article class="role-kpi role-kpi--purple">
                <div class="role-kpi__top">
                    <div>
                        <div class="role-kpi__label">Rules Due for Review</div>
                        <div class="role-kpi__value">{{ number_format($rulesDueForReview) }}</div>
                    </div>
                    <div class="role-kpi__icon">⚖</div>
                </div>
                <div class="role-kpi__meta">Official-source review due within three months.</div>
            </article>
            <article class="role-kpi role-kpi--danger">
                <div class="role-kpi__top">
                    <div>
                        <div class="role-kpi__label">High / Critical Incidents</div>
                        <div class="role-kpi__value">{{ number_format($seriousIncidents) }}</div>
                    </div>
                    <div class="role-kpi__icon">⚠</div>
                </div>
                <div class="role-kpi__meta">Open serious incidents requiring institutional attention.</div>
            </article>
        </section>
        <section class="role-dashboard-grid">
            <article class="role-panel">
                <div class="role-panel__header">
                    <h2 class="role-panel__title">☑ Executive Priority Actions</h2>
                    <a class="role-panel__link" href="{{ route('workforce.action-center') }}">View All →</a>
                </div>
                <div class="role-panel__body role-action-list">
                    @foreach ($actionCards as $card)
                        <div class="role-action-row">
                            <div>
                                <strong>{{ $card['label'] }}</strong>
                                <small>Open the governed workflow for review and action.</small>
                            </div>
                            <span
                                class="role-badge {{ $card['tone'] === 'danger' ? 'role-badge--danger' : ($card['tone'] === 'warning' ? 'role-badge--warning' : 'role-badge--info') }}">{{ $card['value'] }}</span>
                        </div>
                    @endforeach
                    <div class="role-action-row">
                        <div>
                            <strong>Approved cadre versus recorded workforce</strong>
                            <small>Review aggregate workforce capacity and gaps.</small>
                        </div>
                        <span class="role-badge role-badge--info">{{ $fillRate }}%</span>
                    </div>
                </div>
            </article>
            <article class="role-panel">
                <div class="role-panel__header">
                    <h2 class="role-panel__title">🛡 Institution Governance Snapshot</h2>
                    <a class="role-panel__link" href="{{ route('governance.index') }}">View Details →</a>
                </div>
                <div class="role-panel__body role-status-list">
                    <div class="role-status-row">
                        <div>
                            <strong>Official-source verified rules</strong>
                            <small>Verified against institution-recorded authority references.</small>
                        </div>
                        <div class="role-status-value">{{ $verifiedRules }}/{{ $activeRules }}</div>
                    </div>
                    <div class="role-status-row">
                        <div>
                            <strong>Verification coverage</strong>
                            <small>Business Rule Register source-verification indicator.</small>
                        </div>
                        <div class="role-status-value">{{ $ruleVerificationPct }}%</div>
                    </div>
                    <div class="role-status-row">
                        <div>
                            <strong>Human decision authority</strong>
                            <small>System does not make final consequential HR decisions.</small>
                        </div>
                        <span class="role-badge role-badge--success">Required</span>
                    </div>
                    <div class="role-status-row">
                        <div>
                            <strong>AI advisory-only boundary</strong>
                            <small>Drafting and summaries only.</small>
                        </div>
                        <span class="role-badge role-badge--success">Enforced</span>
                    </div>
                    <div class="role-status-row">
                        <div>
                            <strong>Audit events this month</strong>
                            <small>Recorded system audit activity.</small>
                        </div>
                        <div class="role-status-value">{{ number_format($auditThisMonth) }}</div>
                    </div>
                </div>
            </article>
            <article class="role-panel">
                <div class="role-panel__header">
                    <h2 class="role-panel__title">▥ Highest Cadre Gaps</h2>
                    <a class="role-panel__link" href="{{ route('reports.summary') }}">View Reports →</a>
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
                                    <td>{{ $row['approved'] }}</td>
                                    <td>{{ $row['available'] }}</td>
                                    <td>
                                        <span class="role-badge role-badge--danger">{{ $row['gap'] }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">No aggregate cadre data is available.</td>
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
                    <h2 class="role-panel__title">▥ Workforce Capacity Overview</h2>
                    <a class="role-panel__link" href="{{ route('workforce.forecast') }}">Forecast →</a>
                </div>
                <div class="role-panel__body role-status-list">
                    <div class="role-status-row">
                        <div>
                            <strong>Approved cadre</strong>
                            <small>Institutional approved posts.</small>
                        </div>
                        <div class="role-status-value">{{ number_format($approvedTotal) }}</div>
                    </div>
                    <div class="role-status-row">
                        <div>
                            <strong>Recorded workforce</strong>
                            <small>Monthly workforce count.</small>
                        </div>
                        <div class="role-status-value">{{ number_format($availableTotal) }}</div>
                    </div>
                    <div class="role-status-row">
                        <div>
                            <strong>System-calculated vacancy gap</strong>
                            <small>Validate before administrative action.</small>
                        </div>
                        <div class="role-status-value">{{ number_format($vacancyTotal) }}</div>
                    </div>
                    <div class="role-status-row">
                        <div>
                            <strong>Fill rate indicator</strong>
                            <small>System-calculated aggregate.</small>
                        </div>
                        <div class="role-status-value">{{ $fillRate }}%</div>
                    </div>
                </div>
            </article>
            <article class="role-panel">
                <div class="role-panel__header">
                    <h2 class="role-panel__title">⚠ Institutional Risk Signals</h2>
                    <a class="role-panel__link" href="{{ route('incidents.index') }}">Incident Register →</a>
                </div>
                <div class="role-panel__body role-status-list">
                    <div class="role-status-row">
                        <div>
                            <strong>Open incidents</strong>
                            <small>All non-closed incident records.</small>
                        </div>
                        <div class="role-status-value">{{ $openIncidents }}</div>
                    </div>
                    <div class="role-status-row">
                        <div>
                            <strong>High / critical incidents</strong>
                            <small>Require enhanced follow-up.</small>
                        </div>
                        <div class="role-status-value">{{ $seriousIncidents }}</div>
                    </div>
                    <div class="role-status-row">
                        <div>
                            <strong>Data quality issues</strong>
                            <small>Potentially affects administrative indicators.</small>
                        </div>
                        <div class="role-status-value">{{ $qualityOpen }}</div>
                    </div>
                    <div class="role-status-row">
                        <div>
                            <strong>Service letters pending</strong>
                            <small>Awaiting formal workflow action.</small>
                        </div>
                        <div class="role-status-value">{{ $serviceLettersPending }}</div>
                    </div>
                </div>
            </article>
            <article class="role-panel">
                <div class="role-panel__header">
                    <h2 class="role-panel__title">⚡ Quick Actions</h2>
                </div>
                <div class="role-panel__body role-quick-actions">
                    <a class="role-quick-action" href="{{ route('administrative-decisions.index') }}">
                        <span class="role-quick-action__icon">☷</span>Decision Queue</a>
                    <a class="role-quick-action" href="{{ route('governance.index') }}">
                        <span class="role-quick-action__icon">⚖</span>Governance</a>
                    <a class="role-quick-action role-quick-action--warning" href="{{ route('reports.summary') }}">
                        <span class="role-quick-action__icon">▥</span>Reports</a>
                    <a class="role-quick-action role-quick-action--danger" href="{{ route('incidents.create') }}">
                        <span class="role-quick-action__icon">!</span>Report Incident</a>
                </div>
            </article>
        </section>
    </div>
@endsection
