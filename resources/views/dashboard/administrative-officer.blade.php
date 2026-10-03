@extends('layouts.app')
@section('title', 'Hospital Secretary / Administrative Officer Dashboard')
@section('content')
    <div class="page-shell role-dashboard">
        @include('dashboard._role-header', [
            'title' => 'Hospital Secretary / Administrative Officer Dashboard',
            'subtitle' =>
                'Administrative governance, independent approvals, service letters, audit and incident follow-up.',
            'asAt' => $asAt,
        ])
        @include('dashboard._decision-support-notice')
        @include('partials.governance-legal-safeguard', [
            'title' => 'Administrative decision-use safeguard',
            'purpose' => 'This dashboard prioritises work for authorised officers. Counts, trends and risk signals support review; they are not findings against an employee and do not replace the source record or delegated authority.',
            'items' => [
                'Open the governed source register before making a decision or contacting an employee.',
                'Use aggregate indicators to prioritise workload, not to infer blame, misconduct or individual performance.',
                'For consequential actions, verify authority, evidence, applicable rule/version and segregation of duties.',
                'Keep sensitive details out of dashboard notes and exports unless they are necessary for the official purpose.',
                'Use correction/disable/archive workflows rather than deleting historical evidence.',
            ],
        ])
        <section class="role-kpi-grid">
            <article class="role-kpi role-kpi--danger">
                <div class="role-kpi__top">
                    <div>
                        <div class="role-kpi__label">Pending Administrative Decisions</div>
                        <div class="role-kpi__value">{{ $pendingDecisions }}</div>
                    </div>
                    <div class="role-kpi__icon">☷</div>
                </div>
                <div class="role-kpi__meta">Awaiting independent authorised human review.</div>
            </article>
            <article class="role-kpi role-kpi--purple">
                <div class="role-kpi__top">
                    <div>
                        <div class="role-kpi__label">Service Letters Awaiting Approval</div>
                        <div class="role-kpi__value">{{ $serviceLettersPending }}</div>
                    </div>
                    <div class="role-kpi__icon">✉</div>
                </div>
                <div class="role-kpi__meta">Formal approval remains a human institutional action.</div>
            </article>
            <article class="role-kpi role-kpi--warning">
                <div class="role-kpi__top">
                    <div>
                        <div class="role-kpi__label">Governance Rules Due Review</div>
                        <div class="role-kpi__value">{{ $rulesDueForReview }}</div>
                    </div>
                    <div class="role-kpi__icon">⚖</div>
                </div>
                <div class="role-kpi__meta">Official source references require institutional review.</div>
            </article>
            <article class="role-kpi role-kpi--danger">
                <div class="role-kpi__top">
                    <div>
                        <div class="role-kpi__label">Open Incidents</div>
                        <div class="role-kpi__value">{{ $openIncidents }}</div>
                    </div>
                    <div class="role-kpi__icon">⚠</div>
                </div>
                <div class="role-kpi__meta">Data, calculation, workflow, privacy or system incidents.</div>
            </article>
            <article class="role-kpi role-kpi--warning">
                <div class="role-kpi__top">
                    <div>
                        <div class="role-kpi__label">Data Corrections Pending</div>
                        <div class="role-kpi__value">{{ $qualityOpen }}</div>
                    </div>
                    <div class="role-kpi__icon">◫</div>
                </div>
                <div class="role-kpi__meta">Open data-quality issues requiring resolution or verification.</div>
            </article>
            <article class="role-kpi role-kpi--success">
                <div class="role-kpi__top">
                    <div>
                        <div class="role-kpi__label">Audit Events This Month</div>
                        <div class="role-kpi__value">{{ $auditThisMonth }}</div>
                    </div>
                    <div class="role-kpi__icon">✓</div>
                </div>
                <div class="role-kpi__meta">System audit activity recorded during the current month.</div>
            </article>
        </section>

        <section class="role-dashboard-grid" style="margin-bottom:16px">
            <article class="role-panel">
                <div class="role-panel__header">
                    <h2 class="role-panel__title">🧠 Administrative Workforce Intelligence</h2>
                    <a class="role-panel__link" href="{{ route('administrative-intelligence.index') }}">Open Intelligence →</a>
                </div>
                <div class="role-panel__body role-status-list">
                    @foreach ($adminDecisionSignals as $signal)
                        <a class="role-status-row" href="{{ route($signal['route']) }}" style="text-decoration:none;color:inherit">
                            <div><strong>{{ $signal['label'] }}</strong><small>{{ $signal['note'] }}</small></div>
                            <span class="role-badge role-badge--{{ $signal['tone'] }}">{{ number_format($signal['value']) }}</span>
                        </a>
                    @endforeach
                </div>
            </article>

            <article class="role-panel">
                <div class="role-panel__header">
                    <h2 class="role-panel__title">⚖ Governance Intelligence Pulse</h2>
                    <a class="role-panel__link" href="{{ route('governance-control.dashboard') }}">Governance Control →</a>
                </div>
                <div class="role-panel__body role-status-list">
                    @foreach ($governanceDecisionSignals as $signal)
                        <a class="role-status-row" href="{{ route($signal['route']) }}" style="text-decoration:none;color:inherit">
                            <div><strong>{{ $signal['label'] }}</strong><small>Open the governed register for evidence and action.</small></div>
                            <span class="role-badge role-badge--{{ $signal['tone'] }}">{{ number_format($signal['value']) }}</span>
                        </a>
                    @endforeach
                </div>
            </article>

            @if ($utilityDecisionSupport['available'] && \App\Services\FeatureToggleService::enabled('utility_bills'))
                <article class="role-panel">
                    <div class="role-panel__header">
                        <h2 class="role-panel__title">💡 Utility Payment Control</h2>
                        <a class="role-panel__link" href="{{ route('utility-bills.index') }}">Monitor Bills →</a>
                    </div>
                    <div class="role-panel__body role-status-list">
                        <div class="role-status-row"><div><strong>Overdue bills</strong><small>Past due and not fully paid.</small></div><div class="role-status-value">{{ number_format($utilityDecisionSupport['overdue_count']) }}</div></div>
                        <div class="role-status-row"><div><strong>Due within 7 days</strong><small>Upcoming payment obligations.</small></div><div class="role-status-value">{{ number_format($utilityDecisionSupport['due_soon_count']) }}</div></div>
                        <div class="role-status-row"><div><strong>Total outstanding</strong><small>Current unpaid / partly paid balance.</small></div><div class="role-status-value">LKR {{ number_format($utilityDecisionSupport['outstanding'], 2) }}</div></div>
                        <div class="role-status-row"><div><strong>Paid this month</strong><small>Payments recorded in the current month.</small></div><div class="role-status-value">LKR {{ number_format($utilityDecisionSupport['paid_this_month'], 2) }}</div></div>
                    </div>
                </article>
            @endif
        </section>

        <section class="role-panel" style="margin-bottom:16px">
            <div class="role-panel__header">
                <h2 class="role-panel__title">⚡ Administrative Decision Workspaces</h2>
                <span class="role-badge role-badge--info">Aggregate-first</span>
            </div>
            <div class="role-panel__body role-quick-actions">
                @foreach ($administrativeQuickLinks as $link)
                    @if ($link['route'] !== 'utility-bills.index' || \App\Services\FeatureToggleService::enabled('utility_bills'))
                        <a class="role-quick-action" href="{{ route($link['route']) }}"><span class="role-quick-action__icon">{{ $link['icon'] }}</span>{{ $link['label'] }}</a>
                    @endif
                @endforeach
            </div>
        </section>

        <section class="role-dashboard-grid">
            <article class="role-panel">
                <div class="role-panel__header">
                    <h2 class="role-panel__title">☷ Administrative Decision Queue</h2>
                    <a class="role-panel__link" href="{{ route('administrative-decisions.index') }}">View Queue →</a>
                </div>
                <div class="role-panel__body" style="padding:0;">
                    <table class="role-mini-table">
                        <thead>
                            <tr>
                                <th>Decision Type</th>
                                <th>Pending</th>
                                <th>Control</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($decisionSummary as $row)
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td>{{ $row['total'] }}</td>
                                    <td>
                                        <span class="role-badge role-badge--warning">Human approval</span>
                                    </td>
                            </tr>@empty<tr>
                                    <td colspan="3">No pending administrative decisions.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="role-privacy-note">Employee identities are intentionally not displayed on this dashboard. Open
                    the governed decision workflow only when authorised to review a specific request.</div>
            </article>
            <article class="role-panel">
                <div class="role-panel__header">
                    <h2 class="role-panel__title">🛡 Governance & Legal Safeguards</h2>
                    <a class="role-panel__link" href="{{ route('governance.index') }}">View Details →</a>
                </div>
                <div class="role-panel__body role-status-list">
                    <div class="role-status-row">
                        <div>
                            <strong>Business Rule Register</strong>
                            <small>Active source-verified rules.</small>
                        </div>
                        <div class="role-status-value">{{ $verifiedRules }}/{{ $activeRules }}</div>
                    </div>
                    <div class="role-status-row">
                        <div>
                            <strong>Source verification</strong>
                            <small>Institution-recorded verification coverage.</small>
                        </div>
                        <div class="role-status-value">{{ $ruleVerificationPct }}%</div>
                    </div>
                    <div class="role-status-row">
                        <div>
                            <strong>Independent human approval</strong>
                            <small>Requester cannot approve their own request.</small>
                        </div>
                        <span class="role-badge role-badge--success">Required</span>
                    </div>
                    <div class="role-status-row">
                        <div>
                            <strong>AI advisory-only</strong>
                            <small>No final HR decision authority.</small>
                        </div>
                        <span class="role-badge role-badge--success">Active</span>
                    </div>
                    <div class="role-status-row">
                        <div>
                            <strong>Incident & correction register</strong>
                            <small>Traceable investigation and correction history.</small>
                        </div>
                        <span class="role-badge role-badge--success">Enabled</span>
                    </div>
                </div>
            </article>
            <article class="role-panel">
                <div class="role-panel__header">
                    <h2 class="role-panel__title">⚠ Incident Severity Overview</h2>
                    <a class="role-panel__link" href="{{ route('incidents.index') }}">Incident Register →</a>
                </div>
                <div class="role-panel__body role-status-list">
                    @forelse ($incidentSummary as $severity => $count)
                        <div class="role-status-row">
                            <div>
                                <strong>{{ ucfirst($severity) }} severity</strong>
                                <small>Open / non-closed incidents.</small>
                            </div>
                            <div class="role-status-value">{{ $count }}</div>
                    </div>@empty<div class="role-status-row">
                            <div>
                                <strong>No open incident severity data</strong>
                                <small>The incident register has no active records.</small>
                            </div>
                            <span class="role-badge role-badge--success">Clear</span>
                        </div>
                    @endforelse
                </div>
            </article>
        </section>
        <section class="role-dashboard-grid role-dashboard-grid--bottom">
            <article class="role-panel">
                <div class="role-panel__header">
                    <h2 class="role-panel__title">▥ Administrative Control Summary</h2>
                </div>
                <div class="role-panel__body role-status-list">
                    <div class="role-status-row">
                        <div>
                            <strong>Pending decisions</strong>
                            <small>Consequential HR requests awaiting decision.</small>
                        </div>
                        <div class="role-status-value">{{ $pendingDecisions }}</div>
                    </div>
                    <div class="role-status-row">
                        <div>
                            <strong>Pending service letters</strong>
                            <small>Awaiting approval in formal workflow.</small>
                        </div>
                        <div class="role-status-value">{{ $serviceLettersPending }}</div>
                    </div>
                    <div class="role-status-row">
                        <div>
                            <strong>Open serious incidents</strong>
                            <small>High / critical severity.</small>
                        </div>
                        <div class="role-status-value">{{ $seriousIncidents }}</div>
                    </div>
                    <div class="role-status-row">
                        <div>
                            <strong>Rules due review</strong>
                            <small>Review official source references.</small>
                        </div>
                        <div class="role-status-value">{{ $rulesDueForReview }}</div>
                    </div>
                </div>
            </article>
            <article class="role-panel">
                <div class="role-panel__header">
                    <h2 class="role-panel__title">ℹ Decision Checklist</h2>
                </div>
                <div class="role-panel__body role-action-list">
                    <div class="role-action-row">
                        <div>
                            <strong>Verify the underlying employee record</strong>
                            <small>Use authoritative service and personnel records.</small>
                        </div>
                        <span class="role-badge role-badge--info">Required</span>
                    </div>
                    <div class="role-action-row">
                        <div>
                            <strong>Verify applicable authority</strong>
                            <small>Check circulars, Establishments Code, service minutes and PSC decisions.</small>
                        </div>
                        <span class="role-badge role-badge--info">Required</span>
                    </div>
                    <div class="role-action-row">
                        <div>
                            <strong>Record decision reasons</strong>
                            <small>Reason becomes part of the audit and decision history.</small>
                        </div>
                        <span class="role-badge role-badge--info">Required</span>
                    </div>
                    <div class="role-action-row">
                        <div>
                            <strong>Use incident correction workflow when needed</strong>
                            <small>Do not silently overwrite material errors.</small>
                        </div>
                        <span class="role-badge role-badge--success">Traceable</span>
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
                    <a class="role-quick-action" href="{{ route('service-letters.index') }}">
                        <span class="role-quick-action__icon">✉</span>Service Letters</a>
                    <a class="role-quick-action role-quick-action--warning" href="{{ route('governance.index') }}">
                        <span class="role-quick-action__icon">⚖</span>Governance</a>
                    <a class="role-quick-action role-quick-action--danger" href="{{ route('incidents.create') }}">
                        <span class="role-quick-action__icon">!</span>Report Incident</a>
                </div>
            </article>
        </section>
    </div>
@endsection
