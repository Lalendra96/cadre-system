@extends('layouts.app')
@section('content')
    @include('enterprise._head', [
        'title' => 'Formal Subject Officer Handover',
        'subtitle' =>
            'Create a signed-off point-in-time responsibility snapshot when work moves between officers.',
    ])
    <div class="enterprise-note">
        The snapshot captures the outgoing officer's current employee files and pending HR work at creation time. Later
        changes do not rewrite the certificate evidence.</div>
    <div class="enterprise-section enterprise-card">
        <h2>Create handover certificate</h2>
        <form class="enterprise-form" method="POST" action="{{ route('governance-control.handovers.store') }}">@csrf<select
                name="from_user_id" required>
                <option value="">Outgoing officer</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
            </select>
            <select name="to_user_id" required>
                <option value="">Incoming officer</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
            </select>
            <select name="supervisor_user_id" required>
                <option value="">Supervisor / verifier *</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
            </select>
            <input type="date" name="effective_on" required>
            <textarea class="span2" name="notes" placeholder="Handover notes / outstanding matters"></textarea><button class="md-btn md-btn--filled" type="submit">Create snapshot</button>
        </form>
    </div>
    <div class="enterprise-section enterprise-card">
        <h2>Handover register</h2>
        <table class="enterprise-table">
            <tr>
                <th>Certificate</th>
                <th>Officers</th>
                <th>Snapshot</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            @forelse($handovers as $r)
                @php
                    $s = json_decode($r->workload_snapshot ?: '{}', true) ?: [];
                @endphp
                <tr>
                    <td><strong>{{ $r->handover_no }}</strong><br>Effective {{ $r->effective_on }}</td>
                    <td>{{ $r->from_name }} → {{ $r->to_name }}<br><small>Supervisor:
                            {{ $r->supervisor_name ?: 'Not assigned' }}</small></td>
                    <td>Files {{ $s['employee_files'] ?? 0 }} · Promotions {{ $s['pending_promotions'] ?? 0 }} ·
                        Increments
                        {{ $s['increment_cases'] ?? 0 }} · Retirements {{ $s['retirement_cases'] ?? 0 }} · Decisions
                        {{ $s['pending_decisions'] ?? 0 }} · Corrections {{ $s['pending_corrections'] ?? 0 }} · Letters
                        {{ $s['service_letters'] ?? 0 }} · Regulatory {{ $s['regulatory_actions'] ?? 0 }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $r->status)) }}@if ($r->content_hash)
                            <br><small>Hash {{ substr($r->content_hash, 0, 16) }}…</small>
                        @endif
                    </td>
                    <td>
                        <form method="POST" action="{{ route('governance-control.handovers.acknowledge', $r->id) }}">
                            @csrf<select name="action">
                                <option value="handover">Outgoing sign-off</option>
                                <option value="accept">Incoming accept</option>
                                <option value="verify">Supervisor verify</option>
                            </select><button class="md-btn md-btn--filled" type="submit">Record</button></form><a
                            class="enterprise-link" href="{{ route('governance-control.handovers.pdf', $r->id) }}">PDF</a>
                    </td>
                </tr>@empty<tr>
                        <td colspan="5">No handover certificates yet.</td>
                    </tr>
                @endforelse
            </table>
        </div>
    @endsection
