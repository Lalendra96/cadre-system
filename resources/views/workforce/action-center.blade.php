@extends('layouts.app')
@section('title', $isScoped ? 'My Action Centre' : 'Workforce Action Centre')
@section('content')
    <div class="page-header">
        <div>
            <div class="page-eyebrow">Operations</div>
            <h1 class="page-title">{{ $isScoped ? 'My Action Centre' : 'Workforce Action Centre' }}</h1>
            <p class="page-subtitle">One prioritized queue for data corrections, increments, retirements, acting appointments
                and Carder approvals.</p>
        </div>
        <div class="page-actions">
            <a href="{{ $isScoped ? route('subject-officer.workspace') : route('workforce.reconciliation') }}"
                class="md-btn md-btn--outlined">{{ $isScoped ? 'My Workspace' : 'Open Reconciliation' }}</a>
        </div>
    </div>
    <div class="workforce-kpi-grid">
        <div class="workforce-kpi">
            <div class="workforce-kpi__label">Critical Data Issues</div>
            <div class="workforce-kpi__value">{{ $counts['critical_quality'] }}</div>
            <a href="{{ route('data-quality.index') }}">Review issues →</a>
        </div>
        <div class="workforce-kpi">
            <div class="workforce-kpi__label">Overdue Increments</div>
            <div class="workforce-kpi__value">{{ $counts['overdue_increments'] }}</div>
            <small>{{ $counts['increments_30'] }} due within 30 days</small>
        </div>
        <div class="workforce-kpi">
            <div class="workforce-kpi__label">Open Retirement Cases</div>
            <div class="workforce-kpi__value">{{ $counts['retirements'] }}</div>
            <a href="{{ route('retirement-projects.index') }}">Open projects →</a>
        </div>
        <div class="workforce-kpi">
            <div class="workforce-kpi__label">Acting Expiries ≤30 Days</div>
            <div class="workforce-kpi__value">{{ $counts['acting_expiring'] }}</div>
            <small>Appointments nearing end date</small>
        </div>
        @unless ($isScoped)
            <div class="workforce-kpi">
                <div class="workforce-kpi__label">Verification / Amendments</div>
                <div class="workforce-kpi__value">{{ $counts['verification'] + $counts['amendments'] }}</div>
                <small>{{ $counts['verification'] }} verify · {{ $counts['amendments'] }} amendments</small>
            </div>
        @endunless
    </div>
    <div class="section-grid">
        <section class="workforce-panel span-6">
            <div class="panel-title-row">
                <div>
                    <div class="panel-title">Priority data corrections</div>
                    <div class="panel-subtitle">Highest-value employee master-data fixes.</div>
                </div>
                <a class="md-btn md-btn--text md-btn--sm" href="{{ route('data-quality.index') }}">All issues</a>
            </div>
            <div style="display:grid;gap:8px">
                @forelse($quality->take(10) as $i)
                    <a class="action-card" href="{{ route('employees.show', $i['employee']) }}">
                        <span
                            class="priority-dot {{ $i['severity'] === 'critical' ? 'priority-dot--critical' : 'priority-dot--warning' }}">
                        </span>
                        <div>
                            <strong>{{ $i['employee']->display_name }}</strong>
                            <div class="md-body-sm" style="color:var(--md-on-surface-variant)">{{ $i['label'] }}</div>
                        </div>
                        <span class="status-chip" style="margin-left:auto">{{ strtoupper($i['severity']) }}</span>
                    </a>
                @empty
                    <div class="empty-state">No detected employee-data issues.</div>
                @endforelse
            </div>
        </section>
        <section class="workforce-panel span-6">
            <div class="panel-title-row">
                <div>
                    <div class="panel-title">Increment actions</div>
                    <div class="panel-subtitle">Upcoming and overdue employee increments.</div>
                </div>
                <a class="md-btn md-btn--text md-btn--sm" href="{{ route('increments.dashboard') }}">Open increments</a>
            </div>
            <div style="display:grid;gap:8px">
                @forelse($increments->take(10) as $i)
                    <a class="action-card" href="{{ route('employee-increments.index', $i->employee) }}">
                        <span
                            class="priority-dot {{ $i->increment_date && $i->increment_date->isPast() ? 'priority-dot--critical' : 'priority-dot--info' }}">
                        </span>
                        <div>
                            <strong>{{ $i->employee?->display_name }}</strong>
                            <div class="md-body-sm" style="color:var(--md-on-surface-variant)">
                                {{ $i->increment_date?->format('d M Y') }}</div>
                        </div>
                        <span class="status-chip"
                            style="margin-left:auto">{{ ucwords(str_replace('_', ' ', $i->workflow_status ?? 'upcoming')) }}</span>
                    </a>
                @empty
                    <div class="empty-state">No increment action due within 30 days.</div>
                @endforelse
            </div>
        </section>
        <section class="workforce-panel span-6">
            <div class="panel-title-row">
                <div>
                    <div class="panel-title">Retirement actions</div>
                    <div class="panel-subtitle">Open projects and employee verification work.</div>
                </div>
                <a class="md-btn md-btn--text md-btn--sm" href="{{ route('retirement-projects.index') }}">Open projects</a>
            </div>
            <div style="display:grid;gap:8px">
                @forelse($retirements->take(10) as $p)
                    <a class="action-card" href="{{ route('retirement-projects.index') }}">
                        <span class="priority-dot priority-dot--warning">
                        </span>
                        <div>
                            <strong>{{ $p->employee?->display_name }}</strong>
                            <div class="md-body-sm" style="color:var(--md-on-surface-variant)">
                                {{ $p->retirement_date?->format('d M Y') }}</div>
                        </div>
                        <span class="status-chip"
                            style="margin-left:auto">{{ ucwords(str_replace('_', ' ', $p->status)) }}</span>
                    </a>
                @empty
                    <div class="empty-state">No open retirement project.</div>
                @endforelse
            </div>
        </section>
        <section class="workforce-panel span-6">
            <div class="panel-title-row">
                <div>
                    <div class="panel-title">Acting appointments ending</div>
                    <div class="panel-subtitle">Appointments ending within the next 30 days.</div>
                </div>
            </div>
            <div style="display:grid;gap:8px">
                @forelse($acting->take(10) as $a)
                    <a class="action-card" href="{{ route('employees.show', $a->employee) }}">
                        <span class="priority-dot priority-dot--info">
                        </span>
                        <div>
                            <strong>{{ $a->employee?->display_name }}</strong>
                            <div class="md-body-sm" style="color:var(--md-on-surface-variant)">
                                {{ $a->actingPosition?->title ?? 'Acting appointment' }} · ends
                                {{ $a->end_date?->format('d M Y') }}</div>
                        </div>
                    </a>
                @empty
                    <div class="empty-state">No acting appointment ends within 30 days.</div>
                @endforelse
            </div>
        </section>
        @unless ($isScoped)
            <section class="workforce-panel span-12">
                <div class="panel-title-row">
                    <div>
                        <div class="panel-title">Carder approval queue</div>
                        <div class="panel-subtitle">Verification and amendment requests awaiting management action.</div>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:8px">
                    @foreach ($pendingVerification->take(6) as $e)
                        <a class="action-card" href="{{ route('carder-entries.pending-verification') }}">
                            <span class="priority-dot priority-dot--info">
                            </span>
                            <div>
                                <strong>Verify · {{ $e->subjectCode?->code }}</strong>
                                <div class="md-body-sm">{{ $e->position?->title }} ·
                                    {{ sprintf('%02d/%d', $e->month, $e->year) }}</div>
                            </div>
                        </a>
                    @endforeach
                    @foreach ($amendments->take(6) as $e)
                        <a class="action-card" href="{{ route('entry-amendments.index') }}">
                            <span class="priority-dot priority-dot--warning">
                            </span>
                            <div>
                                <strong>Amendment · {{ $e->subjectCode?->code }}</strong>
                                <div class="md-body-sm">{{ $e->position?->title }} ·
                                    {{ sprintf('%02d/%d', $e->month, $e->year) }}</div>
                            </div>
                        </a>
                    @endforeach
                    @if ($pendingVerification->isEmpty() && $amendments->isEmpty())
                        <div class="empty-state">No pending Carder approvals.</div>
                    @endif
                </div>
            </section>
        @endunless
    </div>
@endsection
