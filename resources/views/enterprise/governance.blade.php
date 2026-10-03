@extends('layouts.app')
@section('content')
    @include('enterprise._head', [
        'title' => 'Governance & Compliance',
        'subtitle' =>
            'Authorities, circulars, service minutes, approval matrices, delegations, exceptions, audit evidence and PDPA-oriented accountability controls.',
    ])
    <div class="enterprise-grid">
        <div class="enterprise-card">
            <h3>Governance Compliance Dashboard</h3>
            <p>Executive control view for missing authority, SoD overrides, regulatory implementation, retention and
                handovers.</p><a class="enterprise-link" href="{{ route('governance-control.dashboard') }}">Open dashboard
                →</a>
        </div>
        <div class="enterprise-card">
            <h3>Administrative Decision Register</h3>
            <p>Authoritative administrative decisions with effective dates, approval workflow, evidence and provenance.</p>
            <a class="enterprise-link" href="{{ route('governance-control.decisions') }}">Decision register →</a>
        </div>
        <div class="enterprise-card">
            <h3>Versioned Rules</h3>
            <p>Effective-dated rule versions that preserve the authority applicable to historical decisions.</p><a
                class="enterprise-link" href="{{ route('governance-control.rules') }}">Rule versions →</a>
        </div>
        <div class="enterprise-card">
            <h3>Regulatory Change</h3>
            <p>Track circulars and service minutes from applicability assessment through implementation and testing
                evidence.</p><a class="enterprise-link" href="{{ route('governance-control.regulatory') }}">Regulatory
                changes →</a>
        </div>
        <div class="enterprise-card">
            <h3>Records Retention</h3>
            <p>Archive, retention, disposal eligibility and legal/administrative hold controls.</p><a
                class="enterprise-link" href="{{ route('governance-control.retention') }}">Retention register →</a>
        </div>
        <div class="enterprise-card">
            <h3>Formal Handovers</h3>
            <p>Signed-off responsibility snapshots with incoming/outgoing officer and supervisor verification.</p><a
                class="enterprise-link" href="{{ route('governance-control.handovers') }}">Handover register →</a>
        </div>
        <div class="enterprise-card">
            <h3>Governance Register</h3>
            <p>Existing business rules, versions, authority/source records and configuration history.</p><a
                class="enterprise-link" href="{{ route('governance.index') }}">Open register →</a>
        </div>
        <div class="enterprise-card">
            <h3>Circular Library</h3>
            <p>Controlled circular distribution and reference material.</p><a class="enterprise-link"
                href="{{ route('circulars.index') }}">Circulars →</a>
        </div>
        <div class="enterprise-card">
            <h3>Audit Evidence</h3>
            <p>Application and export evidence for accountability and investigation support.</p><a class="enterprise-link"
                href="{{ route('audit-logs.index') }}">Audit logs →</a>
        </div>
    </div>
    @if (auth()->user()->isSuperAdmin())
        <div class="enterprise-section enterprise-card">
            <h2>Add approval authority</h2>
            <form class="enterprise-form" method="POST" action="{{ route('enterprise.governance.authorities.store') }}">
                @csrf<input name="process_key" placeholder="Process key" required><input name="process_label"
                    placeholder="Process label" required><input name="authority_source"
                    placeholder="Authority / service minute"><input name="reference_no" placeholder="Reference no"><input
                    name="preparer_role" placeholder="Preparer role"><input name="checker_role"
                    placeholder="Checker role"><input name="approver_role" placeholder="Approver role" required><input
                    type="date" name="effective_from"><input type="date" name="effective_to"><button
                    class="md-btn md-btn--filled" type="submit">Register authority</button></form>
        </div>
        <div class="enterprise-section enterprise-card">
            <h2>Add delegation</h2>
            <form class="enterprise-form" method="POST" action="{{ route('enterprise.governance.delegations.store') }}">
                @csrf<select name="from_user_id" required>
                    <option value="">Delegating officer…</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} · {{ $u->email }}</option>
                    @endforeach
                </select>
                <select name="to_user_id" required>
                    <option value="">Delegate officer…</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} · {{ $u->email }}</option>
                    @endforeach
                </select>
                <input name="scope" placeholder="Delegated scope" required><input name="reference_no"
                    placeholder="Reference no"><input type="date" name="starts_on" required><input type="date"
                    name="ends_on" required>
                <textarea class="span2" name="conditions" placeholder="Conditions / limits"></textarea><button class="md-btn md-btn--filled" type="submit">Record delegation</button>
            </form>
        </div>
    @endif
    <div class="enterprise-section enterprise-card">
        <h2>Open governance exception</h2>
        <form class="enterprise-form" method="POST" action="{{ route('enterprise.governance.exceptions.store') }}">
            @csrf<input name="area" placeholder="Area" required><input name="title" placeholder="Exception title"
                required><input type="date" name="review_due_on">
            <textarea class="span2" name="reason" placeholder="Reason / deviation" required></textarea>
            <textarea class="span2" name="mitigation" placeholder="Compensating control / mitigation"></textarea><button class="md-btn md-btn--filled" type="submit">Open exception</button>
        </form>
    </div>
    <div class="enterprise-section enterprise-card">
        <h2>Current approval matrix</h2>
        <table class="enterprise-table">
            <tr>
                <th>Process</th>
                <th>Source</th>
                <th>Prepare</th>
                <th>Check</th>
                <th>Approve</th>
                <th>Effective</th>
            </tr>
            @forelse($authorities as $r)
                <tr>
                    <td>{{ $r->process_label }}</td>
                    <td>{{ $r->authority_source }} {{ $r->reference_no }}</td>
                    <td>{{ $r->preparer_role }}</td>
                    <td>{{ $r->checker_role }}</td>
                    <td>{{ $r->approver_role }}</td>
                    <td>{{ $r->effective_from }} – {{ $r->effective_to ?: 'Open' }}</td>
            </tr>@empty<tr>
                    <td colspan="6">No matrix entries yet. Run migrations and register authorities.</td>
                </tr>
            @endforelse
        </table>
    </div>
    <div class="enterprise-section enterprise-card">
        <h2>Delegations & exceptions</h2>
        <table class="enterprise-table">
            <tr>
                <th>Type</th>
                <th>Scope / title</th>
                <th>Status / period</th>
            </tr>
            @foreach ($delegations as $r)
                <tr>
                    <td>Delegation</td>
                    <td>{{ $r->scope }}</td>
                    <td>{{ $r->starts_on }} – {{ $r->ends_on }}</td>
                </tr>
                @endforeach @foreach ($exceptions as $r)
                    <tr>
                        <td>Exception</td>
                        <td>{{ $r->area }} · {{ $r->title }}</td>
                        <td>{{ $r->status }} @if ($r->review_due_on)
                                · review {{ $r->review_due_on }}
                            @endif
                        </td>
                    </tr>
                @endforeach
        </table>
    </div>
@endsection
