@extends('layouts.app')
@section('content')
    @include('enterprise._head', [
        'title' => 'System Administration & Assurance',
        'subtitle' =>
            'MFA, access reviews, auditability, backup/restore evidence, scheduler/job health, integrations and security assurance for administrators.',
    ])
    <div class="enterprise-grid">
        <div class="enterprise-kpi"><span>Database</span><strong
                class="{{ $database === 'connected' ? 'status-good' : 'status-bad' }}">{{ ucfirst($database) }}</strong>
        </div>
        <div class="enterprise-kpi"><span>Failed jobs</span><strong>{{ number_format($failedJobs) }}</strong></div>
        <div class="enterprise-kpi"><span>MFA enabled</span><strong>{{ $mfaEnabled }}/{{ $activeUsers }}</strong></div>
        <div class="enterprise-kpi"><span>Last intelligence run</span><strong
                style="font-size:15px">{{ $scheduler ?: 'Not recorded' }}</strong></div>
    </div>
    <div class="enterprise-grid">
        <div class="enterprise-card">
            <h3>Audit & Export Evidence</h3>
            <p>Review application events and report/export accountability.</p><a class="enterprise-link"
                href="{{ route('audit-logs.index') }}">Audit logs</a>
        </div>
        <div class="enterprise-card">
            <h3>Existing Health Monitor</h3>
            <p>Database, scheduler and failed-job status from the current health page.</p><a class="enterprise-link"
                href="{{ route('admin.workforce-health') }}">Health monitor</a>
        </div>
        <div class="enterprise-card">
            <h3>MFA & User Access</h3>
            <p>Use user administration with periodic review evidence below.</p><a class="enterprise-link"
                href="{{ route('users.index') }}">Users</a>
        </div>
    </div>
    <div class="enterprise-section enterprise-card">
        <h2>Access review evidence</h2>
        <form class="enterprise-form" method="POST" action="{{ route('enterprise.assurance.access-reviews.store') }}">
            @csrf<input name="review_period" placeholder="e.g. 2026 Q3" required><input name="scope" placeholder="Scope"
                required><select name="status">
                <option value="planned">Planned</option>
                <option value="in_progress">In progress</option>
                <option value="completed">Completed</option>
            </select><input type="date" name="due_on"><input type="number" min="0" name="accounts_reviewed"
                placeholder="Accounts reviewed"><input type="number" min="0" name="exceptions_found"
                placeholder="Exceptions">
            <textarea class="span2" name="evidence_reference" placeholder="Evidence reference / file location"></textarea><button class="md-btn md-btn--filled">Save access review</button>
        </form>
    </div>
    <div class="enterprise-section enterprise-card">
        <h2>Backup & restore evidence</h2>
        <form class="enterprise-form" method="POST" action="{{ route('enterprise.assurance.backups.store') }}">@csrf<input
                name="backup_type" value="database" placeholder="Backup type"><input type="datetime-local" name="backup_at"
                required><select name="status">
                <option value="successful">Successful</option>
                <option value="partial">Partial</option>
                <option value="failed">Failed</option>
            </select><input name="storage_reference" placeholder="Storage/evidence reference"><input type="datetime-local"
                name="restore_tested_at"><select name="restore_result">
                <option value="not_tested">Not tested</option>
                <option value="successful">Successful</option>
                <option value="partial">Partial</option>
                <option value="failed">Failed</option>
            </select>
            <textarea class="span2" name="restore_evidence" placeholder="Restore test evidence / notes"></textarea><button class="md-btn md-btn--filled">Record backup evidence</button>
        </form>
    </div>
    <div class="enterprise-section enterprise-card">
        <h2>Integration health</h2>
        <form class="enterprise-form" method="POST" action="{{ route('enterprise.assurance.integrations.store') }}">
            @csrf<input name="name" placeholder="Integration name" required><input name="integration_type"
                placeholder="Type"><input name="endpoint_label" placeholder="Endpoint label (not secret)"><select
                name="status">
                <option value="healthy">Healthy</option>
                <option value="degraded">Degraded</option>
                <option value="down">Down</option>
                <option value="unknown">Unknown</option>
            </select><input type="datetime-local" name="last_checked_at"><input type="datetime-local"
                name="last_success_at"><input type="date" name="certificate_expires_on">
            <textarea class="span2" name="notes" placeholder="Notes (do not store passwords/secrets)"></textarea><button class="md-btn md-btn--filled">Add health record</button>
        </form>
    </div>
    <div class="enterprise-section enterprise-grid">
        <div class="enterprise-card">
            <h3>Recent access reviews</h3>
            @forelse($accessReviews as $r)
            <p><strong>{{ $r->review_period }}</strong> · {{ $r->scope }} · {{ $r->status }}</p>@empty<p>No
                    evidence recorded.</p>
            @endforelse
        </div>
        <div class="enterprise-card">
            <h3>Recent backups</h3>
            @forelse($backups as $r)
                <p><strong>{{ $r->backup_at }}</strong> · {{ $r->status }} · restore
                {{ $r->restore_result ?: 'not tested' }}</p>@empty<p>No evidence recorded.</p>
            @endforelse
        </div>
        <div class="enterprise-card">
            <h3>Integrations</h3>
            @forelse($integrations as $r)
                <p><strong>{{ $r->name }}</strong> · {{ $r->status }} @if ($r->certificate_expires_on)
                        · cert {{ $r->certificate_expires_on }}
                    @endif
                </p>
            @empty<p>No integration health records.</p>
            @endforelse
        </div>
    </div>
@endsection
