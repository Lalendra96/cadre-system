<form method="POST" action="{{ $action }}" style="margin-top:14px">
    @csrf
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px">
        <label>
            <span class="md-body-sm">Official planning purpose *</span>
            <textarea class="md-input" name="administrative_purpose" rows="3" required placeholder="Why is this review/referral required for hospital planning?">{{ old('administrative_purpose') }}</textarea>
        </label>
        <label>
            <span class="md-body-sm">Authority / evidence reference *</span>
            <textarea class="md-input" name="authority_reference" rows="3" required placeholder="Circular, minute, approved plan, report, dataset reference or other authority">{{ old('authority_reference', $assessment->authority_reference) }}</textarea>
        </label>
    </div>
    @foreach ([
        'evidence_reviewed' => 'I reviewed the supporting evidence and material assumptions.',
        'accuracy_confirmed' => 'I confirm the recorded summary accurately represents the planning analysis to the best of my knowledge.',
        'minimum_necessary_confirmed' => 'Only minimum necessary information is included for this official planning purpose.',
        'no_conflict_confirmed' => 'I am acting within my authorised planning role and have not treated this recommendation as a final approval.',
    ] as $field => $label)
        <label style="display:flex;gap:10px;align-items:flex-start;margin-top:10px">
            <input type="checkbox" name="{{ $field }}" value="1" required>
            <span>{{ $label }}</span>
        </label>
    @endforeach
    <button class="md-btn md-btn--primary" type="submit" style="margin-top:14px">{{ $button }}</button>
</form>
