@extends('layouts.app')
@section('title', 'Deputy Director Dashboard')
@section('content')
    <div class="page-shell role-dashboard">
        @include('dashboard._role-header', [
            'title' => 'Deputy Director Dashboard',
            'subtitle' =>
                'Operational oversight of cadre administration, workforce risks and governed follow-up actions.',
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
                <div class="role-kpi__meta">Aggregate active workforce.</div>
            </article>
            <article class="role-kpi role-kpi--warning">
                <div class="role-kpi__top">
                    <div>
                        <div class="role-kpi__label">Pending Workforce Decisions</div>
                        <div class="role-kpi__value">{{ number_format($pendingDecisions) }}</div>
                    </div>
                    <div class="role-kpi__icon">☷</div>
                </div>
                <div class="role-kpi__meta">Pending formal approval workflow items.</div>
            </article>
            <article class="role-kpi role-kpi--danger">
                <div class="role-kpi__top">
                    <div>
                        <div class="role-kpi__label">Vacancy Gap</div>
                        <div class="role-kpi__value">{{ number_format($vacancyTotal) }}</div>
                    </div>
                    <div class="role-kpi__icon">⇄</div>
                </div>
                <div class="role-kpi__meta">System-calculated aggregate gap.</div>
            </article>
            <article class="role-kpi role-kpi--warning">
                <div class="role-kpi__top">
                    <div>
                        <div class="role-kpi__label">Retirements — 24 Months</div>
                        <div class="role-kpi__value">{{ number_format($retirements24m) }}</div>
                    </div>
                    <div class="role-kpi__icon">◷</div>
                </div>
                <div class="role-kpi__meta">Projection for operational planning.</div>
            </article>
            <article class="role-kpi role-kpi--danger">
                <div class="role-kpi__top">
                    <div>
                        <div class="role-kpi__label">Data Quality Issues</div>
                        <div class="role-kpi__value">{{ number_format($qualityOpen) }}</div>
                    </div>
                    <div class="role-kpi__icon">!</div>
                </div>
                <div class="role-kpi__meta">Open corrections and verification needs.</div>
            </article>
            <article class="role-kpi role-kpi--purple">
                <div class="role-kpi__top">
                    <div>
                        <div class="role-kpi__label">Service Letters Pending</div>
                        <div class="role-kpi__value">{{ number_format($serviceLettersPending) }}</div>
                    </div>
                    <div class="role-kpi__icon">✉</div>
                </div>
                <div class="role-kpi__meta">Formal letters awaiting approval.</div>
            </article>
        </section>
        <section class="role-dashboard-grid">
            <article class="role-panel">
                <div class="role-panel__header">
                    <h2 class="role-panel__title">⚙ Operational Action Centre</h2>
                    <a class="role-panel__link" href="{{ route('workforce.action-center') }}">View All →</a>
                </div>
                <div class="role-panel__body role-action-list">
                    @foreach ($actionCards as $card)
                        <div class="role-action-row">
                            <div>
                                <strong>{{ $card['label'] }}</strong>
                                <small>Review through the authorised workflow.</small>
                            </div>
                            <span
                                class="role-badge {{ $card['tone'] === 'danger' ? 'role-badge--danger' : ($card['tone'] === 'warning' ? 'role-badge--warning' : 'role-badge--info') }}">{{ $card['value'] }}</span>
                        </div>
                    @endforeach
                    <div class="role-action-row">
                        <div>
                            <strong>Retirement and succession follow-up</strong>
                            <small>Review operational staffing exposure.</small>
                        </div>
                        <span class="role-badge role-badge--warning">{{ $retirements24m }}</span>
                    </div>
                </div>
            </article>
            <article class="role-panel">
                <div class="role-panel__header">
                    <h2 class="role-panel__title">🛡 Governance & Compliance</h2>
                    <a class="role-panel__link" href="{{ route('governance.index') }}">Details →</a>
                </div>
                <div class="role-panel__body role-status-list">
                    <div class="role-status-row">
                        <div>
                            <strong>Source-verified business rules</strong>
                            <small>Institution-recorded source status.</small>
                        </div>
                        <div class="role-status-value">{{ $ruleVerificationPct }}%</div>
                    </div>
                    <div class="role-status-row">
                        <div>
                            <strong>Rules due for review</strong>
                            <small>Due within three months.</small>
                        </div>
                        <div class="role-status-value">{{ $rulesDueForReview }}</div>
                    </div>
                    <div class="role-status-row">
                        <div>
                            <strong>Human approval control</strong>
                            <small>Final consequential actions require authorised approval.</small>
                        </div>
                        <span class="role-badge role-badge--success">Enabled</span>
                    </div>
                    <div class="role-status-row">
                        <div>
                            <strong>Open incidents</strong>
                            <small>Operational and governance follow-up.</small>
                        </div>
                        <div class="role-status-value">{{ $openIncidents }}</div>
                    </div>
                    <div class="role-status-row">
                        <div>
                            <strong>Audit events this month</strong>
                            <small>Recorded system activity.</small>
                        </div>
                        <div class="role-status-value">{{ $auditThisMonth }}</div>
                    </div>
                </div>
            </article>
            <article class="role-panel">
                <div class="role-panel__header">
                    <h2 class="role-panel__title">▥ Cadre Pressure Points</h2>
                    <a class="role-panel__link" href="{{ route('reports.summary') }}">Reports →</a>
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
                            </tr>@empty<tr>
                                    <td colspan="4">No aggregate cadre data is available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>@include('dashboard._privacy-note')
            </article>
        </section>
        <section class="role-dashboard-grid role-dashboard-grid--bottom">
            <article class="role-panel">
                <div class="role-panel__header">
                    <h2 class="role-panel__title">▥ Workforce Position Summary</h2>
                    <a class="role-panel__link" href="{{ route('workforce.forecast') }}">View Forecast →</a>
                </div>
                <div class="role-panel__body role-status-list">
                    <div class="role-status-row">
                        <div>
                            <strong>Approved cadre</strong>
                            <small>Current approved posts.</small>
                        </div>
                        <div class="role-status-value">{{ $approvedTotal }}</div>
                    </div>
                    <div class="role-status-row">
                        <div>
                            <strong>Recorded workforce</strong>
                            <small>Latest workforce return.</small>
                        </div>
                        <div class="role-status-value">{{ $availableTotal }}</div>
                    </div>
                    <div class="role-status-row">
                        <div>
                            <strong>Fill rate</strong>
                            <small>System-calculated indicator.</small>
                        </div>
                        <div class="role-status-value">{{ $fillRate }}%</div>
                    </div>
                    <div class="role-status-row">
                        <div>
                            <strong>Vacancy gap</strong>
                            <small>Requires record verification before action.</small>
                        </div>
                        <div class="role-status-value">{{ $vacancyTotal }}</div>
                    </div>
                </div>
            </article>
            <article class="role-panel">
                <div class="role-panel__header">
                    <h2 class="role-panel__title">⚠ Follow-up Signals</h2>
                    <a class="role-panel__link" href="{{ route('data-quality.index') }}">Data Quality →</a>
                </div>
                <div class="role-panel__body role-status-list">
                    <div class="role-status-row">
                        <div>
                            <strong>Data quality issues</strong>
                            <small>Open issues.</small>
                        </div>
                        <div class="role-status-value">{{ $qualityOpen }}</div>
                    </div>
                    <div class="role-status-row">
                        <div>
                            <strong>Open incidents</strong>
                            <small>Non-closed incident records.</small>
                        </div>
                        <div class="role-status-value">{{ $openIncidents }}</div>
                    </div>
                    <div class="role-status-row">
                        <div>
                            <strong>Serious incidents</strong>
                            <small>High / critical severity.</small>
                        </div>
                        <div class="role-status-value">{{ $seriousIncidents }}</div>
                    </div>
                    <div class="role-status-row">
                        <div>
                            <strong>Increments due in 30 days</strong>
                            <small>System date indicator.</small>
                        </div>
                        <div class="role-status-value">{{ $increments30 }}</div>
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
                    <a class="role-quick-action" href="{{ route('reports.summary') }}">
                        <span class="role-quick-action__icon">▥</span>Reports</a>
                    <a class="role-quick-action role-quick-action--warning" href="{{ route('data-quality.index') }}">
                        <span class="role-quick-action__icon">!</span>Data Quality</a>
                    <a class="role-quick-action role-quick-action--danger" href="{{ route('incidents.create') }}">
                        <span class="role-quick-action__icon">⚠</span>Report Incident</a>
                </div>
            </article>
        </section>
    </div>
@endsection
