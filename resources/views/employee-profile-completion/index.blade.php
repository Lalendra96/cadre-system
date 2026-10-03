@extends('layouts.app')

@section('title', 'Employee Profile Handling Count Tracking')

@section('content')
    <style>
        .pct-shell {
            display: grid;
            gap: 16px;
        }

        .pct-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            flex-wrap: wrap;
        }

        .pct-title {
            margin: 0;
            font-size: 26px;
            font-weight: 800;
            color: var(--md-on-surface, #18344f);
        }

        .pct-subtitle {
            margin-top: 4px;
            color: var(--md-on-surface-variant, #667a8e);
            font-size: 12px;
        }

        .pct-note {
            border-left: 4px solid var(--md-primary, #1976d2);
            background: var(--md-primary-container);
            padding: 12px 14px;
            border-radius: 10px;
            color: var(--md-on-primary-container);
            font-size: 12px;
            line-height: 1.55;
        }

        .pct-cards {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .pct-card {
            background: var(--md-surface-container-low);
            border: 1px solid var(--md-outline-variant, #dbe4ec);
            border-radius: 12px;
            padding: 14px;
        }

        .pct-card small {
            color: var(--md-on-surface-variant);
            display: block;
            margin-bottom: 5px;
        }

        .pct-card strong {
            font-size: 24px;
            color: var(--md-on-surface, #18344f);
        }

        .pct-panel {
            background: var(--md-surface-container-low);
            border: 1px solid var(--md-outline-variant, #dbe4ec);
            border-radius: 14px;
            overflow: hidden;
        }

        .pct-panel-head {
            padding: 15px 16px;
            border-bottom: 1px solid var(--md-outline-variant, #dbe4ec);
        }

        .pct-panel-head h2 {
            margin: 0;
            font-size: 16px;
            color: var(--md-on-surface, #18344f);
        }

        .pct-table-wrap {
            overflow: auto;
        }

        .pct-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1050px;
        }

        .pct-table th,
        .pct-table td {
            padding: 11px 12px;
            border-bottom: 1px solid var(--md-outline-variant);
            text-align: left;
            vertical-align: top;
            font-size: 11px;
        }

        .pct-table th {
            background: var(--md-surface-container-high);
            color: var(--md-on-surface-variant);
            text-transform: uppercase;
            letter-spacing: .04em;
            font-size: 9px;
        }

        .pct-progress {
            width: 110px;
            height: 7px;
            background: var(--md-surface-container-highest);
            border-radius: 999px;
            overflow: hidden;
        }

        .pct-progress span {
            display: block;
            height: 100%;
            background: var(--md-primary, #1976d2);
            border-radius: 999px;
        }

        .pct-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 999px;
            background: var(--md-surface-container-high);
            color: var(--md-on-surface-variant);
            font-weight: 700;
            font-size: 10px;
        }

        .pct-badge--good {
            background: var(--md-success-container);
            color: var(--md-on-success-container);
        }

        .pct-badge--warn {
            background: var(--md-warning-container);
            color: var(--md-on-warning-container);
        }

        .pct-badge--danger {
            background: var(--md-error-container);
            color: var(--md-on-error-container);
        }

        .pct-target-form {
            display: grid;
            grid-template-columns: 95px 125px minmax(170px, 1fr) auto;
            gap: 6px;
            min-width: 500px;
        }

        .pct-target-form input {
            min-width: 0;
            border: 1px solid var(--md-outline);
            border-radius: 8px;
            padding: 7px 8px;
            font-size: 11px;
            background: var(--md-surface, #fff);
            color: var(--md-on-surface, #18344f);
        }

        .pct-target-form button {
            border: 0;
            border-radius: 8px;
            padding: 7px 11px;
            background: var(--md-primary, #1976d2);
            color: #fff;
            font-weight: 700;
            cursor: pointer;
        }

        .pct-breakdown {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            max-width: 420px;
        }

        .pct-breakdown span {
            display: inline-flex;
            gap: 5px;
            align-items: center;
            background: var(--md-surface-container-high);
            border: 1px solid var(--md-outline-variant);
            border-radius: 999px;
            padding: 4px 8px;
            color: var(--md-on-surface-variant);
            font-size: 10px;
        }

        .pct-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }

        .pct-export-filter {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: flex-end;
            padding: 10px;
            border: 1px solid var(--md-outline-variant);
            border-radius: 11px;
            background: var(--md-surface-container-low);
        }

        .pct-export-field {
            display: grid;
            gap: 4px;
            min-width: 240px;
        }

        .pct-export-field label {
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--md-on-surface-variant);
        }

        .pct-export-field select {
            border: 1px solid var(--md-outline);
            border-radius: 8px;
            padding: 8px 10px;
            background: var(--md-surface, #fff);
            color: var(--md-on-surface, #18344f);
            font-size: 11px;
            min-height: 35px;
        }

        .pct-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border-radius: 9px;
            padding: 9px 13px;
            font-size: 11px;
            font-weight: 800;
            text-decoration: none;
            border: 1px solid var(--md-outline);
            color: var(--md-primary);
            background: var(--md-surface-container-low);
            cursor: pointer;
            font-family: inherit;
        }

        .pct-btn--primary {
            background: var(--md-primary, #1976d2);
            color: #fff;
            border-color: var(--md-primary, #1976d2);
        }

        .pct-charts {
            display: grid;
            grid-template-columns: minmax(260px, .8fr) minmax(420px, 1.6fr);
            gap: 14px;
        }

        .pct-chart-card {
            background: var(--md-surface-container-low);
            border: 1px solid var(--md-outline-variant, #dbe4ec);
            border-radius: 12px;
            padding: 14px;
            min-width: 0;
        }

        .pct-chart-card h3 {
            margin: 0 0 3px;
            color: var(--md-on-surface, #18344f);
            font-size: 14px;
        }

        .pct-chart-card p {
            margin: 0 0 12px;
            color: var(--md-on-surface-variant);
            font-size: 10px;
        }

        .pct-chart-box {
            position: relative;
            height: 260px;
        }

        .pct-chart-card--wide {
            grid-column: 1 / -1;
        }

        .pct-chart-scroll {
            overflow-x: auto;
        }

        .pct-chart-scroll-inner {
            min-width: 720px;
            height: 300px;
            position: relative;
        }

        .pct-card,
        .pct-panel,
        .pct-chart-card,
        .pct-export-filter,
        .pct-note {
            transition: background-color .18s ease, border-color .18s ease, color .18s ease;
        }

        .pct-table tbody tr:hover {
            background: color-mix(in srgb, var(--md-primary) 7%, transparent);
        }

        @media(max-width:1000px) {
            .pct-cards {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .pct-charts {
                grid-template-columns: 1fr;
            }

            .pct-chart-card--wide {
                grid-column: auto;
            }
        }

        @media(max-width:620px) {
            .pct-cards {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="pct-shell">
        <div class="pct-head">
            <div>
                <h1 class="pct-title">Employee Profile Handling Count Tracking</h1>
                <p class="pct-subtitle">One official handling count per Subject Officer, compared with the current active
                    employee profiles in that officer's effective HR responsibility.</p>
            </div>
            <form class="pct-export-filter" method="GET" action="{{ route('employee-profile-completion.export.pdf') }}">
                @if ($canFilterExports)
                    <div class="pct-export-field">
                        <label for="completion-export-officer">Export only files handled by</label>
                        <select id="completion-export-officer" name="officer_id">
                            <option value="">All authorised Subject Officers</option>
                            @foreach ($rows as $exportOfficer)
                                <option value="{{ $exportOfficer['officer_id'] }}">{{ $exportOfficer['officer_name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @else
                    @if ($rows->count() === 1)
                        <input type="hidden" name="officer_id" value="{{ $rows->first()['officer_id'] }}">
                    @endif
                @endif
                <div class="pct-actions">
                    <button class="pct-btn" type="submit"
                        formaction="{{ route('employee-profile-completion.export.csv') }}">CSV Export</button>
                    <button class="pct-btn pct-btn--primary" type="submit"
                        formaction="{{ route('employee-profile-completion.export.pdf') }}">PDF Export</button>
                </div>
            </form>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Handling count could not be saved.</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="pct-note">
            <strong>Counting rule:</strong>
            Handling Count is entered once for the Subject Officer as the total number of employee profiles they are
            expected to handle.
            Current Profile Count is the number of active employee profiles in that officer's effective HR responsibility.
            Remaining = Handling Count − Current Profile Count. Completion % = Current Profile Count ÷ Handling Count × 100.
            Position-wise figures are shown only as a read-only breakdown of the current profiles and do not require
            separate handling counts.
        </div>

        <div class="pct-cards">
            <div class="pct-card">
                <small>Total Handling Count</small>
                <strong>{{ number_format($summary['handling_count']) }}</strong>
            </div>
            <div class="pct-card">
                <small>Current Profile Count</small>
                <strong>{{ number_format($summary['profile_count']) }}</strong>
            </div>
            <div class="pct-card">
                <small>Remaining</small>
                <strong>{{ number_format($summary['remaining_count']) }}</strong>
            </div>
            <div class="pct-card">
                <small>Completion</small>
                <strong>{{ $summary['completion_pct'] === null ? '—' : number_format($summary['completion_pct'], 1) . '%' }}</strong>
            </div>
        </div>

        <section class="pct-charts" aria-label="Profile handling progress charts">
            <div class="pct-chart-card">
                <h3>Overall Progress</h3>
                <p>Current profiles and remaining workload against the total handling count.</p>
                <div class="pct-chart-box"><canvas id="pctOverallChart"></canvas></div>
            </div>
            <div class="pct-chart-card">
                <h3>Handling Count vs Current Profiles</h3>
                <p>Side-by-side comparison for each Subject Officer in your authorised scope.</p>
                <div class="pct-chart-scroll">
                    <div class="pct-chart-scroll-inner"><canvas id="pctOfficerCountChart"></canvas></div>
                </div>
            </div>
            <div class="pct-chart-card pct-chart-card--wide">
                <h3>Completion Percentage by Subject Officer</h3>
                <p>Completion is capped at 100%. Officers without an official Handling Count are excluded from this
                    percentage chart.</p>
                <div class="pct-chart-scroll">
                    <div class="pct-chart-scroll-inner"><canvas id="pctCompletionChart"></canvas></div>
                </div>
            </div>
        </section>

        <section class="pct-panel">
            <div class="pct-panel-head">
                <h2>Subject Officer Progress</h2>
                <div class="pct-subtitle">Handling Count is officer-level. Post breakdown is calculated from current
                    profiles only.</div>
            </div>

            <div class="pct-table-wrap">
                <table class="pct-table">
                    <thead>
                        <tr>
                            <th>Subject Officer</th>
                            <th>Assigned Subject Codes</th>
                            <th>Total Handling Count</th>
                            <th>Current Profile Count</th>
                            <th>Remaining</th>
                            <th>Completion</th>
                            <th>Current Profiles by Post</th>
                            <th>Status</th>
                            @if ($canManageTargets)
                                <th>Handling Count Governance</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td><strong>{{ $row['officer_name'] }}</strong></td>
                                <td>
                                    @if (count($row['subject_codes']) > 0)
                                        {{ implode(', ', $row['subject_codes']) }}
                                    @else
                                        <span class="pct-badge">Effective HR scope</span>
                                    @endif
                                </td>
                                <td>{{ $row['handling_count'] === null ? 'Not set' : number_format($row['handling_count']) }}
                                </td>
                                <td><strong>{{ number_format($row['profile_count']) }}</strong></td>
                                <td>
                                    @if ($row['remaining_count'] === null)
                                        <span class="pct-badge">Handling count needed</span>
                                    @else
                                        <span
                                            class="pct-badge {{ $row['remaining_count'] > 0 ? 'pct-badge--danger' : 'pct-badge--good' }}">
                                            {{ number_format($row['remaining_count']) }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if ($row['completion_pct'] === null)
                                        —
                                    @else
                                        <div style="display:flex;align-items:center;gap:8px;">
                                            <div class="pct-progress">
                                                <span style="width:{{ $row['completion_pct'] }}%"></span>
                                            </div>
                                            <strong>{{ number_format($row['completion_pct'], 1) }}%</strong>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="pct-breakdown">
                                        @forelse ($row['position_breakdown'] as $position)
                                            <span title="{{ $position['position_code'] }}">
                                                {{ $position['position_title'] }}
                                                <strong>{{ number_format($position['profile_count']) }}</strong>
                                            </span>
                                        @empty
                                            <span>No current profiles</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td>
                                    @switch($row['status'])
                                        @case('completed')
                                            <span class="pct-badge pct-badge--good">Completed</span>
                                        @break

                                        @case('in_progress')
                                            <span class="pct-badge pct-badge--warn">In Progress</span>
                                        @break

                                        @case('not_started')
                                            <span class="pct-badge pct-badge--danger">Not Started</span>
                                        @break

                                        @default
                                            <span class="pct-badge">Handling Count Not Set</span>
                                    @endswitch
                                </td>
                                @if ($canManageTargets)
                                    <td>
                                        <form method="POST"
                                            action="{{ route('employee-profile-completion.targets.save') }}"
                                            class="pct-target-form">
                                            @csrf
                                            <input type="hidden" name="user_id" value="{{ $row['officer_id'] }}">
                                            <input type="number" name="target_count" min="0"
                                                value="{{ $row['handling_count'] ?? '' }}" placeholder="Total handling"
                                                required>
                                            <input type="date" name="effective_from" value="{{ now()->toDateString() }}"
                                                required>
                                            <input type="text" name="source_reference"
                                                value="{{ $row['target_source_reference'] ?? '' }}"
                                                placeholder="Source / memo reference" required>
                                            <button type="submit">Save Total</button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                            @empty
                                <tr>
                                    <td colspan="9"
                                        style="padding:28px;text-align:center;color:var(--md-on-surface-variant);">
                                        No active Subject Officers are available in your authorised scope.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof Chart === 'undefined') return;

                    const css = getComputedStyle(document.documentElement);
                    const themeColor = name => css.getPropertyValue(name).trim();
                    const chartText = themeColor('--md-on-surface-variant') || '#667a8e';
                    const chartGrid = themeColor('--md-outline-variant') || '#dbe4ec';
                    const chartPrimary = themeColor('--md-primary') || '#4E91D9';
                    const chartSuccess = themeColor('--md-success') || '#20B486';
                    const chartWarning = themeColor('--md-warning') || '#F2A93B';
                    Chart.defaults.color = chartText;
                    Chart.defaults.borderColor = chartGrid;

                    const rows = @json($rows->values());
                    const labels = rows.map(row => row.officer_name);
                    const handling = rows.map(row => Number(row.handling_count || 0));
                    const current = rows.map(row => Number(row.profile_count || 0));
                    const completionRows = rows.filter(row => row.completion_pct !== null);
                    let overallChart = null;
                    let countChart = null;
                    let completionChart = null;

                    const overallCanvas = document.getElementById('pctOverallChart');
                    if (overallCanvas) {
                        overallChart = new Chart(overallCanvas, {
                            type: 'doughnut',
                            data: {
                                labels: ['Current Profiles', 'Remaining'],
                                datasets: [{
                                    data: [{{ (int) $summary['profile_count'] }},
                                        {{ (int) $summary['remaining_count'] }}
                                    ],
                                    backgroundColor: [chartSuccess, chartWarning],
                                    borderWidth: 0,
                                    hoverOffset: 5
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                cutout: '68%',
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                        labels: {
                                            usePointStyle: true,
                                            padding: 16
                                        }
                                    }
                                }
                            }
                        });
                    }

                    const countCanvas = document.getElementById('pctOfficerCountChart');
                    if (countCanvas) {
                        countChart = new Chart(countCanvas, {
                            type: 'bar',
                            data: {
                                labels: labels,
                                datasets: [{
                                        label: 'Handling Count',
                                        data: handling,
                                        backgroundColor: chartPrimary,
                                        borderRadius: 6
                                    },
                                    {
                                        label: 'Current Profiles',
                                        data: current,
                                        backgroundColor: chartSuccess,
                                        borderRadius: 6
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            precision: 0
                                        }
                                    },
                                    x: {
                                        ticks: {
                                            maxRotation: 35,
                                            minRotation: 0
                                        }
                                    }
                                },
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                        labels: {
                                            usePointStyle: true,
                                            padding: 16
                                        }
                                    }
                                }
                            }
                        });
                    }

                    const completionCanvas = document.getElementById('pctCompletionChart');
                    if (completionCanvas) {
                        completionChart = new Chart(completionCanvas, {
                            type: 'bar',
                            data: {
                                labels: completionRows.map(row => row.officer_name),
                                datasets: [{
                                    label: 'Completion %',
                                    data: completionRows.map(row => Number(row.completion_pct || 0)),
                                    backgroundColor: chartPrimary,
                                    borderRadius: 6
                                }]
                            },
                            options: {
                                indexAxis: 'y',
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    x: {
                                        beginAtZero: true,
                                        max: 100,
                                        ticks: {
                                            callback: value => value + '%'
                                        }
                                    }
                                },
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: context => context.parsed.x.toFixed(1) + '%'
                                        }
                                    }
                                }
                            }
                        });
                    }

                    window.addEventListener('carder:theme-changed', function() {
                        const currentCss = getComputedStyle(document.documentElement);
                        Chart.defaults.color = currentCss.getPropertyValue('--md-on-surface-variant').trim();
                        Chart.defaults.borderColor = currentCss.getPropertyValue('--md-outline-variant').trim();
                        const primary = currentCss.getPropertyValue('--md-primary').trim();
                        const success = currentCss.getPropertyValue('--md-success').trim();
                        const warning = currentCss.getPropertyValue('--md-warning').trim();
                        if (overallChart) overallChart.data.datasets[0].backgroundColor = [success, warning];
                        if (countChart) {
                            countChart.data.datasets[0].backgroundColor = primary;
                            countChart.data.datasets[1].backgroundColor = success;
                        }
                        if (completionChart) completionChart.data.datasets[0].backgroundColor = primary;
                        [overallChart, countChart, completionChart].filter(Boolean).forEach(function(chart) {
                            chart.options.color = Chart.defaults.color;
                            chart.update();
                        });
                    });
                });
            </script>
        @endpush
    @endsection
