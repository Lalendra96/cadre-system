@extends('layouts.app')

@section('title', 'Governance Intelligence')

@section('content')
    @php
        $urgentFindings = $findings
            ->filter(function ($finding) {
                return in_array($finding->status, ['due', 'overdue'], true);
            })
            ->count();

        $upcomingFindings = $findings
            ->filter(function ($finding) {
                return !in_array($finding->status, ['due', 'overdue'], true);
            })
            ->count();

        $forecastMovementTotal =
            (int) $forecast['confirmed_retirements'] +
            (int) $forecast['transfers_out'] +
            (int) $forecast['transfers_in'];
    @endphp

    <div class="governance-page">
        <header class="governance-page__header governance-animate governance-animate--1">
            <div class="governance-page__heading">
                <div class="governance-page__eyebrow">Governance &amp; Workforce Intelligence</div>
                <h1>Administrative decision-support workspace</h1>
                <p>
                    Review administrative milestones, establishment changes, historical state,
                    authoritative evidence and Ministry reconciliation from one controlled workspace.
                </p>
            </div>

            <div class="governance-decision-note" role="note" aria-label="Human decision safeguard">
                <span class="governance-decision-note__icon" aria-hidden="true">✓</span>
                <div>
                    <strong>Human decision required</strong>
                    <span>
                        Findings support officers. The system does not automatically confirm,
                        promote, retire, transfer or approve an employee.
                    </span>
                </div>
            </div>
        </header>

        @if (session('success'))
            <div class="governance-alert governance-alert--success" role="status">
                <strong>Completed.</strong>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="governance-alert governance-alert--danger" role="alert">
                <strong>Please review the highlighted information.</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="governance-stat-grid governance-animate governance-animate--2" aria-label="Governance summary">
            <article class="governance-stat-card">
                <span class="governance-stat-card__label">Rules in force</span>
                <strong>{{ number_format($metrics['rules']) }}</strong>
                <small>Effective-dated versions</small>
            </article>

            <article class="governance-stat-card {{ $urgentFindings > 0 ? 'governance-stat-card--attention' : '' }}">
                <span class="governance-stat-card__label">Needs attention</span>
                <strong>{{ number_format($urgentFindings) }}</strong>
                <small>{{ number_format($upcomingFindings) }} additional upcoming findings</small>
            </article>

            <article class="governance-stat-card">
                <span class="governance-stat-card__label">HRMIS differences</span>
                <strong>{{ number_format($metrics['reconciliation']) }}</strong>
                <small>Awaiting officer reconciliation</small>
            </article>

            <article
                class="governance-stat-card {{ $metrics['missing_documents'] > 0 ? 'governance-stat-card--attention' : '' }}">
                <span class="governance-stat-card__label">Missing evidence</span>
                <strong>{{ number_format($metrics['missing_documents']) }}</strong>
                <small>Lifecycle document controls</small>
            </article>

            <article class="governance-stat-card">
                <span class="governance-stat-card__label">Field provenance</span>
                <strong>{{ number_format($metrics['provenance']) }}</strong>
                <small>Authoritative value origins</small>
            </article>

            <article class="governance-stat-card">
                <span class="governance-stat-card__label">Case bundles</span>
                <strong>{{ number_format($metrics['case_bundles']) }}</strong>
                <small>Evidence packages generated</small>
            </article>
        </section>

        <div class="governance-layout governance-animate governance-animate--3">
            <main class="governance-main">
                <section class="governance-card governance-card--priority" id="eligibility">
                    @include('partials.section-help', ['topic' => 'eligibility'])
                    <div class="governance-card__header">
                        <div>
                            <span class="governance-section-kicker">Administrative intelligence</span>
                            <h2>Eligibility &amp; milestone review</h2>
                            <p>
                                Upcoming and overdue administrative events derived from recorded service facts.
                            </p>
                        </div>

                        <form method="POST" action="{{ route('advanced-governance.eligibility.refresh') }}">
                            @csrf
                            <button class="governance-btn governance-btn--primary" type="submit">
                                <span aria-hidden="true">↻</span>
                                Run evaluation
                            </button>
                        </form>
                    </div>

                    <div class="governance-eligibility-summary">
                        <div>
                            <span>Open findings</span>
                            <strong>{{ number_format($metrics['findings']) }}</strong>
                        </div>
                        <div>
                            <span>Needs attention</span>
                            <strong>{{ number_format($urgentFindings) }}</strong>
                        </div>
                        <div>
                            <span>Upcoming</span>
                            <strong>{{ number_format($upcomingFindings) }}</strong>
                        </div>
                        <div>
                            <span>Decision mode</span>
                            <strong class="governance-text-status">Officer review</strong>
                        </div>
                    </div>

                    <div class="governance-table-wrap">
                        <table class="governance-table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Administrative event</th>
                                    <th>Target date</th>
                                    <th>Status</th>
                                    <th>Why it was flagged</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($findings as $finding)
                                    <tr>
                                        <td>
                                            <div class="governance-person">
                                                <span class="governance-person__avatar" aria-hidden="true">
                                                    {{ strtoupper(mb_substr($finding->employee_name, 0, 1)) }}
                                                </span>
                                                <div>
                                                    <strong>{{ $finding->employee_name }}</strong>
                                                    <small>{{ $finding->pay_no ?: 'No pay number' }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ ucwords(str_replace('_', ' ', $finding->finding_type)) }}</td>
                                        <td>{{ $finding->target_date ?: '—' }}</td>
                                        <td>
                                            @php
                                                $statusClass = in_array($finding->status, ['due', 'overdue'], true)
                                                    ? 'governance-badge--warning'
                                                    : 'governance-badge--info';
                                            @endphp
                                            <span class="governance-badge {{ $statusClass }}">
                                                {{ ucwords(str_replace('_', ' ', $finding->status)) }}
                                            </span>
                                        </td>
                                        <td class="governance-table__explanation">{{ $finding->explanation }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5">
                                            <div class="governance-empty">
                                                <strong>No findings are currently displayed.</strong>
                                                <span>Run the evaluation to refresh administrative intelligence.</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="governance-card" id="forecast">
                    <div class="governance-card__header">
                        <div>
                            <span class="governance-section-kicker">Establishment planning</span>
                            <h2>Workforce forecast</h2>
                            <p>Known, confirmed movements are projected separately from assumptions.</p>
                        </div>

                        @include('partials.section-help', ['topic' => 'forecast'])
                        <form method="GET" action="{{ route('advanced-governance.forecast') }}"
                            class="governance-date-toolbar">
                            <label>
                                <span>From</span>
                                <input type="date" name="as_at" value="{{ $asAt->toDateString() }}" required>
                            </label>
                            <span class="governance-date-toolbar__arrow" aria-hidden="true">→</span>
                            <label>
                                <span>Until</span>
                                <input type="date" name="until" value="{{ $until->toDateString() }}"
                                    min="{{ $asAt->toDateString() }}" required>
                            </label>
                            <button class="governance-btn governance-btn--secondary" type="submit">
                                Recalculate
                            </button>
                        </form>
                    </div>

                    <div class="governance-forecast-overview">
                        <div class="governance-forecast-kpi">
                            <span>Current active</span>
                            <strong>{{ number_format($forecast['current_active']) }}</strong>
                        </div>
                        <div class="governance-forecast-kpi">
                            <span>Confirmed retirements</span>
                            <strong>{{ number_format($forecast['confirmed_retirements']) }}</strong>
                        </div>
                        <div class="governance-forecast-kpi">
                            <span>Transfers out</span>
                            <strong>{{ number_format($forecast['transfers_out']) }}</strong>
                        </div>
                        <div class="governance-forecast-kpi">
                            <span>Transfers in</span>
                            <strong>{{ number_format($forecast['transfers_in']) }}</strong>
                        </div>
                        <div class="governance-forecast-kpi governance-forecast-kpi--projected">
                            <span>Projected active</span>
                            <strong>{{ number_format($forecast['projected_active']) }}</strong>
                        </div>
                    </div>

                    <div class="governance-chart-shell">
                        <div class="governance-chart-shell__title">
                            <div>
                                <strong>Confirmed workforce movement</strong>
                                <span>{{ number_format($forecastMovementTotal) }} known movements within the selected
                                    period</span>
                            </div>
                            <span class="governance-badge governance-badge--neutral">Forecast, not decision</span>
                        </div>
                        <div class="governance-chart-shell__canvas">
                            <canvas id="governanceForecastChart" aria-label="Workforce forecast chart"></canvas>
                        </div>
                    </div>
                </section>

                <section class="governance-card" id="temporal">
                    <div class="governance-card__header">
                        <div>
                            <span class="governance-section-kicker">Historical establishment</span>
                            <h2>Organisation as at a date</h2>
                            <p>
                                Reconstruct the organisation using effective-dated events instead of today's record.
                            </p>
                        </div>
                        <span class="governance-badge governance-badge--info">
                            {{ number_format($snapshot['event_count']) }} events applied
                        </span>
                    </div>

                    @include('partials.section-help', ['topic' => 'temporal'])
                    <div class="governance-temporal-stage">
                        <div class="governance-temporal-stage__date">
                            <span>Reconstruction date</span>
                            <strong>{{ \Carbon\Carbon::parse($snapshot['date'])->format('d F Y') }}</strong>
                            <small>{{ $snapshot['coverage_note'] }}</small>
                        </div>

                        <div class="governance-temporal-track" aria-hidden="true">
                            <span class="governance-temporal-track__line"></span>
                            <span class="governance-temporal-track__node governance-temporal-track__node--past"></span>
                            <span class="governance-temporal-track__node governance-temporal-track__node--selected"></span>
                            <span class="governance-temporal-track__node governance-temporal-track__node--future"></span>
                        </div>

                        <form method="GET" action="{{ route('advanced-governance.index') }}"
                            class="governance-as-at-form">
                            <label for="governance-as-at">Show organisation as at</label>
                            <div>
                                <input id="governance-as-at" type="date" name="as_at"
                                    value="{{ $asAt->toDateString() }}" required>
                                <input type="hidden" name="until" value="{{ $until->toDateString() }}">
                                <button class="governance-btn governance-btn--primary" type="submit">
                                    Reconstruct
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="temporal-replay" data-temporal-replay>
                        <div class="temporal-replay__toolbar">
                            <strong>Recorded history</strong>
                            <button type="button" class="governance-btn governance-btn--secondary" data-replay-timeline
                                @disabled(empty($snapshot['recent_events']))>Replay timeline</button>
                        </div>
                        <p class="md-caption">Latest {{ count($snapshot['recent_events']) }} of
                            {{ number_format($snapshot['event_count']) }} events up to the selected date. Replay is visual
                            only.</p>
                        <ol class="temporal-events">
                            @forelse ($snapshot['recent_events'] as $event)
                                <li class="temporal-event" style="--event-order: {{ $loop->index }}">
                                    <time
                                        datetime="{{ $event['effective_date'] }}">{{ \Carbon\Carbon::parse($event['effective_date'])->format('d M Y') }}</time>
                                    <strong>{{ ucfirst($event['event_type']) }}</strong>
                                    <span>{{ ucfirst($event['entity_type']) }}</span>
                                </li>
                            @empty
                                <li class="temporal-empty">No recorded events are available to replay for this date.</li>
                            @endforelse
                        </ol>
                        <span class="md-caption" data-replay-status role="status" aria-live="polite"></span>
                    </div>

                    <details class="governance-disclosure">
                        <summary>
                            <span>
                                <strong>Record a historical establishment event</strong>
                                <small>Advanced administrative action</small>
                            </span>
                            <span aria-hidden="true">⌄</span>
                        </summary>
                        <div class="governance-disclosure__body">
                            <p class="governance-form-note">
                                Use this only when entering an authoritative historical event. Technical state is retained
                                by
                                the backend, while officers should enter the administrative facts and source reference.
                            </p>

                            <form method="POST" action="{{ route('advanced-governance.temporal-events.store') }}"
                                class="governance-form governance-form--two-column needs-validation" novalidate>
                                @csrf

                                <label class="governance-field">
                                    <span>Record type</span>
                                    <select name="entity_type" required>
                                        <option value="employee">Employee</option>
                                        <option value="position">Position</option>
                                        <option value="unit">Unit</option>
                                    </select>
                                </label>

                                <label class="governance-field">
                                    <span>Record ID</span>
                                    <input name="entity_id" type="number" min="1" required
                                        placeholder="e.g. 1024">
                                </label>

                                <label class="governance-field">
                                    <span>Event type</span>
                                    <select name="event_type" required>
                                        <option value="">Select event</option>
                                        <option value="appointment">Appointment</option>
                                        <option value="transfer">Transfer</option>
                                        <option value="promotion">Promotion</option>
                                        <option value="retirement">Retirement</option>
                                        <option value="correction">Authoritative correction</option>
                                        <option value="position_change">Position change</option>
                                        <option value="unit_change">Unit change</option>
                                    </select>
                                </label>

                                <label class="governance-field">
                                    <span>Effective date</span>
                                    <input name="effective_date" type="date" required>
                                </label>

                                <label class="governance-field governance-field--full">
                                    <span>Authoritative source / reference</span>
                                    <input name="source_reference" maxlength="180"
                                        placeholder="e.g. Ministry letter / appointment reference">
                                </label>

                                <label class="governance-field governance-field--full governance-field--technical">
                                    <span>Effective state payload</span>
                                    <textarea name="after_state" required placeholder='{"unit_id":12,"position_id":5}'></textarea>
                                    <small>
                                        Advanced field. It is retained for compatibility with the current temporal service.
                                    </small>
                                </label>

                                <div class="governance-form__actions governance-field--full">
                                    <button class="governance-btn governance-btn--primary" type="submit">
                                        Record authoritative event
                                    </button>
                                </div>
                            </form>
                        </div>
                    </details>
                </section>

                <section class="governance-card" id="integration">
                    @include('partials.section-help', ['topic' => 'integration'])
                    <div class="governance-card__header">
                        <div>
                            <span class="governance-section-kicker">Interoperability</span>
                            <h2>National HRMIS reconciliation</h2>
                            <p>
                                Compare Ministry-authoritative data with local records. No external value silently
                                overwrites local data.
                            </p>
                        </div>
                        <span class="governance-badge governance-badge--neutral">
                            {{ number_format($metrics['reconciliation']) }} pending
                        </span>
                    </div>

                    <form method="POST" action="{{ route('advanced-governance.hrmis.import') }}"
                        enctype="multipart/form-data" class="governance-import-bar needs-validation" novalidate>
                        @csrf

                        <label class="governance-field">
                            <span>Authoritative source</span>
                            <select name="external_hr_source_id" required>
                                <option value="">Select registered source</option>
                                @foreach ($sources as $source)
                                    <option value="{{ $source->id }}">
                                        {{ $source->name }}{{ $source->is_authoritative ? ' • authoritative' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label class="governance-field governance-field--file">
                            <span>HRMIS CSV</span>
                            <input name="file" type="file" accept=".csv,text/csv" required>
                        </label>

                        <button class="governance-btn governance-btn--secondary" type="submit">
                            Reconcile import
                        </button>
                    </form>

                    @if ($reconciliationItems->isNotEmpty())
                        <div class="governance-table-wrap governance-table-wrap--spaced">
                            <table class="governance-table governance-table--compact">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Field</th>
                                        <th>Local record</th>
                                        <th>HRMIS record</th>
                                        <th>Officer action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($reconciliationItems as $item)
                                        <tr>
                                            <td>
                                                <strong>{{ $item->employee_name ?: $item->external_identifier }}</strong>
                                            </td>
                                            <td>{{ ucwords(str_replace('_', ' ', $item->field_name)) }}</td>
                                            <td>{{ $item->local_value ?: '—' }}</td>
                                            <td>{{ $item->external_value ?: '—' }}</td>
                                            <td>
                                                <form method="POST"
                                                    action="{{ route('advanced-governance.hrmis.resolve', $item->id) }}"
                                                    class="governance-inline-review needs-validation" novalidate>
                                                    @csrf
                                                    <select name="resolution" required aria-label="Resolution">
                                                        <option value="keep_local">Keep local</option>
                                                        <option value="accept_external">Accept HRMIS</option>
                                                        <option value="defer">Defer</option>
                                                    </select>
                                                    <input name="resolution_note" required minlength="5"
                                                        maxlength="3000" placeholder="Reason for decision"
                                                        aria-label="Resolution reason">
                                                    <button class="governance-btn governance-btn--small" type="submit">
                                                        Apply
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="governance-empty governance-empty--inline">
                            <strong>No reconciliation differences are awaiting review.</strong>
                            <span>Import a Ministry HRMIS file to compare authoritative values.</span>
                        </div>
                    @endif

                    <details class="governance-disclosure governance-disclosure--subtle">
                        <summary>
                            <span>
                                <strong>Manage integration sources</strong>
                                <small>Register API, file or database authorities</small>
                            </span>
                            <span aria-hidden="true">⌄</span>
                        </summary>
                        <div class="governance-disclosure__body">
                            <form method="POST" action="{{ route('advanced-governance.sources.store') }}"
                                class="governance-form governance-form--two-column needs-validation" novalidate>
                                @csrf

                                <label class="governance-field">
                                    <span>Source code</span>
                                    <input name="code" required maxlength="60" pattern="[A-Z0-9_-]+"
                                        placeholder="MOH_HRMIS">
                                </label>

                                <label class="governance-field">
                                    <span>Source name</span>
                                    <input name="name" required maxlength="160" placeholder="Ministry HRMIS">
                                </label>

                                <label class="governance-field">
                                    <span>Connection type</span>
                                    <select name="source_type" required>
                                        <option value="">Select type</option>
                                        @foreach (['api', 'csv', 'xlsx', 'database', 'sftp'] as $type)
                                            <option value="{{ $type }}">{{ strtoupper($type) }}</option>
                                        @endforeach
                                    </select>
                                </label>

                                <label class="governance-field">
                                    <span>Authority</span>
                                    <input name="authority" maxlength="180" placeholder="Ministry division / authority">
                                </label>

                                <label class="governance-field governance-field--full">
                                    <span>API base URL <small>(optional)</small></span>
                                    <input name="base_url" type="url" maxlength="500" placeholder="https://...">
                                </label>

                                <label class="governance-checkbox governance-field--full">
                                    <input type="checkbox" name="is_authoritative" value="1">
                                    <span>
                                        <strong>Ministry-authoritative source</strong>
                                        <small>Values still require reconciliation before local acceptance.</small>
                                    </span>
                                </label>

                                <div class="governance-form__actions governance-field--full">
                                    <button class="governance-btn governance-btn--primary" type="submit">
                                        Register source
                                    </button>
                                </div>
                            </form>
                        </div>
                    </details>
                </section>
            </main>

            <aside class="governance-side">
                <section class="governance-card governance-card--compact" id="documents">
                    @include('partials.section-help', ['topic' => 'personnel_file'])
                    <div class="governance-card__header governance-card__header--compact">
                        <div>
                            <span class="governance-section-kicker">Personnel evidence</span>
                            <h2>Digital personnel file</h2>
                        </div>
                        <span class="governance-badge governance-badge--warning">
                            {{ number_format($metrics['missing_documents']) }} missing
                        </span>
                    </div>

                    @if ($missingDocuments->isNotEmpty())
                        <div class="governance-evidence-list">
                            @foreach ($missingDocuments->take(8) as $missing)
                                <article>
                                    <span class="governance-evidence-list__dot" aria-hidden="true"></span>
                                    <div>
                                        <strong>{{ $missing->employee_name }}</strong>
                                        <span>{{ $missing->requirement_label }}</span>
                                        <small>
                                            {{ ucwords(str_replace('_', ' ', $missing->event_type)) }}
                                            @if ($missing->effective_date)
                                                • {{ $missing->effective_date }}
                                            @endif
                                        </small>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="governance-empty governance-empty--compact">
                            <strong>No missing evidence detected.</strong>
                            <span>Lifecycle document intelligence is clear.</span>
                        </div>
                    @endif

                    <details class="governance-disclosure governance-disclosure--subtle">
                        <summary>
                            <span>
                                <strong>Add document requirement</strong>
                                <small>Lifecycle evidence rule</small>
                            </span>
                            <span aria-hidden="true">⌄</span>
                        </summary>
                        <div class="governance-disclosure__body">
                            <form method="POST" action="{{ route('advanced-governance.document-requirements.store') }}"
                                class="governance-form needs-validation" novalidate>
                                @csrf

                                <label class="governance-field">
                                    <span>Lifecycle event</span>
                                    <input name="lifecycle_event" required pattern="[a-z0-9_-]+" maxlength="60"
                                        placeholder="promotion">
                                </label>

                                <label class="governance-field">
                                    <span>Document category</span>
                                    <input name="document_category" required pattern="[a-z0-9_-]+" maxlength="60"
                                        placeholder="service_record">
                                </label>

                                <label class="governance-field">
                                    <span>Officer-facing label</span>
                                    <input name="label" required maxlength="180" placeholder="Verified service record">
                                </label>

                                <label class="governance-field">
                                    <span>Position code <small>(optional)</small></span>
                                    <input name="applicable_position_code" maxlength="30">
                                </label>

                                <div class="governance-field-row">
                                    <label class="governance-field">
                                        <span>Effective from</span>
                                        <input name="effective_from" type="date">
                                    </label>

                                    <label class="governance-field">
                                        <span>Effective until</span>
                                        <input name="effective_until" type="date">
                                    </label>
                                </div>

                                <label class="governance-checkbox">
                                    <input type="checkbox" name="is_mandatory" value="1" checked>
                                    <span>
                                        <strong>Mandatory evidence</strong>
                                        <small>Flag the lifecycle event when this document is missing.</small>
                                    </span>
                                </label>

                                <button class="governance-btn governance-btn--primary" type="submit">
                                    Add requirement
                                </button>
                            </form>
                        </div>
                    </details>
                </section>

                <section class="governance-card governance-card--compact" id="bundles">
                    @include('partials.section-help', ['topic' => 'bundles'])
                    <div class="governance-card__header governance-card__header--compact">
                        <div>
                            <span class="governance-section-kicker">Evidence package</span>
                            <h2>Formal case bundle</h2>
                        </div>
                    </div>

                    <p class="governance-card__description">
                        Generate the evidence manifest behind a promotion, transfer, correction,
                        retirement or cadre decision.
                    </p>

                    <form method="POST" action="{{ route('advanced-governance.case-bundles.generate') }}"
                        class="governance-form needs-validation" novalidate>
                        @csrf

                        <label class="governance-field">
                            <span>Case type</span>
                            <select name="case_type" required>
                                <option value="">Select case</option>
                                @foreach (['promotion', 'transfer', 'correction', 'retirement', 'cadre'] as $case)
                                    <option value="{{ $case }}">{{ ucfirst($case) }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="governance-field">
                            <span>Employee ID <small>(not required for cadre)</small></span>
                            <input name="employee_id" type="number" min="1" placeholder="Employee ID">
                        </label>

                        <button class="governance-btn governance-btn--primary governance-btn--block" type="submit">
                            Generate evidence bundle
                        </button>
                    </form>

                    @if ($bundles->isNotEmpty())
                        <div class="governance-download-list">
                            @foreach ($bundles->take(5) as $bundle)
                                <a href="{{ route('advanced-governance.case-bundles.download', $bundle->id) }}">
                                    <span>
                                        <strong>{{ $bundle->bundle_no }}</strong>
                                        <small>{{ ucfirst($bundle->case_type) }}</small>
                                    </span>
                                    <span aria-hidden="true">↓</span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </section>

                <section class="governance-card governance-card--compact" id="assurance">
                    @include('partials.section-help', ['topic' => 'assurance'])
                    <div class="governance-card__header governance-card__header--compact">
                        <div>
                            <span class="governance-section-kicker">External assurance</span>
                            <h2>Official exports</h2>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('advanced-governance.assurance.generate') }}"
                        class="governance-form needs-validation" novalidate>
                        @csrf

                        <label class="governance-field">
                            <span>Audience</span>
                            <select name="audience" required>
                                <option value="">Select audience</option>
                                <option value="management_services">Management Services</option>
                                <option value="ministry">Ministry</option>
                                <option value="audit">Audit</option>
                                <option value="institution">Institution</option>
                            </select>
                        </label>

                        <label class="governance-field">
                            <span>Report</span>
                            <select name="report_type" required>
                                <option value="">Select report</option>
                                @foreach (['establishment', 'workforce', 'vacancy', 'retirement', 'governance', 'data_quality'] as $report)
                                    <option value="{{ $report }}">
                                        {{ ucwords(str_replace('_', ' ', $report)) }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label class="governance-field">
                            <span>As-at date</span>
                            <input name="as_at_date" type="date" max="{{ now()->toDateString() }}"
                                value="{{ now()->toDateString() }}" required>
                        </label>

                        <button class="governance-btn governance-btn--primary governance-btn--block" type="submit">
                            Generate official manifest
                        </button>
                    </form>

                    @if ($exports->isNotEmpty())
                        <div class="governance-download-list">
                            @foreach ($exports->take(5) as $export)
                                <a href="{{ route('advanced-governance.assurance.download', $export->id) }}">
                                    <span>
                                        <strong>{{ $export->export_no }}</strong>
                                        <small>{{ ucwords(str_replace('_', ' ', $export->report_type)) }}</small>
                                    </span>
                                    <span aria-hidden="true">↓</span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </section>
            </aside>
        </div>

        <section class="governance-card governance-advanced governance-animate governance-animate--4" id="rules">
            @include('partials.section-help', ['topic' => 'rule_provisions'])
            <div class="governance-card__header">
                <div>
                    <span class="governance-section-kicker">Policy configuration</span>
                    <h2>Circular &amp; Service Minute rules</h2>
                    <p>
                        Convert effective-dated provisions into machine-readable decision-support rules while preserving
                        the source reference and mandatory human review.
                    </p>
                </div>
                <span class="governance-badge governance-badge--neutral">Advanced configuration</span>
            </div>

            <details class="governance-disclosure">
                <summary>
                    <span>
                        <strong>Create or update a machine-readable provision</strong>
                        <small>For authorised HR / establishment administrators</small>
                    </span>
                    <span aria-hidden="true">⌄</span>
                </summary>
                <div class="governance-disclosure__body">
                    <form method="POST" action="{{ route('advanced-governance.rule-provisions.store') }}"
                        class="governance-form governance-form--two-column needs-validation" novalidate>
                        @csrf

                        <label class="governance-field">
                            <span>Rule version ID</span>
                            <input name="rule_version_id" type="number" min="1" required>
                        </label>

                        <label class="governance-field">
                            <span>Provision code</span>
                            <input name="provision_code" required maxlength="100" pattern="[A-Z0-9._-]+"
                                placeholder="SM.2025.CONFIRM.01">
                        </label>

                        <label class="governance-field governance-field--full">
                            <span>Subject area</span>
                            <select name="subject_area" required>
                                <option value="">Select subject area</option>
                                @foreach (['confirmation', 'increment', 'efficiency_bar', 'promotion', 'retirement', 'registration', 'transfer', 'cadre', 'other'] as $area)
                                    <option value="{{ $area }}">
                                        {{ ucwords(str_replace('_', ' ', $area)) }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label class="governance-field governance-field--full governance-field--technical">
                            <span>Conditions payload</span>
                            <textarea name="conditions" required placeholder='{"service_years":{"gte":3}}'></textarea>
                            <small>Machine-readable JSON conditions.</small>
                        </label>

                        <label class="governance-field governance-field--full governance-field--technical">
                            <span>Decision-support outcome</span>
                            <textarea name="outcomes" required placeholder='{"action":"review_eligibility"}'></textarea>
                            <small>Outcome must route to review; it must not execute the final administrative
                                decision.</small>
                        </label>

                        <label class="governance-field governance-field--full governance-field--technical">
                            <span>Required evidence</span>
                            <textarea name="required_evidence" required placeholder='["service_record","eb_result"]'></textarea>
                        </label>

                        <label class="governance-field governance-field--full">
                            <span>Plain-language officer explanation</span>
                            <textarea name="plain_language_summary" maxlength="3000"
                                placeholder="Explain the provision in administrative language."></textarea>
                        </label>

                        <div class="governance-form__actions governance-field--full">
                            <button class="governance-btn governance-btn--primary" type="submit">
                                Save rule provision
                            </button>
                        </div>
                    </form>
                </div>
            </details>
        </section>

        <section class="governance-card governance-advanced governance-animate governance-animate--5" id="provenance">
            @include('partials.section-help', ['topic' => 'provenance'])
            <div class="governance-card__header">
                <div>
                    <span class="governance-section-kicker">Authoritative data lineage</span>
                    <h2>Field-level provenance</h2>
                    <p>Record where an authoritative employee value originated and when that source applied.</p>
                </div>
                <span class="governance-badge governance-badge--neutral">
                    {{ number_format($metrics['provenance']) }} provenance records
                </span>
            </div>

            <details class="governance-disclosure">
                <summary>
                    <span>
                        <strong>Record an authoritative field source</strong>
                        <small>Advanced data-governance action</small>
                    </span>
                    <span aria-hidden="true">⌄</span>
                </summary>
                <div class="governance-disclosure__body">
                    <form method="POST" action="{{ route('advanced-governance.provenance.store') }}"
                        class="governance-form governance-form--two-column needs-validation" novalidate>
                        @csrf

                        <label class="governance-field">
                            <span>Employee ID</span>
                            <input name="employee_id" type="number" min="1" required>
                        </label>

                        <label class="governance-field">
                            <span>Field</span>
                            <input name="field_name" required pattern="[a-z0-9_]+" maxlength="100"
                                placeholder="date_of_appointment">
                        </label>

                        <label class="governance-field governance-field--full">
                            <span>Authoritative value</span>
                            <input name="field_value" maxlength="5000">
                        </label>

                        <label class="governance-field">
                            <span>Source type</span>
                            <select name="source_type" required>
                                <option value="">Select source</option>
                                @foreach (['appointment_letter', 'service_file', 'ministry_hrmis', 'circular', 'service_minute', 'verified_document', 'administrative_decision', 'manual_verified', 'other'] as $source)
                                    <option value="{{ $source }}">
                                        {{ ucwords(str_replace('_', ' ', $source)) }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label class="governance-field">
                            <span>Source reference</span>
                            <input name="source_reference" maxlength="180" placeholder="Reference number">
                        </label>

                        <label class="governance-field">
                            <span>Originating system</span>
                            <input name="source_system" maxlength="100" placeholder="System / registry">
                        </label>

                        <label class="governance-field">
                            <span>Confidence</span>
                            <div class="governance-input-suffix">
                                <input name="confidence" type="number" min="0" max="100" value="100"
                                    required>
                                <span>%</span>
                            </div>
                        </label>

                        <label class="governance-field">
                            <span>Effective from</span>
                            <input name="effective_from" type="date">
                        </label>

                        <label class="governance-field">
                            <span>Effective until</span>
                            <input name="effective_until" type="date">
                        </label>

                        <label class="governance-checkbox">
                            <input type="checkbox" name="is_authoritative" value="1">
                            <span>
                                <strong>Authoritative value</strong>
                                <small>This source is accepted as authoritative for this field.</small>
                            </span>
                        </label>

                        <div class="governance-form__actions governance-field--full">
                            <button class="governance-btn governance-btn--primary" type="submit">
                                Record provenance
                            </button>
                        </div>
                    </form>
                </div>
            </details>
        </section>

        <section class="governance-boundary governance-animate governance-animate--5"
            aria-label="Payroll interoperability boundary">
            <div class="governance-boundary__icon" aria-hidden="true">↔</div>
            <div>
                <strong>Payroll interoperability boundary</strong>
                <p>
                    Carder Management provides authoritative employee, grade, increment and establishment facts for
                    reconciliation with payroll. It does not duplicate the institution's payroll calculation engine.
                </p>
            </div>
        </section>
    </div>

    <style>
        .governance-page {
            --gov-primary: #245b82;
            --gov-primary-strong: #174a72;
            --gov-primary-soft: #edf5fa;
            --gov-surface: #ffffff;
            --gov-surface-soft: #f6f8fb;
            --gov-surface-muted: #eef3f7;
            --gov-text: #182534;
            --gov-text-muted: #66778a;
            --gov-border: #dfe6ed;
            --gov-border-strong: #cdd8e2;
            --gov-success: #24734e;
            --gov-success-soft: #eaf7f0;
            --gov-warning: #9a5a13;
            --gov-warning-soft: #fff5e6;
            --gov-danger: #a43d3d;
            --gov-danger-soft: #fff0f0;
            --gov-shadow: 0 8px 24px rgba(29, 52, 73, 0.07);
            --gov-shadow-hover: 0 14px 34px rgba(29, 52, 73, 0.11);
            padding: 6px 4px 36px;
            color: var(--gov-text);
        }

        html[data-theme="dark"] .governance-page {
            --gov-primary: #7db4d9;
            --gov-primary-strong: #9bc7e4;
            --gov-primary-soft: #162b3a;
            --gov-surface: #17212b;
            --gov-surface-soft: #1c2834;
            --gov-surface-muted: #22313e;
            --gov-text: #ecf2f7;
            --gov-text-muted: #a9b7c4;
            --gov-border: #344452;
            --gov-border-strong: #425665;
            --gov-success: #79c59d;
            --gov-success-soft: #173429;
            --gov-warning: #e8b067;
            --gov-warning-soft: #3a2d1c;
            --gov-danger: #ef9696;
            --gov-danger-soft: #402426;
            --gov-shadow: 0 10px 28px rgba(0, 0, 0, 0.16);
            --gov-shadow-hover: 0 16px 38px rgba(0, 0, 0, 0.22);
        }

        .governance-page__header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 24px;
            padding: 18px 4px 22px;
            border-bottom: 1px solid var(--gov-border);
            margin-bottom: 20px;
        }

        .governance-page__heading {
            min-width: 0;
        }

        .governance-page__eyebrow,
        .governance-section-kicker {
            display: block;
            margin-bottom: 5px;
            color: var(--gov-primary);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .governance-page__heading h1 {
            margin: 0;
            color: var(--gov-text);
            font-size: clamp(25px, 2.2vw, 34px);
            font-weight: 720;
            line-height: 1.18;
            letter-spacing: -0.025em;
        }

        .governance-page__heading p {
            max-width: 820px;
            margin: 8px 0 0;
            color: var(--gov-text-muted);
            font-size: 14px;
            line-height: 1.6;
        }

        .governance-decision-note {
            display: flex;
            align-items: flex-start;
            gap: 11px;
            width: min(370px, 100%);
            padding: 12px 14px;
            border: 1px solid var(--gov-border);
            border-radius: 10px;
            background: var(--gov-surface);
            box-shadow: var(--gov-shadow);
        }

        .governance-decision-note__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 26px;
            height: 26px;
            flex: 0 0 26px;
            border-radius: 50%;
            background: var(--gov-success-soft);
            color: var(--gov-success);
            font-size: 14px;
            font-weight: 800;
        }

        .governance-decision-note strong,
        .governance-decision-note span {
            display: block;
        }

        .governance-decision-note strong {
            color: var(--gov-text);
            font-size: 13px;
        }

        .governance-decision-note div>span {
            margin-top: 3px;
            color: var(--gov-text-muted);
            font-size: 11px;
            line-height: 1.45;
        }

        .governance-alert {
            margin-bottom: 16px;
            padding: 12px 14px;
            border: 1px solid;
            border-radius: 9px;
            font-size: 13px;
        }

        .governance-alert strong,
        .governance-alert span {
            display: block;
        }

        .governance-alert ul {
            margin: 7px 0 0;
            padding-left: 20px;
        }

        .governance-alert--success {
            border-color: color-mix(in srgb, var(--gov-success) 35%, var(--gov-border));
            background: var(--gov-success-soft);
            color: var(--gov-success);
        }

        .governance-alert--danger {
            border-color: color-mix(in srgb, var(--gov-danger) 35%, var(--gov-border));
            background: var(--gov-danger-soft);
            color: var(--gov-danger);
        }

        .governance-stat-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 18px;
        }

        .governance-stat-card {
            min-width: 0;
            padding: 14px 15px;
            border: 1px solid var(--gov-border);
            border-radius: 10px;
            background: var(--gov-surface);
            box-shadow: 0 3px 12px rgba(29, 52, 73, 0.035);
            transition: transform 180ms ease, box-shadow 180ms ease, border-color 180ms ease;
        }

        .governance-stat-card:hover {
            transform: translateY(-2px);
            border-color: var(--gov-border-strong);
            box-shadow: var(--gov-shadow);
        }

        .governance-stat-card--attention {
            border-left: 3px solid var(--gov-warning);
        }

        .governance-stat-card__label {
            display: block;
            overflow: hidden;
            color: var(--gov-text-muted);
            font-size: 11px;
            font-weight: 650;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .governance-stat-card strong {
            display: block;
            margin-top: 5px;
            color: var(--gov-text);
            font-size: 25px;
            font-weight: 750;
            line-height: 1;
        }

        .governance-stat-card small {
            display: block;
            margin-top: 7px;
            overflow: hidden;
            color: var(--gov-text-muted);
            font-size: 10.5px;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .governance-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 320px;
            gap: 16px;
            align-items: start;
        }

        .governance-main,
        .governance-side {
            display: grid;
            gap: 16px;
            min-width: 0;
        }

        .governance-card {
            min-width: 0;
            padding: 20px;
            border: 1px solid var(--gov-border);
            border-radius: 12px;
            background: var(--gov-surface);
            box-shadow: var(--gov-shadow);
        }

        .governance-card--priority {
            border-top: 3px solid var(--gov-primary);
        }

        .governance-card--compact {
            padding: 17px;
        }

        .governance-card__header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 16px;
        }

        .governance-card__header--compact {
            gap: 10px;
            margin-bottom: 12px;
        }

        .governance-card h2 {
            margin: 0;
            color: var(--gov-text);
            font-size: 18px;
            font-weight: 720;
            line-height: 1.25;
            letter-spacing: -0.012em;
        }

        .governance-card__header p,
        .governance-card__description {
            margin: 5px 0 0;
            color: var(--gov-text-muted);
            font-size: 12.5px;
            line-height: 1.55;
        }

        .governance-card__description {
            margin-bottom: 14px;
        }

        .governance-btn {
            appearance: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 38px;
            padding: 8px 14px;
            border: 1px solid transparent;
            border-radius: 8px;
            font: inherit;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.2;
            cursor: pointer;
            text-decoration: none;
            transition: transform 160ms ease, box-shadow 160ms ease, background-color 160ms ease;
        }

        .governance-btn:hover {
            transform: translateY(-1px);
        }

        .governance-btn:focus-visible {
            outline: 3px solid color-mix(in srgb, var(--gov-primary) 25%, transparent);
            outline-offset: 2px;
        }

        .governance-btn--primary {
            border-color: var(--gov-primary-strong);
            background: var(--gov-primary-strong);
            color: #ffffff;
            box-shadow: 0 4px 10px rgba(23, 74, 114, 0.14);
        }

        html[data-theme="dark"] .governance-btn--primary {
            color: #10212d;
        }

        .governance-btn--secondary {
            border-color: var(--gov-border-strong);
            background: var(--gov-surface);
            color: var(--gov-primary);
        }

        .governance-btn--small {
            min-height: 34px;
            padding: 6px 11px;
            border-color: var(--gov-border-strong);
            background: var(--gov-surface-soft);
            color: var(--gov-primary);
        }

        .governance-btn--block {
            width: 100%;
        }

        .governance-badge {
            display: inline-flex;
            align-items: center;
            width: max-content;
            max-width: 100%;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 750;
            line-height: 1.25;
            white-space: nowrap;
        }

        .governance-badge--info {
            background: var(--gov-primary-soft);
            color: var(--gov-primary);
        }

        .governance-badge--warning {
            background: var(--gov-warning-soft);
            color: var(--gov-warning);
        }

        .governance-badge--neutral {
            background: var(--gov-surface-muted);
            color: var(--gov-text-muted);
        }

        .governance-eligibility-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
            margin-bottom: 15px;
        }

        .governance-eligibility-summary>div {
            padding: 11px 12px;
            border: 1px solid var(--gov-border);
            border-radius: 8px;
            background: var(--gov-surface-soft);
        }

        .governance-eligibility-summary span,
        .governance-eligibility-summary strong {
            display: block;
        }

        .governance-eligibility-summary span {
            color: var(--gov-text-muted);
            font-size: 10px;
            font-weight: 650;
            text-transform: uppercase;
            letter-spacing: 0.045em;
        }

        .governance-eligibility-summary strong {
            margin-top: 4px;
            color: var(--gov-text);
            font-size: 19px;
            line-height: 1.1;
        }

        .governance-text-status {
            color: var(--gov-success) !important;
            font-size: 13px !important;
        }

        .governance-table-wrap {
            overflow-x: auto;
            border: 1px solid var(--gov-border);
            border-radius: 9px;
        }

        .governance-table-wrap--spaced {
            margin-top: 16px;
        }

        .governance-table {
            width: 100%;
            margin: 0;
            border-collapse: collapse;
            color: var(--gov-text);
            font-size: 12px;
        }

        .governance-table th {
            padding: 10px 12px;
            border-bottom: 1px solid var(--gov-border);
            background: var(--gov-surface-soft);
            color: var(--gov-text-muted);
            font-size: 10px;
            font-weight: 750;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 0.045em;
            white-space: nowrap;
        }

        .governance-table td {
            padding: 11px 12px;
            border-bottom: 1px solid var(--gov-border);
            vertical-align: middle;
        }

        .governance-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .governance-table tbody tr {
            transition: background-color 140ms ease;
        }

        .governance-table tbody tr:hover {
            background: var(--gov-surface-soft);
        }

        .governance-table__explanation {
            min-width: 230px;
            color: var(--gov-text-muted);
            line-height: 1.45;
        }

        .governance-table--compact td {
            padding-top: 9px;
            padding-bottom: 9px;
        }

        .governance-person {
            display: flex;
            align-items: center;
            gap: 9px;
            min-width: 150px;
        }

        .governance-person__avatar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            flex: 0 0 30px;
            border-radius: 50%;
            background: var(--gov-primary-soft);
            color: var(--gov-primary);
            font-size: 11px;
            font-weight: 800;
        }

        .governance-person strong,
        .governance-person small {
            display: block;
        }

        .governance-person small {
            margin-top: 2px;
            color: var(--gov-text-muted);
            font-size: 10px;
        }

        .governance-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            min-height: 100px;
            color: var(--gov-text-muted);
            text-align: center;
        }

        .governance-empty strong {
            color: var(--gov-text);
            font-size: 12px;
        }

        .governance-empty span {
            font-size: 11px;
        }

        .governance-empty--inline {
            min-height: 82px;
            margin-top: 14px;
            border: 1px dashed var(--gov-border-strong);
            border-radius: 9px;
            background: var(--gov-surface-soft);
        }

        .governance-empty--compact {
            min-height: 80px;
            border: 1px dashed var(--gov-border);
            border-radius: 8px;
        }

        .governance-date-toolbar {
            display: flex;
            align-items: flex-end;
            gap: 8px;
            flex-wrap: wrap;
        }

        .governance-date-toolbar label {
            display: grid;
            gap: 4px;
        }

        .governance-date-toolbar label>span {
            color: var(--gov-text-muted);
            font-size: 10px;
            font-weight: 650;
        }

        .governance-date-toolbar input {
            min-height: 38px;
        }

        .governance-date-toolbar__arrow {
            margin-bottom: 9px;
            color: var(--gov-text-muted);
        }

        .governance-forecast-overview {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 8px;
            margin-bottom: 15px;
        }

        .governance-forecast-kpi {
            padding: 12px;
            border: 1px solid var(--gov-border);
            border-radius: 8px;
            background: var(--gov-surface-soft);
        }

        .governance-forecast-kpi span,
        .governance-forecast-kpi strong {
            display: block;
        }

        .governance-forecast-kpi span {
            color: var(--gov-text-muted);
            font-size: 10px;
            line-height: 1.3;
        }

        .governance-forecast-kpi strong {
            margin-top: 5px;
            color: var(--gov-text);
            font-size: 21px;
            line-height: 1;
        }

        .governance-forecast-kpi--projected {
            border-color: color-mix(in srgb, var(--gov-primary) 45%, var(--gov-border));
            background: var(--gov-primary-soft);
        }

        .governance-forecast-kpi--projected strong {
            color: var(--gov-primary);
        }

        .governance-chart-shell {
            padding: 14px;
            border: 1px solid var(--gov-border);
            border-radius: 9px;
        }

        .governance-chart-shell__title {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 12px;
        }

        .governance-chart-shell__title strong,
        .governance-chart-shell__title span {
            display: block;
        }

        .governance-chart-shell__title strong {
            color: var(--gov-text);
            font-size: 12px;
        }

        .governance-chart-shell__title div>span {
            margin-top: 2px;
            color: var(--gov-text-muted);
            font-size: 10.5px;
        }

        .governance-chart-shell__canvas {
            position: relative;
            height: 230px;
        }

        .governance-temporal-stage {
            display: grid;
            grid-template-columns: 230px minmax(160px, 1fr) 290px;
            gap: 20px;
            align-items: center;
            padding: 16px;
            border: 1px solid var(--gov-border);
            border-radius: 10px;
            background: var(--gov-surface-soft);
        }

        .governance-temporal-stage__date span,
        .governance-temporal-stage__date strong,
        .governance-temporal-stage__date small {
            display: block;
        }

        .governance-temporal-stage__date span {
            color: var(--gov-text-muted);
            font-size: 10px;
            font-weight: 650;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .governance-temporal-stage__date strong {
            margin-top: 4px;
            color: var(--gov-text);
            font-size: 18px;
        }

        .governance-temporal-stage__date small {
            margin-top: 6px;
            color: var(--gov-text-muted);
            font-size: 10px;
            line-height: 1.45;
        }

        .governance-temporal-track {
            position: relative;
            height: 34px;
        }

        .governance-temporal-track__line {
            position: absolute;
            top: 16px;
            right: 0;
            left: 0;
            height: 2px;
            border-radius: 2px;
            background: var(--gov-border-strong);
            overflow: hidden;
        }

        .governance-temporal-track__line::after {
            content: "";
            position: absolute;
            inset: 0 45% 0 0;
            background: linear-gradient(90deg, transparent, var(--gov-primary), transparent);
            animation: governanceTimelineSweep 3s ease-in-out infinite;
        }

        .governance-temporal-track__node {
            position: absolute;
            top: 11px;
            width: 12px;
            height: 12px;
            border: 2px solid var(--gov-surface);
            border-radius: 50%;
            background: var(--gov-border-strong);
            box-shadow: 0 0 0 1px var(--gov-border-strong);
        }

        .governance-temporal-track__node--past {
            left: 6%;
        }

        .governance-temporal-track__node--selected {
            left: 52%;
            background: var(--gov-primary);
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--gov-primary) 15%, transparent);
            animation: governanceSelectedNode 2.2s ease-in-out infinite;
        }

        .governance-temporal-track__node--future {
            right: 6%;
        }

        .governance-as-at-form {
            display: grid;
            gap: 6px;
        }

        .governance-as-at-form>label {
            color: var(--gov-text-muted);
            font-size: 10px;
            font-weight: 650;
        }

        .governance-as-at-form>div {
            display: flex;
            gap: 7px;
        }

        .governance-as-at-form input {
            min-width: 0;
            flex: 1;
        }

        .governance-disclosure {
            margin-top: 14px;
            border: 1px solid var(--gov-border);
            border-radius: 9px;
            background: var(--gov-surface);
            overflow: hidden;
        }

        .governance-disclosure--subtle {
            margin-top: 12px;
            border-style: dashed;
        }

        .governance-disclosure summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 11px 13px;
            color: var(--gov-text);
            cursor: pointer;
            list-style: none;
            user-select: none;
        }

        .governance-disclosure summary::-webkit-details-marker {
            display: none;
        }

        .governance-disclosure summary:hover {
            background: var(--gov-surface-soft);
        }

        .governance-disclosure summary strong,
        .governance-disclosure summary small {
            display: block;
        }

        .governance-disclosure summary strong {
            font-size: 11.5px;
        }

        .governance-disclosure summary small {
            margin-top: 2px;
            color: var(--gov-text-muted);
            font-size: 9.5px;
        }

        .governance-disclosure[open] summary {
            border-bottom: 1px solid var(--gov-border);
            background: var(--gov-surface-soft);
        }

        .governance-disclosure__body {
            padding: 15px;
        }

        .governance-form-note {
            margin: 0 0 13px;
            padding: 9px 11px;
            border-left: 3px solid var(--gov-primary);
            background: var(--gov-primary-soft);
            color: var(--gov-text-muted);
            font-size: 11px;
            line-height: 1.5;
        }

        .governance-form {
            display: grid;
            gap: 11px;
        }

        .governance-form--two-column {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .governance-field {
            display: grid;
            gap: 5px;
            min-width: 0;
            margin: 0;
        }

        .governance-field>span,
        .governance-field-row .governance-field>span {
            color: var(--gov-text-muted);
            font-size: 10.5px;
            font-weight: 650;
        }

        .governance-field>span small {
            font-weight: 500;
        }

        .governance-field>small {
            color: var(--gov-text-muted);
            font-size: 9.5px;
            line-height: 1.4;
        }

        .governance-field--full {
            grid-column: 1 / -1;
        }

        .governance-field--technical {
            padding: 10px;
            border: 1px dashed var(--gov-border-strong);
            border-radius: 8px;
            background: var(--gov-surface-soft);
        }

        .governance-field-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 9px;
        }

        .governance-field input,
        .governance-field select,
        .governance-field textarea,
        .governance-date-toolbar input,
        .governance-inline-review input,
        .governance-inline-review select,
        .governance-as-at-form input {
            width: 100%;
            min-height: 40px;
            box-sizing: border-box;
            padding: 8px 10px;
            border: 1px solid var(--gov-border-strong);
            border-radius: 7px;
            outline: none;
            background: var(--gov-surface);
            color: var(--gov-text);
            font: inherit;
            font-size: 12px;
            transition: border-color 150ms ease, box-shadow 150ms ease, background-color 150ms ease;
        }

        .governance-field textarea {
            min-height: 86px;
            resize: vertical;
            line-height: 1.45;
        }

        .governance-field input:focus,
        .governance-field select:focus,
        .governance-field textarea:focus,
        .governance-date-toolbar input:focus,
        .governance-inline-review input:focus,
        .governance-inline-review select:focus,
        .governance-as-at-form input:focus {
            border-color: var(--gov-primary);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--gov-primary) 14%, transparent);
        }

        .governance-form.was-validated :invalid {
            border-color: var(--gov-danger);
        }

        .governance-form.was-validated :valid {
            border-color: color-mix(in srgb, var(--gov-success) 65%, var(--gov-border));
        }

        .governance-field input::placeholder,
        .governance-field textarea::placeholder,
        .governance-inline-review input::placeholder {
            color: color-mix(in srgb, var(--gov-text-muted) 72%, transparent);
        }

        .governance-checkbox {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            margin: 0;
            color: var(--gov-text);
            font-size: 11px;
        }

        .governance-checkbox input {
            width: 16px;
            height: 16px;
            margin-top: 1px;
            accent-color: var(--gov-primary);
        }

        .governance-checkbox strong,
        .governance-checkbox small {
            display: block;
        }

        .governance-checkbox small {
            margin-top: 2px;
            color: var(--gov-text-muted);
            line-height: 1.35;
        }

        .governance-form__actions {
            display: flex;
            justify-content: flex-end;
            padding-top: 3px;
        }

        .governance-import-bar {
            display: grid;
            grid-template-columns: minmax(180px, 1fr) minmax(220px, 1.3fr) auto;
            gap: 10px;
            align-items: end;
            padding: 13px;
            border: 1px solid var(--gov-border);
            border-radius: 9px;
            background: var(--gov-surface-soft);
        }

        .governance-field--file input {
            padding: 6px 8px;
        }

        .governance-inline-review {
            display: grid;
            grid-template-columns: 110px minmax(150px, 1fr) auto;
            gap: 6px;
            min-width: 360px;
        }

        .governance-inline-review input,
        .governance-inline-review select {
            min-height: 34px;
            padding: 6px 8px;
            font-size: 11px;
        }

        .governance-evidence-list {
            display: grid;
            gap: 0;
            border-top: 1px solid var(--gov-border);
        }

        .governance-evidence-list article {
            display: flex;
            gap: 9px;
            padding: 10px 0;
            border-bottom: 1px solid var(--gov-border);
        }

        .governance-evidence-list__dot {
            width: 7px;
            height: 7px;
            flex: 0 0 7px;
            margin-top: 5px;
            border-radius: 50%;
            background: var(--gov-warning);
        }

        .governance-evidence-list strong,
        .governance-evidence-list span,
        .governance-evidence-list small {
            display: block;
        }

        .governance-evidence-list strong {
            color: var(--gov-text);
            font-size: 11px;
        }

        .governance-evidence-list span {
            margin-top: 2px;
            color: var(--gov-text);
            font-size: 10.5px;
        }

        .governance-evidence-list small {
            margin-top: 2px;
            color: var(--gov-text-muted);
            font-size: 9.5px;
        }

        .governance-download-list {
            display: grid;
            gap: 6px;
            margin-top: 13px;
            padding-top: 12px;
            border-top: 1px solid var(--gov-border);
        }

        .governance-download-list a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 8px 9px;
            border: 1px solid var(--gov-border);
            border-radius: 7px;
            color: var(--gov-text);
            text-decoration: none;
            transition: border-color 150ms ease, background-color 150ms ease;
        }

        .governance-download-list a:hover {
            border-color: var(--gov-primary);
            background: var(--gov-primary-soft);
        }

        .governance-download-list strong,
        .governance-download-list small {
            display: block;
        }

        .governance-download-list strong {
            font-size: 10.5px;
        }

        .governance-download-list small {
            margin-top: 1px;
            color: var(--gov-text-muted);
            font-size: 9.5px;
        }

        .governance-input-suffix {
            position: relative;
        }

        .governance-input-suffix input {
            padding-right: 28px;
        }

        .governance-input-suffix span {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            color: var(--gov-text-muted);
            font-size: 11px;
        }

        .governance-advanced {
            margin-top: 16px;
        }

        .governance-boundary {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-top: 16px;
            padding: 14px 16px;
            border: 1px solid var(--gov-border);
            border-radius: 10px;
            background: var(--gov-surface-soft);
        }

        .governance-boundary__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            flex: 0 0 30px;
            border-radius: 8px;
            background: var(--gov-primary-soft);
            color: var(--gov-primary);
            font-weight: 800;
        }

        .governance-boundary strong {
            color: var(--gov-text);
            font-size: 12px;
        }

        .governance-boundary p {
            margin: 3px 0 0;
            color: var(--gov-text-muted);
            font-size: 11px;
            line-height: 1.5;
        }

        .governance-animate {
            opacity: 0;
            transform: translateY(7px);
            animation: governanceEnter 380ms ease forwards;
        }

        .governance-animate--1 {
            animation-delay: 20ms;
        }

        .governance-animate--2 {
            animation-delay: 70ms;
        }

        .governance-animate--3 {
            animation-delay: 120ms;
        }

        .governance-animate--4 {
            animation-delay: 170ms;
        }

        .governance-animate--5 {
            animation-delay: 220ms;
        }

        @keyframes governanceEnter {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes governanceTimelineSweep {

            0%,
            100% {
                transform: translateX(-35%);
                opacity: 0;
            }

            35%,
            65% {
                opacity: 0.7;
            }

            100% {
                transform: translateX(160%);
            }
        }

        @keyframes governanceSelectedNode {

            0%,
            100% {
                box-shadow: 0 0 0 4px color-mix(in srgb, var(--gov-primary) 12%, transparent);
            }

            50% {
                box-shadow: 0 0 0 8px color-mix(in srgb, var(--gov-primary) 4%, transparent);
            }
        }

        @media (max-width: 1320px) {
            .governance-stat-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .governance-layout {
                grid-template-columns: minmax(0, 1fr) 290px;
            }

            .governance-temporal-stage {
                grid-template-columns: 210px 1fr;
            }

            .governance-as-at-form {
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 1080px) {
            .governance-page__header {
                align-items: flex-start;
                flex-direction: column;
            }

            .governance-decision-note {
                width: 100%;
            }

            .governance-layout {
                grid-template-columns: 1fr;
            }

            .governance-side {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .governance-forecast-overview {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 820px) {

            .governance-stat-grid,
            .governance-side {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .governance-eligibility-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .governance-card__header {
                flex-direction: column;
            }

            .governance-date-toolbar {
                width: 100%;
            }

            .governance-forecast-overview {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .governance-temporal-stage {
                grid-template-columns: 1fr;
            }

            .governance-temporal-track {
                display: none;
            }

            .governance-form--two-column,
            .governance-import-bar {
                grid-template-columns: 1fr;
            }

            .governance-field--full {
                grid-column: auto;
            }
        }

        @media (max-width: 560px) {
            .governance-page {
                padding-right: 0;
                padding-left: 0;
            }

            .governance-stat-grid,
            .governance-side,
            .governance-eligibility-summary,
            .governance-forecast-overview {
                grid-template-columns: 1fr;
            }

            .governance-card {
                padding: 15px;
                border-radius: 9px;
            }

            .governance-date-toolbar label {
                width: 100%;
            }

            .governance-date-toolbar__arrow {
                display: none;
            }

            .governance-date-toolbar .governance-btn,
            .governance-as-at-form .governance-btn {
                width: 100%;
            }

            .governance-as-at-form>div,
            .governance-field-row {
                grid-template-columns: 1fr;
                display: grid;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            .governance-animate,
            .governance-temporal-track__line::after,
            .governance-temporal-track__node--selected {
                opacity: 1;
                transform: none;
                animation: none !important;
            }

            .governance-stat-card,
            .governance-btn {
                transition: none;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('.needs-validation');

            forms.forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    const effectiveFrom = form.querySelector('[name="effective_from"]');
                    const effectiveUntil = form.querySelector('[name="effective_until"]');

                    if (
                        effectiveFrom &&
                        effectiveUntil &&
                        effectiveFrom.value &&
                        effectiveUntil.value &&
                        effectiveUntil.value < effectiveFrom.value
                    ) {
                        effectiveUntil.setCustomValidity(
                            'Effective-until date must be on or after the effective-from date.'
                        );
                    } else if (effectiveUntil) {
                        effectiveUntil.setCustomValidity('');
                    }

                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();

                        const invalidField = form.querySelector(':invalid');

                        if (invalidField) {
                            invalidField.focus({
                                preventScroll: true
                            });
                            invalidField.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center',
                            });
                        }
                    }

                    form.classList.add('was-validated');
                });
            });

            const chartElement = document.getElementById('governanceForecastChart');

            if (chartElement && window.Chart) {
                const rootStyles = getComputedStyle(document.querySelector('.governance-page'));
                const primary = rootStyles.getPropertyValue('--gov-primary').trim();
                const border = rootStyles.getPropertyValue('--gov-border').trim();
                const textMuted = rootStyles.getPropertyValue('--gov-text-muted').trim();
                const currentActive = Number(@json((int) $forecast['current_active']));
                const afterRetirements = currentActive - Number(@json((int) $forecast['confirmed_retirements']));
                const afterTransfersOut = afterRetirements - Number(@json((int) $forecast['transfers_out']));
                const projectedActive = Number(@json((int) $forecast['projected_active']));

                new Chart(chartElement, {
                    type: 'line',
                    data: {
                        labels: [
                            'Current',
                            'After retirements',
                            'After transfers out',
                            'Projected',
                        ],
                        datasets: [{
                            label: 'Active employees',
                            data: [
                                currentActive,
                                afterRetirements,
                                afterTransfersOut,
                                projectedActive,
                            ],
                            borderColor: primary,
                            backgroundColor: primary,
                            borderWidth: 2.5,
                            pointRadius: 4,
                            pointHoverRadius: 5,
                            pointBackgroundColor: primary,
                            tension: 0.28,
                            fill: false,
                        }, ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: {
                            duration: 850,
                            easing: 'easeOutQuart',
                        },
                        interaction: {
                            intersect: false,
                            mode: 'index',
                        },
                        plugins: {
                            legend: {
                                display: false,
                            },
                            tooltip: {
                                displayColors: false,
                            },
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false,
                                },
                                ticks: {
                                    color: textMuted,
                                    font: {
                                        size: 10,
                                    },
                                },
                                border: {
                                    color: border,
                                },
                            },
                            y: {
                                beginAtZero: false,
                                grid: {
                                    color: border,
                                },
                                ticks: {
                                    color: textMuted,
                                    precision: 0,
                                    font: {
                                        size: 10,
                                    },
                                },
                                border: {
                                    display: false,
                                },
                            },
                        },
                    },
                });
            }
        });
    </script>
@endsection
