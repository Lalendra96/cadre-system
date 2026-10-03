@extends('layouts.app')
@section('title', 'Governance Safeguard Attestations')
@section('content')
    <div class="page-shell">
        <div class="page-header">
            <div>
                <div class="page-eyebrow">Governance evidence</div>
                <h1 class="page-title">Safeguard Attestation Register</h1>
                <p class="page-subtitle">Read-only evidence of high-impact actions where the acting officer explicitly confirmed purpose, source/evidence review, accuracy, minimum-necessary use and role safeguards.</p>
            </div>
            <a class="md-btn md-btn--text" href="{{ route('governance-control.dashboard') }}">← Governance Control</a>
        </div>

        @include('partials.governance-legal-safeguard', [
            'title' => 'How to interpret this register',
            'purpose' => 'An attestation strengthens accountability but does not by itself prove that an action was lawful or correct. Review the underlying authority, source evidence, workflow and audit trail when investigating a case.',
            'items' => [
                'Use the register to reconstruct who acted, for what stated official purpose and under what reference.',
                'Compare the attestation with the underlying record and normal audit log before reaching a conclusion.',
                'Do not treat absence of an attestation for older records as proof of wrongdoing; the safeguard may have been introduced later.',
                'IP and user-agent values are security/audit metadata and should be accessed only for authorised assurance work.',
            ],
        ])

        <section class="md-card" style="padding:16px;margin-bottom:16px">
            <form method="GET" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;align-items:end">
                <label class="md-field"><span>Action</span><select name="action_key"><option value="">All actions</option>@foreach($actionKeys as $key)<option value="{{ $key }}" @selected(request('action_key')===$key)>{{ ucwords(str_replace('_',' ', $key)) }}</option>@endforeach</select></label>
                <label class="md-field"><span>From</span><input type="date" name="from" value="{{ request('from') }}"></label>
                <label class="md-field"><span>To</span><input type="date" name="to" value="{{ request('to') }}"></label>
                <button class="md-btn md-btn--filled" type="submit">Apply Filters</button>
            </form>
        </section>

        <section class="md-card" style="padding:0;overflow:auto">
            <table class="md-table" style="width:100%">
                <thead><tr><th>When / Officer</th><th>Action / Record</th><th>Official Purpose</th><th>Authority</th><th>Confirmed Safeguards</th></tr></thead>
                <tbody>
                @forelse($attestations as $row)
                    <tr>
                        <td><strong>{{ \Carbon\Carbon::parse($row->attested_at)->format('d M Y H:i') }}</strong><br><span class="md-body-sm">{{ $row->user_name }} · {{ $row->user_email }}</span></td>
                        <td><strong>{{ ucwords(str_replace('_',' ', $row->action_key)) }}</strong><br><span class="md-body-sm">{{ class_basename($row->resource_type) }}{{ $row->resource_id ? ' #'.$row->resource_id : '' }}</span></td>
                        <td>{{ $row->administrative_purpose }}</td>
                        <td>{{ $row->authority_reference ?: 'Not separately recorded' }}</td>
                        <td class="md-body-sm">
                            Evidence {{ $row->evidence_reviewed ? '✓' : '—' }} · Accuracy {{ $row->accuracy_confirmed ? '✓' : '—' }}<br>
                            Minimum necessary {{ $row->minimum_necessary_confirmed ? '✓' : '—' }} · Role/SoD {{ $row->no_conflict_confirmed ? '✓' : '—' }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="padding:28px;text-align:center">No safeguard attestations recorded for the selected filters.</td></tr>
                @endforelse
                </tbody>
            </table>
        </section>
        <div style="margin-top:14px">{{ $attestations->links() }}</div>
    </div>
@endsection
