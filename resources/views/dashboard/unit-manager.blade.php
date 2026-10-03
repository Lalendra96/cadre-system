@extends('layouts.app')

@section('title', 'Unit Decision Support')

@section('content')
    <div class="page-shell role-dashboard">
        @include('dashboard._role-header', [
            'title' => 'Unit Decision Support',
            'subtitle' => 'Aggregate operational signals for the unit(s) assigned to your account.',
            'asAt' => $asAt,
        ])

        @include('dashboard._decision-support-notice')

        <section class="md-card md-card--outlined"
            style="padding:16px;margin-bottom:18px;border-left:4px solid var(--md-primary);">
            <div class="md-title-md">Your decision-support scope</div>
            @if ($units->isEmpty())
                <p style="margin-bottom:0;">No unit has been assigned to this account. Ask the Super Admin to assign your
                    responsible unit before relying on this dashboard.</p>
            @else
                <p style="margin:6px 0 10px;">These indicators are limited to the following assigned unit(s):</p>
                <div style="display:flex;flex-wrap:wrap;gap:8px;">
                    @foreach ($units as $unit)
                        <span class="md-chip md-chip--selected">{{ $unit->code }} — {{ $unit->name }}</span>
                    @endforeach
                </div>
            @endif
            <div class="md-caption" style="margin-top:10px;">
                This scope grants aggregate decision-support visibility only. It does not grant access to employee profiles,
                NICs, service-file numbers or confidential HR documents.
            </div>
        </section>

        <section class="role-kpi-grid" aria-label="Unit decision-support indicators">
            <article class="role-kpi">
                <div class="role-kpi__top">
                    <div>
                        <div class="role-kpi__label">Active Workforce</div>
                        <div class="role-kpi__value">{{ number_format($activeEmployees) }}</div>
                    </div>
                    <div class="role-kpi__icon">👥</div>
                </div>
                <div class="role-kpi__meta">Active staff records in your assigned unit scope.</div>
            </article>

            <article class="role-kpi role-kpi--danger">
                <div class="role-kpi__top">
                    <div>
                        <div class="role-kpi__label">Unit Vacancy Gap</div>
                        <div class="role-kpi__value">{{ number_format($vacancyGap) }}</div>
                    </div>
                    <div class="role-kpi__icon">△</div>
                </div>
                <div class="role-kpi__meta">From current unit-position allocations; indicator only.</div>
            </article>

            <article class="role-kpi role-kpi--warning">
                <div class="role-kpi__top">
                    <div>
                        <div class="role-kpi__label">Retirements — 12 Months</div>
                        <div class="role-kpi__value">{{ number_format($retirements12m) }}</div>
                    </div>
                    <div class="role-kpi__icon">◷</div>
                </div>
                <div class="role-kpi__meta">Projected from recorded DOB and retirement-age rules.</div>
            </article>

            <article class="role-kpi role-kpi--warning">
                <div class="role-kpi__top">
                    <div>
                        <div class="role-kpi__label">Increments — 30 Days</div>
                        <div class="role-kpi__value">{{ number_format($increments30) }}</div>
                    </div>
                    <div class="role-kpi__icon">↑</div>
                </div>
                <div class="role-kpi__meta">Upcoming increment records requiring timely administrative follow-up.</div>
            </article>

            <article class="role-kpi role-kpi--danger">
                <div class="role-kpi__top">
                    <div>
                        <div class="role-kpi__label">Open Data Quality Issues</div>
                        <div class="role-kpi__value">{{ number_format($qualityOpen) }}</div>
                    </div>
                    <div class="role-kpi__icon">!</div>
                </div>
                <div class="role-kpi__meta">Resolve or escalate before relying on affected indicators.</div>
            </article>

            <article class="role-kpi role-kpi--purple">
                <div class="role-kpi__top">
                    <div>
                        <div class="role-kpi__label">Registration Expiry — 90 Days</div>
                        <div class="role-kpi__value">{{ number_format($registrationExpiry90) }}</div>
                    </div>
                    <div class="role-kpi__icon">◇</div>
                </div>
                <div class="role-kpi__meta">Aggregate professional-registration expiry watch.</div>
            </article>
        </section>

        <section class="role-dashboard-grid">
            <article class="role-panel">
                <div class="role-panel__header">
                    <h2 class="role-panel__title">☑ Decision Attention Centre</h2>
                </div>
                <div class="role-panel__body role-action-list">
                    <div class="role-action-row">
                        <div>
                            <strong>Staffing pressure</strong>
                            <small>Compare allocated posts with current in-post figures and raise staffing needs through the
                                formal planning workflow.</small>
                        </div>
                        <span
                            class="role-badge {{ $vacancyGap > 0 ? 'role-badge--danger' : 'role-badge--success' }}">{{ number_format($vacancyGap) }}
                            gap</span>
                    </div>
                    <div class="role-action-row">
                        <div>
                            <strong>Succession / retirement exposure</strong>
                            <small>Use the retirement horizon as an early-warning signal, then verify official service
                                records before action.</small>
                        </div>
                        <span class="role-badge role-badge--warning">{{ number_format($retirements12m) }}</span>
                    </div>
                    <div class="role-action-row">
                        <div>
                            <strong>Data reliability</strong>
                            <small>Open quality issues and incomplete core records can distort staffing decisions.</small>
                        </div>
                        <span
                            class="role-badge role-badge--danger">{{ number_format($qualityOpen + $missingCoreData) }}</span>
                    </div>
                    <div class="role-action-row">
                        <div>
                            <strong>Recent workforce movement</strong>
                            <small>Net movement over the last 90 days helps identify growing or shrinking staffing
                                pressure.</small>
                        </div>
                        <span
                            class="role-badge role-badge--info">{{ $netTransfer90 >= 0 ? '+' : '' }}{{ number_format($netTransfer90) }}</span>
                    </div>
                </div>
            </article>

            <article class="role-panel">
                <div class="role-panel__header">
                    <h2 class="role-panel__title">▥ Unit Allocation Summary</h2>
                </div>
                <div class="role-panel__body role-status-list">
                    <div class="role-status-row">
                        <div><strong>Allocated posts</strong><small>Configured unit-position allocation for
                                {{ $asAt->year }}.</small></div>
                        <div class="role-status-value">{{ number_format($allocatedPosts) }}</div>
                    </div>
                    <div class="role-status-row">
                        <div><strong>Actual in post</strong><small>Recorded current unit allocation figure.</small></div>
                        <div class="role-status-value">{{ number_format($actualInPost) }}</div>
                    </div>
                    <div class="role-status-row">
                        <div><strong>Allocation fill rate</strong><small>System calculation, not an employment
                                determination.</small></div>
                        <div class="role-status-value">{{ number_format($allocationFillRate, 1) }}%</div>
                    </div>
                    <div class="role-status-row">
                        <div><strong>Transfers in — 90 days</strong><small>Scoped movement count.</small></div>
                        <div class="role-status-value">{{ number_format($transferIn90) }}</div>
                    </div>
                    <div class="role-status-row">
                        <div><strong>Transfers out — 90 days</strong><small>Scoped movement count.</small></div>
                        <div class="role-status-value">{{ number_format($transferOut90) }}</div>
                    </div>
                </div>
            </article>

            <article class="role-panel">
                <div class="role-panel__header">
                    <h2 class="role-panel__title">🛡 Governance & Legal Safeguards</h2>
                </div>
                <div class="role-panel__body role-status-list">
                    <div class="role-status-row">
                        <div><strong>Aggregate-only display</strong><small>No employee names or direct profile links are
                                shown here.</small></div>
                        <span class="role-badge role-badge--success">Enforced</span>
                    </div>
                    <div class="role-status-row">
                        <div><strong>Need-to-know unit scope</strong><small>Counts are limited to units assigned by the
                                Super Admin.</small></div>
                        <span class="role-badge role-badge--success">Enforced</span>
                    </div>
                    <div class="role-status-row">
                        <div><strong>Human review required</strong><small>Dashboard indicators cannot execute transfers,
                                appointments, disciplinary or other consequential HR actions.</small></div>
                        <span class="role-badge role-badge--success">Required</span>
                    </div>
                    <div class="role-status-row">
                        <div><strong>Source-record verification</strong><small>Verify official establishment, service and
                                circular records before formal decisions.</small></div>
                        <span class="role-badge role-badge--warning">Officer duty</span>
                    </div>
                </div>
            </article>
        </section>

        <section class="role-dashboard-grid role-dashboard-grid--bottom">
            <article class="role-panel">
                <div class="role-panel__header">
                    <h2 class="role-panel__title">▦ Largest Unit Staffing Gaps</h2>
                </div>
                <div class="role-panel__body" style="padding:0;">
                    <table class="role-mini-table">
                        <thead>
                            <tr>
                                <th>Unit</th>
                                <th>Position</th>
                                <th>Allocated</th>
                                <th>In Post</th>
                                <th>Gap</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($topVacancyRows as $row)
                                <tr>
                                    <td>{{ $row['unit'] }}</td>
                                    <td>{{ $row['position'] }}</td>
                                    <td>{{ number_format($row['allocated']) }}</td>
                                    <td>{{ number_format($row['actual']) }}</td>
                                    <td><span
                                            class="role-badge {{ $row['gap'] > 0 ? 'role-badge--danger' : 'role-badge--success' }}">{{ number_format($row['gap']) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">No unit-position allocation rows are available for
                                        {{ $asAt->year }}.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @include('dashboard._privacy-note')
            </article>

            <article class="role-panel">
                <div class="role-panel__header">
                    <h2 class="role-panel__title">▥ Workforce Mix by Position</h2>
                </div>
                <div class="role-panel__body role-status-list">
                    @forelse ($positionMix as $row)
                        <div class="role-status-row">
                            <div><strong>{{ $row['label'] }}</strong><small>Aggregate active headcount.</small></div>
                            <div class="role-status-value">{{ number_format($row['total']) }}</div>
                        </div>
                    @empty
                        <p>No workforce data is available in the assigned unit scope.</p>
                    @endforelse
                </div>
            </article>

            <article class="role-panel">
                <div class="role-panel__header">
                    <h2 class="role-panel__title">⚡ What to do with these signals</h2>
                </div>
                <div class="role-panel__body role-action-list">
                    <div class="role-action-row">
                        <div><strong>Vacancy or workload concern</strong><small>Escalate to Planning / Administrative
                                Officer with the unit evidence and official establishment reference.</small></div>
                    </div>
                    <div class="role-action-row">
                        <div><strong>Incorrect figures</strong><small>Use the incident/correction workflow rather than
                                changing an official figure informally.</small></div>
                    </div>
                    <div class="role-action-row">
                        <div><strong>Employee-specific action needed</strong><small>Refer the case to the responsible
                                Subject Officer/HR officer; this dashboard intentionally does not expose personal
                                records.</small></div>
                    </div>
                </div>
            </article>
        </section>
    </div>
@endsection
