@extends('layouts.app')
@section('title', $isScoped ? 'My Data Quality' : 'Data Quality')
@section('content')
    <div class="page-header">
        <div>
            <div class="page-eyebrow">Workforce Management</div>
            <h1 class="page-title">Workforce Data Quality</h1>
            <p class="page-subtitle">Monitor completeness, consistency and employee master-data exceptions.</p>
        </div>
        <span
            class="status-chip {{ $score >= 95 ? 'status-chip--good' : ($score >= 85 ? 'status-chip--warn' : 'status-chip--bad') }}">Quality
            score {{ $score }}%</span>
    </div>
    <div class="workforce-kpi-grid">
        <div class="workforce-kpi">
            <div class="workforce-kpi__label">Employees Checked</div>
            <div class="workforce-kpi__value">{{ $employees->count() }}</div>
        </div>
        <div class="workforce-kpi">
            <div class="workforce-kpi__label">Open Issues</div>
            <div class="workforce-kpi__value">{{ $persistent->count() }}</div>
        </div>
        <div class="workforce-kpi">
            <div class="workforce-kpi__label">Critical</div>
            <div class="workforce-kpi__value">{{ $bySeverity['critical'] ?? 0 }}</div>
        </div>
        <div class="workforce-kpi">
            <div class="workforce-kpi__label">High</div>
            <div class="workforce-kpi__value">{{ $bySeverity['high'] ?? 0 }}</div>
        </div>
    </div>
    <div class="quality-overview">
        <div class="quality-ring"
            style="--quality:{{ $score }};--quality-color:{{ $score >= 95 ? 'var(--md-success)' : ($score >= 85 ? 'var(--md-warning)' : 'var(--md-error)') }}">
            <strong>{{ $score }}%</strong>
        </div>
        <div>
            <div class="panel-title">Data quality overview</div>
            <div class="panel-subtitle">Completeness, valid dates and duplicate checks are combined into one decision-ready
                score.</div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:9px">
                <span class="status-chip status-chip--good">{{ $bySeverity['low'] ?? 0 }} low priority</span>
                <span class="status-chip status-chip--warn">{{ $bySeverity['medium'] ?? 0 }} medium</span>
                <span
                    class="status-chip status-chip--bad">{{ ($bySeverity['critical'] ?? 0) + ($bySeverity['high'] ?? 0) }}
                    urgent</span>
            </div>
        </div>
    </div>
    <section class="workforce-panel">
        <h2 class="md-title-lg">Records Requiring Attention</h2>
        <div style="overflow:auto;margin-top:12px">
            <table class="md-table" style="width:100%">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Issue</th>
                        <th>Severity</th>
                        <th>Status</th>
                        <th>Correction</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($persistent as $issue)
                        <tr>
                            <td>
                                <a href="{{ route('employees.show', $issue->employee) }}">
                                    <strong>{{ $issue->employee?->display_name }}</strong>
                                </a>
                                <br>
                                <small>{{ $issue->employee?->pay_no ?: '—' }} ·
                                    {{ $issue->employee?->subjectCode?->code ?? '—' }}</small>
                            </td>
                            <td>{{ $issue->label }}</td>
                            <td>
                                <span
                                    class="status-chip {{ in_array($issue->severity, ['critical', 'high']) ? 'status-chip--bad' : ($issue->severity === 'medium' ? 'status-chip--warn' : '') }}">{{ ucfirst($issue->severity) }}</span>
                            </td>
                            <td>{{ ucwords(str_replace('_', ' ', $issue->status)) }}</td>
                            <td>
                                <details>
                                    <summary style="cursor:pointer;color:var(--md-primary)">Update</summary>
                                    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:8px">
                                        <a class="md-btn md-btn--ghost"
                                            href="{{ route('employees.edit', $issue->employee) }}">Correct Record</a>
                                        <form method="POST" action="{{ route('data-quality.update', $issue) }}">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="status" value="corrected">
                                            <input type="hidden" name="resolution_note"
                                                value="Record corrected by officer">
                                            <button class="md-btn--primary">Mark Corrected</button>
                                        </form>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align:center;padding:30px">No open employee data-quality issues.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
