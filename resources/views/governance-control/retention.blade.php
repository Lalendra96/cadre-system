@extends('layouts.app')
@section('content')
    @include('enterprise._head', [
        'title' => 'Records Retention, Archive & Legal Hold',
        'subtitle' =>
            'Track record lifecycle, approved retention periods, disposal eligibility and legal/administrative holds without losing disposition evidence.',
    ])
    <div class="enterprise-section enterprise-card">
        <h2>Create retention record</h2>
        <form class="enterprise-form" method="POST" action="{{ route('governance-control.retention.store') }}">@csrf
            <input name="record_type" placeholder="Record type" required><input type="number" name="record_id"
                placeholder="System record ID"><input name="record_reference"
                placeholder="File / decision / case reference"><input name="title" placeholder="Record title"
                required><input type="date" name="closed_on"><input type="date" name="archived_on"><input
                type="number" name="retention_years" min="0" max="100"
                placeholder="Approved retention years"><label><input type="checkbox" name="legal_hold" value="1"> Legal
                / administrative hold</label>
            <textarea class="span2" name="hold_reason" placeholder="Hold reason (if applicable)"></textarea><button class="md-btn md-btn--filled" type="submit">Create retention record</button>
        </form>
    </div>
    <div class="enterprise-section enterprise-card">
        <h2>Retention register</h2>
        <table class="enterprise-table">
            <tr>
                <th>Record</th>
                <th>Status</th>
                <th>Archived</th>
                <th>Retention</th>
                <th>Eligible disposal</th>
                <th>Governance actions</th>
            </tr>
            @forelse($cases as $r)
                <tr>
                    <td><strong>{{ $r->record_type }}</strong><br>{{ $r->record_reference ?: '#' . $r->record_id }} ·
                        {{ $r->title }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $r->status)) }}@if ($r->legal_hold)
                            <br><span class="status-warn">LEGAL / ADMIN HOLD</span>
                        @endif
                    </td>
                    <td>{{ $r->archived_on ?: '—' }}</td>
                    <td>{{ is_null($r->retention_years) ? 'Policy not assigned' : $r->retention_years . ' years' }}</td>
                    <td>{{ $r->eligible_disposal_on ?: '—' }}</td>
                    <td>
                        @if ($r->legal_hold)
                            <small>{{ $r->hold_reason }}</small>
                        @endif
                        <form method="POST" action="{{ route('governance-control.retention.action', $r->id) }}">@csrf
                            <select name="action">
                                <option value="set_retention">Assign / update retention policy</option>
                                <option value="archive">Archive</option>
                                <option value="hold">Place hold</option>
                                <option value="release_hold">Release hold</option>
                                <option value="authorize_disposal">Authorize disposal</option>
                                <option value="dispose">Record disposal</option>
                            </select>
                            <input type="number" min="0" max="100" name="retention_years"
                                value="{{ $r->retention_years }}" placeholder="Retention years">
                            <input name="reason" placeholder="Reason (required for hold)">
                            <input name="evidence_reference" placeholder="Disposition evidence/reference">
                            <button class="md-btn md-btn--filled" type="submit">Apply</button>
                        </form>
                        @if ($r->disposed_at)
                            <small>Disposed {{ $r->disposed_at }} · Evidence:
                                {{ $r->disposal_evidence_reference }}</small>
                        @endif
                    </td>
                </tr>
                @empty<tr>
                        <td colspan="6">No retention records yet.</td>
                    </tr>
                @endforelse
            </table>
        </div>
    @endsection
