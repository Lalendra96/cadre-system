@extends('layouts.app')
@section('content')
    @include('enterprise._head', [
        'title' => 'Governance Compliance Dashboard',
        'subtitle' =>
            'Executive control view for decisions, authority, segregation of duties, regulatory implementation, records retention and administrative assurance.',
    ])
    @php
        $governanceMetrics = [
            ['label' => 'Draft / in-review decisions', 'value' => $metrics['draft_decisions'] ?? 0],
            ['label' => 'Decisions missing authority', 'value' => $metrics['missing_authority'] ?? 0],
            ['label' => 'Decisions missing provenance', 'value' => $metrics['missing_provenance'] ?? 0],
            ['label' => 'Decisions without versioned rule', 'value' => $metrics['missing_rule'] ?? 0],
            ['label' => 'SoD overrides', 'value' => $metrics['sod_overrides'] ?? 0],
            ['label' => 'Expired delegations', 'value' => $metrics['expired_delegations'] ?? 0],
            ['label' => 'Regulatory changes open', 'value' => $metrics['regulatory_open'] ?? 0],
            ['label' => 'Regulatory changes overdue', 'value' => $metrics['regulatory_overdue'] ?? 0],
            ['label' => 'Records due for disposal', 'value' => $metrics['retention_due'] ?? 0],
            ['label' => 'Retention policy not assigned', 'value' => $metrics['retention_policy_missing'] ?? 0],
            ['label' => 'Pending handovers', 'value' => $metrics['handover_pending'] ?? 0],
            ['label' => 'High-severity DQ issues', 'value' => $metrics['high_dq'] ?? 0],
            ['label' => 'Access reviews overdue', 'value' => $metrics['access_review_overdue'] ?? 0],
            ['label' => 'Restore evidence missing', 'value' => $metrics['restore_overdue'] ?? 0],
            ['label' => 'Safeguard attestations · 30 days', 'value' => $metrics['safeguard_attestations_30d'] ?? 0],
        ];
    @endphp
    @include('partials.governance-legal-safeguard', [
        'title' => 'How to use this governance dashboard',
        'purpose' => 'These indicators identify records that need review. They do not replace the underlying file, legal authority, circular, service minute or an authorised human decision.',
        'items' => [
            'Open the underlying governed register before taking action on a red/amber signal.',
            'Treat missing authority, provenance, rule version or evidence as a control gap to resolve — not as permission to proceed.',
            'Use the formal workflow for approval, override, retention/disposal and handover actions so the evidentiary trail remains reconstructable.',
            'Do not infer misconduct or employee fault from an exception count alone; verify the source record and context.',
        ],
    ])

    <div class="enterprise-grid">
        @foreach ($governanceMetrics as $metric)
            <div class="enterprise-kpi">
                <span>{{ $metric['label'] }}</span>
                <strong>{{ number_format($metric['value']) }}</strong>
            </div>
        @endforeach
    </div>
    <div class="enterprise-grid">
        <div class="enterprise-card">
            <h3>Administrative Decision Register</h3>
            <p>Authoritative decisions with rule provenance, evidence and enforced maker/checker/recommender/approver
                separation.</p><a class="enterprise-link" href="{{ route('governance-control.decisions') }}">Open decisions
                →</a>
        </div>
        <div class="enterprise-card">
            <h3>Versioned Governance Rules</h3>
            <p>Effective-dated rules preserved by version so historical decisions keep the rule that applied at the time.
            </p><a class="enterprise-link" href="{{ route('governance-control.rules') }}">Open rules →</a>
        </div>
        <div class="enterprise-card">
            <h3>Regulatory Change Management</h3>
            <p>Track circular/service-minute applicability through implementation, testing and retained evidence.</p><a
                class="enterprise-link" href="{{ route('governance-control.regulatory') }}">Open changes →</a>
        </div>
        <div class="enterprise-card">
            <h3>Records Retention & Legal Hold</h3>
            <p>Archive, retention, disposal eligibility and legal/administrative hold controls.</p><a
                class="enterprise-link" href="{{ route('governance-control.retention') }}">Open retention →</a>
        </div>
        <div class="enterprise-card">
            <h3>Formal Officer Handovers</h3>
            <p>Point-in-time workload snapshot with outgoing, incoming and supervisor acknowledgement plus PDF evidence.</p>
            <a class="enterprise-link" href="{{ route('governance-control.handovers') }}">Open handovers →</a>
        </div>
        <div class="enterprise-card">
            <h3>Safeguard Attestation Register</h3>
            <p>Read-only evidence of stated purpose, evidence review, accuracy confirmation, minimum-necessary use and role safeguards for high-impact actions.</p>
            <a class="enterprise-link" href="{{ route('governance-control.attestations') }}">Open attestations →</a>
        </div>
    </div>
    <div class="enterprise-section enterprise-card">
        <h2>Recent administrative decisions</h2>
        <table class="enterprise-table">
            <tr>
                <th>Decision</th>
                <th>Type</th>
                <th>Employee</th>
                <th>Prepared by</th>
                <th>Status</th>
                <th>Authority</th>
            </tr>
            @forelse($decisions as $r)
                <tr>
                    <td><strong>{{ $r->decision_no }}</strong><br>{{ $r->title }}</td>
                    <td>{{ $r->decision_type }}</td>
                    <td>{{ $r->employee_name ?: '—' }}</td>
                    <td>{{ $r->preparer_name }}</td>
                    <td>{{ ucfirst($r->status) }}@if ($r->sod_override)
                            · <span class="status-warn">SoD override</span>
                        @endif
                    </td>
                    <td>{{ $r->authority_reference ?: ($r->approval_authority_id ? 'Matrix #' . $r->approval_authority_id : 'Missing') }}
                    </td>
                </tr>@empty<tr>
                        <td colspan="6">No administrative decisions have been registered yet.</td>
                    </tr>
                @endforelse
            </table>
        </div>
        <div class="enterprise-section enterprise-card">
            <h2>Regulatory implementation watch</h2>
            <table class="enterprise-table">
                <tr>
                    <th>Change</th>
                    <th>Source</th>
                    <th>Status</th>
                    <th>Target effective</th>
                </tr>
                @forelse($changes as $r)
                    <tr>
                        <td>{{ $r->change_no }} · {{ $r->title }}</td>
                        <td>{{ $r->source_type }} {{ $r->source_reference }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $r->status)) }}</td>
                        <td>{{ $r->target_effective_on ?: '—' }}</td>
                </tr>@empty<tr>
                        <td colspan="4">No regulatory changes registered.</td>
                    </tr>
                @endforelse
            </table>
        </div>
    @endsection
