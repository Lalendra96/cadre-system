@extends('layouts.app')

@section('title', 'Career Events & Workforce Timeline')

@section('content')
    @php
        $eventMeta = [
            'promotion' => ['label' => 'Grade Promotion', 'short' => 'PROM'],
            'retirement' => ['label' => 'Retirement', 'short' => 'RET'],
            'increment' => ['label' => 'Increment', 'short' => 'INC'],
            'training_expiry' => ['label' => 'Training Expiry', 'short' => 'TRN'],
            'registration_expiry' => ['label' => 'Registration Expiry', 'short' => 'REG'],
            'transfer' => ['label' => 'Transfer', 'short' => 'TRF'],
            'interdiction' => ['label' => 'Interdiction / Hold', 'short' => 'HOLD'],
        ];

        $monthCount = max(1, $months->count());
        $timelineStart = $start->copy()->startOfDay();
        $timelineDays = max(1, $timelineStart->diffInDays($end->copy()->endOfDay()));
        $todayOffset = max(0, min(100, ($timelineStart->diffInDays(today(), false) / $timelineDays) * 100));
    @endphp

    <div class="career-timeline-page">
        <section class="career-hero">
            <div>
                <div class="career-eyebrow">Workforce planning & HR governance</div>
                <h1>Career Events & Workforce Timeline</h1>
                <p>
                    Track scheduled grade promotions, retirement projects, increments, training and
                    professional-registration
                    expiry, transfers and formal employment-status events from recorded HR data.
                </p>
            </div>
            <div class="career-hero__notice">
                <strong>Decision-support only</strong>
                <span>Verify source records and applicable service rules before consequential action.</span>
            </div>
        </section>

        @if ($identityRestricted)
            <div class="career-notice career-notice--privacy">
                <strong>Administrative aggregate view:</strong>
                Employee-level rows are intentionally aggregated by unit and position for this role. Names, identifiers and
                exact event dates are not exposed, preserving the existing aggregate-only administrative access model.
            </div>
        @endif

        <form method="GET" action="{{ route('workforce.career-events-timeline') }}" class="career-filter-panel">
            <div class="career-filter-row">
                @unless ($identityRestricted)
                    <label class="career-search">
                        <span class="sr-only">Search employees</span>
                        <input type="search" name="q" value="{{ $search }}"
                            placeholder="Search employee, NIC or unit..." autocomplete="off">
                    </label>
                @endunless

                <label class="career-month-picker">
                    <span>Timeline start</span>
                    <input type="month" name="start" value="{{ $start->format('Y-m') }}">
                </label>

                <div class="career-filter-actions">
                    <button type="submit" class="md-btn md-btn--filled">Apply</button>
                    <a href="{{ route('workforce.career-events-timeline') }}" class="md-btn md-btn--outlined">Reset</a>
                    <button type="button" class="md-btn md-btn--outlined" onclick="window.print()">Print A4</button>
                </div>
            </div>

            <div class="career-chip-row" aria-label="Event filters">
                @foreach ($eventMeta as $type => $meta)
                    <label class="career-filter-chip career-filter-chip--{{ $type }}">
                        <input type="checkbox" name="types[]" value="{{ $type }}" @checked(in_array($type, $selectedTypes, true))>
                        <span>{{ $meta['label'] }}</span>
                    </label>
                @endforeach
            </div>
        </form>

        <section class="career-summary-grid" aria-label="Timeline summary">
            <article>
                <strong>{{ number_format($summary['employees']) }}</strong>
                <span>Employees with events</span>
            </article>
            <article>
                <strong>{{ number_format($summary['events']) }}</strong>
                <span>Recorded events</span>
            </article>
            <article>
                <strong>{{ number_format($summary['due_90']) }}</strong>
                <span>Next event within 90 days</span>
            </article>
            <article>
                <strong>{{ number_format($summary['high_priority']) }}</strong>
                <span>Time-sensitive records</span>
            </article>
        </section>

        <div class="career-legend">
            @foreach ($eventMeta as $type => $meta)
                <span class="career-legend__item career-legend__item--{{ $type }}">
                    <i></i>{{ $meta['label'] }}
                </span>
            @endforeach
            <span class="career-legend__help">Markers are recorded/scheduled dates; today moves automatically.</span>
        </div>

        <section class="career-table-shell">
            <div class="career-table-scroll">
                <table class="career-table">
                    <thead>
                        <tr>
                            <th class="career-sticky career-person-col">Employee</th>
                            <th class="career-sticky career-grade-col">Current position / grade</th>
                            <th class="career-sticky career-unit-col">Unit</th>
                            <th class="career-sticky career-next-col">Next key event</th>
                            <th class="career-sticky career-risk-col">Priority</th>
                            <th class="career-timeline-col">
                                <div class="career-month-grid">
                                    @foreach ($months as $month)
                                        <span>{{ $month->format('M') }}<small>{{ $month->format('Y') }}</small></span>
                                    @endforeach
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (count($rows) === 0)
                            <tr>
                                <td colspan="6" class="career-empty">
                                    No recorded career events match this 13-month period and filter selection.
                                </td>
                            </tr>
                        @else
                            @foreach ($rows as $row)
                                <tr>
                                    <td class="career-sticky career-person-col">
                                        <strong>{{ $row['display_name'] }}</strong>
                                        <small>{{ $row['identity_subtitle'] }}</small>
                                    </td>
                                    <td class="career-sticky career-grade-col">{{ $row['grade'] }}</td>
                                    <td class="career-sticky career-unit-col">{{ $row['unit'] }}</td>
                                    <td class="career-sticky career-next-col">
                                        <strong>{{ $row['next_event']['label'] }}</strong>
                                        <small>{{ $row['next_event_display_date'] }}</small>
                                    </td>
                                    <td class="career-sticky career-risk-col">
                                        <span
                                            class="career-risk career-risk--{{ $row['risk'] }}">{{ ucfirst($row['risk']) }}</span>
                                    </td>
                                    <td class="career-timeline-col">
                                        <div class="career-track">
                                            @if ($todayOffset !== null)
                                                <span class="career-today-line" style="left: {{ $todayOffset }}%;">
                                                    <em>Today</em>
                                                </span>
                                            @endif

                                            @foreach ($row['events'] as $event)
                                                <button type="button"
                                                    class="career-event career-event--{{ $event['type'] }}"
                                                    style="left: {{ $event['timeline_offset'] }}%;"
                                                    title="{{ $event['tooltip'] }}"
                                                    aria-label="{{ $event['aria_label'] }}">
                                                    <span>{{ $event['short'] }}{{ $event['count_badge'] }}</span>
                                                    <small>{{ $event['short_date'] }}</small>
                                                </button>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </section>

        <section class="career-governance-grid">
            <article><strong>Role-based access</strong><span>Visibility follows the user's authorised workforce
                    scope.</span></article>
            <article><strong>Source traceability</strong><span>Markers are derived from recorded HR workflow data, not
                    inferred decisions.</span></article>
            <article><strong>Data minimisation</strong><span>Administrative planning view masks employee identity by
                    design.</span></article>
            <article><strong>No automatic decisions</strong><span>Priority indicates time proximity only; it does not
                    determine entitlement or fitness.</span></article>
            <article><strong>Official records prevail</strong><span>Users must verify service minutes, approvals and
                    supporting documents.</span></article>
            <article><strong>Print confidentiality</strong><span>Printed/exported material remains subject to the user's
                    handling obligations.</span></article>
        </section>
    </div>

    <style>
        .career-timeline-page {
            --ct-bg: #f5f8fc;
            --ct-panel: var(--md-surface, #ffffff);
            --ct-border: rgba(30, 64, 110, 0.16);
            --ct-text: var(--md-on-surface, #172b4d);
            --ct-muted: #60728d;
            --ct-blue: #1685e5;
            --ct-orange: #ff9a3c;
            --ct-green: #2ebf82;
            --ct-yellow: #e6c800;
            --ct-red: #e85151;
            --ct-purple: #8458d8;
            --ct-grey: #7e91ac;
            color: var(--ct-text);
        }

        .career-hero,
        .career-filter-panel,
        .career-summary-grid article,
        .career-legend,
        .career-table-shell,
        .career-governance-grid article,
        .career-notice {
            border: 1px solid var(--ct-border);
            border-radius: 16px;
            background: var(--ct-panel);
            box-shadow: 0 6px 18px rgba(29, 61, 101, 0.06);
        }

        .career-hero {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            padding: 22px 24px;
            margin-bottom: 14px;
        }

        .career-eyebrow {
            text-transform: uppercase;
            letter-spacing: .08em;
            font-size: 11px;
            font-weight: 800;
            color: var(--md-primary, #1f6fb2);
        }

        .career-hero h1 {
            margin: 6px 0 4px;
            font-size: clamp(24px, 2.5vw, 34px);
        }

        .career-hero p,
        .career-hero__notice span,
        .career-governance-grid span,
        .career-notice {
            color: var(--ct-muted);
        }

        .career-hero__notice {
            max-width: 360px;
            padding: 13px 16px;
            border-radius: 12px;
            background: rgba(33, 150, 243, .08);
            align-self: center;
        }

        .career-hero__notice strong,
        .career-hero__notice span {
            display: block;
        }

        .career-notice {
            padding: 12px 16px;
            margin-bottom: 14px;
        }

        .career-notice--privacy {
            border-left: 4px solid #7755c7;
        }

        .career-filter-panel {
            padding: 14px 16px;
            margin-bottom: 14px;
        }

        .career-filter-row {
            display: flex;
            align-items: end;
            flex-wrap: wrap;
            gap: 12px;
        }

        .career-search {
            flex: 1 1 280px;
        }

        .career-search input,
        .career-month-picker input {
            width: 100%;
            min-height: 42px;
            border: 1px solid var(--ct-border);
            border-radius: 12px;
            padding: 9px 12px;
            background: var(--ct-panel);
            color: var(--ct-text);
        }

        .career-month-picker {
            min-width: 170px;
        }

        .career-month-picker span {
            display: block;
            margin-bottom: 4px;
            font-size: 11px;
            font-weight: 700;
            color: var(--ct-muted);
        }

        .career-filter-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .career-chip-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }

        .career-filter-chip {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 7px 10px;
            border: 1px solid var(--ct-border);
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .career-summary-grid,
        .career-governance-grid {
            display: grid;
            gap: 10px;
        }

        .career-summary-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
            margin-bottom: 14px;
        }

        .career-summary-grid article {
            padding: 14px 16px;
        }

        .career-summary-grid strong {
            display: block;
            font-size: 24px;
        }

        .career-summary-grid span {
            font-size: 12px;
            color: var(--ct-muted);
        }

        .career-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 12px 18px;
            align-items: center;
            padding: 11px 14px;
            margin-bottom: 10px;
        }

        .career-legend__item {
            display: inline-flex;
            gap: 6px;
            align-items: center;
            font-size: 11px;
            font-weight: 700;
        }

        .career-legend__item i {
            width: 11px;
            height: 11px;
            border-radius: 3px;
        }

        .career-legend__item--promotion i,
        .career-event--promotion {
            background: var(--ct-blue);
        }

        .career-legend__item--retirement i,
        .career-event--retirement {
            background: var(--ct-orange);
        }

        .career-legend__item--increment i,
        .career-event--increment {
            background: var(--ct-green);
        }

        .career-legend__item--training_expiry i,
        .career-event--training_expiry {
            background: var(--ct-yellow);
        }

        .career-legend__item--registration_expiry i,
        .career-event--registration_expiry {
            background: var(--ct-red);
        }

        .career-legend__item--transfer i,
        .career-event--transfer {
            background: var(--ct-purple);
        }

        .career-legend__item--interdiction i,
        .career-event--interdiction {
            background: var(--ct-grey);
        }

        .career-legend__help {
            margin-left: auto;
            font-size: 11px;
            color: var(--ct-muted);
        }

        .career-table-shell {
            overflow: hidden;
        }

        .career-table-scroll {
            overflow-x: auto;
        }

        .career-table {
            border-collapse: separate;
            border-spacing: 0;
            min-width: 1480px;
            width: 100%;
            font-size: 12px;
        }

        .career-table th,
        .career-table td {
            padding: 10px;
            border-right: 1px solid var(--ct-border);
            border-bottom: 1px solid var(--ct-border);
            vertical-align: middle;
            background: var(--ct-panel);
        }

        .career-table thead th {
            position: sticky;
            top: 0;
            z-index: 8;
            font-weight: 800;
        }

        .career-sticky {
            position: sticky;
            z-index: 5;
        }

        .career-person-col {
            left: 0;
            width: 190px;
            min-width: 190px;
        }

        .career-grade-col {
            left: 190px;
            width: 160px;
            min-width: 160px;
        }

        .career-unit-col {
            left: 350px;
            width: 160px;
            min-width: 160px;
        }

        .career-next-col {
            left: 510px;
            width: 160px;
            min-width: 160px;
        }

        .career-risk-col {
            left: 670px;
            width: 90px;
            min-width: 90px;
        }

        .career-timeline-col {
            min-width: 720px;
            padding: 0 !important;
        }

        .career-table td strong,
        .career-table td small {
            display: block;
        }

        .career-table td small {
            margin-top: 3px;
            color: var(--ct-muted);
        }

        .career-month-grid {
            display: grid;
            grid-template-columns: repeat({{ $monthCount }}, minmax(54px, 1fr));
            min-height: 44px;
        }

        .career-month-grid span {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            border-right: 1px solid var(--ct-border);
        }

        .career-month-grid small {
            font-size: 9px;
            color: var(--ct-muted);
        }

        .career-track {
            position: relative;
            height: 72px;
            background-image: linear-gradient(to right, var(--ct-border) 1px, transparent 1px);
            background-size: calc(100% / {{ $monthCount }}) 100%;
        }

        .career-today-line {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #248cff;
            z-index: 2;
        }

        .career-today-line em {
            position: absolute;
            top: 2px;
            left: 4px;
            padding: 2px 5px;
            border-radius: 6px;
            background: #248cff;
            color: white;
            font-size: 9px;
            font-style: normal;
        }

        .career-event {
            position: absolute;
            top: 21px;
            transform: translateX(-50%);
            min-width: 42px;
            border: 0;
            border-radius: 8px;
            padding: 4px 6px;
            color: #fff;
            cursor: help;
            box-shadow: 0 4px 10px rgba(0, 0, 0, .14);
            z-index: 3;
        }

        .career-event span,
        .career-event small {
            display: block;
            color: inherit;
            white-space: nowrap;
            font-weight: 800;
            line-height: 1.15;
        }

        .career-event small {
            font-size: 8px;
            opacity: .92;
        }

        .career-event--training_expiry {
            color: #293446;
        }

        .career-risk {
            display: inline-flex;
            justify-content: center;
            min-width: 64px;
            padding: 5px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 800;
        }

        .career-risk--high {
            background: rgba(218, 54, 51, .13);
            color: #b42318;
        }

        .career-risk--medium {
            background: rgba(255, 159, 28, .16);
            color: #9a5b00;
        }

        .career-risk--low {
            background: rgba(31, 157, 104, .13);
            color: #14734d;
        }

        .career-empty {
            padding: 36px !important;
            text-align: center;
            color: var(--ct-muted);
        }

        .career-governance-grid {
            grid-template-columns: repeat(6, minmax(0, 1fr));
            margin-top: 12px;
        }

        .career-governance-grid article {
            padding: 12px;
        }

        .career-governance-grid strong,
        .career-governance-grid span {
            display: block;
        }

        .career-governance-grid strong {
            margin-bottom: 4px;
            font-size: 11px;
        }

        .career-governance-grid span {
            font-size: 10px;
            line-height: 1.45;
        }

        @media (max-width: 1100px) {
            .career-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .career-governance-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 720px) {
            .career-hero {
                flex-direction: column;
            }

            .career-summary-grid,
            .career-governance-grid {
                grid-template-columns: 1fr;
            }
        }

        @media print {

            .career-filter-panel,
            .career-filter-actions,
            .career-hero__notice {
                display: none !important;
            }

            .career-table-scroll {
                overflow: visible;
            }

            .career-table {
                min-width: 0;
                font-size: 8px;
            }

            .career-sticky,
            .career-table thead th {
                position: static;
            }
        }
    </style>
@endsection
