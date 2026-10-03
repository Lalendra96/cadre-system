@extends('layouts.app')
@section('content')
    @include('enterprise._head', [
        'title' => 'Administrative Decision Register',
        'subtitle' =>
            'Government/administrative decisions linked to authority, rule version, evidence, effective date and enforced segregation of duties.',
    ])
    @include('partials.governance-legal-safeguard', [
        'title' => 'Decision safeguard — verify before progressing',
        'purpose' => 'This register supports accountable administrative decisions. A dashboard signal, recommendation or system calculation is not itself an approval authority.',
        'items' => [
            'Confirm the underlying source record, applicable rule/version and authority reference before changing workflow status.',
            'Use only information necessary for this decision; avoid copying unrelated personal information into free-text notes.',
            'Keep maker/checker/recommender/approver roles independent. Use an override only when formally authorised and document why.',
            'Approval must be explainable later from the recorded reason, evidence, rule/version and authority reference.',
            'Corrections must preserve history; do not replace evidence in a way that hides the earlier record.',
        ],
    ])

    <div class="enterprise-note">
        <strong>SoD control:</strong> each consequential decision follows Prepared → Checked → Recommended → Approved.
        Transactional decisions apply their underlying HR change only at final approval. Exceptional overrides require both
        a reason and an authority/reference and remain visible in the audit history.
    </div>

    <div class="enterprise-section enterprise-card">
        <h2>Register establishment / administrative decision</h2>
        <form class="enterprise-form" method="POST" action="{{ route('governance-control.decisions.store') }}">@csrf
            <input name="decision_type" placeholder="Decision type (establishment / policy / administrative order...)"
                required><input name="title" placeholder="Decision title" required>
            <select name="employee_id">
                <option value="">No single employee / establishment decision</option>
                @foreach ($employees as $e)
                    <option value="{{ $e->id }}">{{ $e->name }}@if ($e->pay_no)
                            · {{ $e->pay_no }}
                        @endif
                    </option>
                @endforeach
            </select>
            <select name="approval_authority_id">
                <option value="">Approval matrix entry (optional)</option>
                @foreach ($authorities as $a)
                    <option value="{{ $a->id }}">{{ $a->process_label }} · {{ $a->authority_source }}</option>
                @endforeach
            </select>
            <select name="rule_version_id">
                <option value="">Rule version (optional)</option>
                @foreach ($rules as $r)
                    <option value="{{ $r->id }}">{{ $r->rule_key }} v{{ $r->version_no }} · {{ $r->title }}
                    </option>
                @endforeach
            </select>
            <input name="authority_reference" placeholder="Authority / circular / service minute reference"><input
                type="date" name="effective_date"><input name="source_type" placeholder="Linked source type"><input
                type="number" name="source_id" placeholder="Linked source ID">
            <textarea class="span2" name="decision_text" placeholder="Decision / administrative order text"></textarea>
            <textarea class="span2" name="supporting_evidence" placeholder="Supporting document / minute / file references"></textarea>
            <textarea name="provenance_source" placeholder="Provenance: source facts used"></textarea>
            <textarea name="provenance_method" placeholder="Provenance: rule/calculation/method"></textarea><button class="md-btn md-btn--filled" type="submit">Register draft decision</button>
        </form>
    </div>

    <div class="enterprise-section enterprise-card">
        <h2>Decision register</h2>
        <table class="enterprise-table">
            <tr>
                <th>Decision</th>
                <th>Employee</th>
                <th>Workflow</th>
                <th>Authority / provenance</th>
                <th>Controlled action</th>
            </tr>
            @forelse($decisions as $r)
                @php
                    $next = in_array($r->status, ['draft', 'pending'], true)
                        ? 'check'
                        : ($r->status === 'checked'
                            ? 'recommend'
                            : ($r->status === 'recommended'
                                ? 'approve'
                                : null));
                @endphp
                <tr>
                    <td><strong>{{ $r->decision_no ?: '#' . $r->id }}</strong><br>{{ $r->title ?: $r->summary }}<br><small>{{ $r->effective_date ?: 'No effective date' }}</small>
                    </td>
                    <td>{{ $r->employee_name ?: 'Establishment / multiple' }}</td>
                    <td>{{ ucfirst($r->status) }}<br><small>P: {{ $r->preparer_name ?: '—' }} · C:
                            {{ $r->checker_name ?: '—' }} · R: {{ $r->recommender_name ?: '—' }} · A:
                            {{ $r->approver_name ?: '—' }}</small>
                        @if ($r->sod_override)
                            <br><span class="status-warn">SoD override: {{ $r->sod_override_authority }}</span>
                        @endif
                    </td>
                    <td>{{ $r->authority_reference ?: ($r->approval_authority_id ? 'Matrix #' . $r->approval_authority_id : ($r->source_reference ?: 'Missing authority')) }}
                        @if ($r->rule_version_id)
                            <br>Rule version #{{ $r->rule_version_id }}@else<br><span class="status-warn">No versioned rule
                                linked</span>
                        @endif
                    </td>
                    <td>
                        @if ($next)
                            <form method="POST" action="{{ route('governance-control.decisions.advance', $r->id) }}">@csrf
                                <input type="hidden" name="action" value="{{ $next }}">
                                <textarea name="decision_reason"
                                    placeholder="{{ $next === 'approve' ? 'Final decision / authority checked' : 'Review note' }}"></textarea>
                                <input name="administrative_purpose" required maxlength="500" placeholder="Official administrative purpose for this {{ $next }} action">
                                <div class="enterprise-note" style="margin:8px 0;padding:10px">
                                    <strong>Required safeguard confirmation</strong><br>
                                    <label><input type="checkbox" name="evidence_reviewed" value="1" required> I reviewed the source evidence relevant to this action.</label><br>
                                    <label><input type="checkbox" name="accuracy_confirmed" value="1" required> I checked the material facts for accuracy.</label><br>
                                    <label><input type="checkbox" name="minimum_necessary_confirmed" value="1" required> I used only information necessary for this official purpose.</label><br>
                                    <label><input type="checkbox" name="no_conflict_confirmed" value="1" required> I am not knowingly bypassing segregation-of-duties controls.</label>
                                    @if ($next === 'approve')
                                        <br><label><input type="checkbox" name="authority_confirmed" value="1" required> I verified the applicable approval authority/reference and rule/version.</label>
                                    @endif
                                </div>
                                <details>
                                    <summary>Exceptional SoD override</summary><input name="override_authority"
                                        placeholder="Override authority/reference">
                                    <textarea name="override_reason" placeholder="Override reason"></textarea>
                                </details>
                                <button class="md-btn md-btn--filled" type="submit">{{ ucfirst($next) }}</button>
                            </form>
                            @if (array_key_exists($r->decision_type, \App\Models\AdministrativeDecision::TYPES))
                                <a class="enterprise-link"
                                    href="{{ route('administrative-decisions.show', $r->id) }}">Detailed transactional
                                    review →</a>
                            @endif
                        @elseif($r->status === 'approved')
                            <span class="status-good">Approved</span>
                        @else
                            <span>{{ ucfirst($r->status) }}</span>
                        @endif
                    </td>
                </tr>
            @empty<tr>
                    <td colspan="5">No decisions yet.</td>
                </tr>
            @endforelse
        </table>
    </div>
@endsection
