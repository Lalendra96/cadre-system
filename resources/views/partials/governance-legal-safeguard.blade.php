@php
    $safeguardTitle = $title ?? 'Governance & legal safeguard';
    $safeguardPurpose = $purpose ?? 'Use this screen only for authorised official work and only for the purpose for which access was granted.';
    $safeguardItems = $items ?? [
        'Verify the source record before acting; dashboard signals are decision support, not legal authority by themselves.',
        'Use the minimum information necessary. Do not copy sensitive information into remarks unless it is required for the official record.',
        'Do not bypass maker/checker/approver separation. Exceptional overrides require a recorded reason and authority reference.',
        'Do not delete or overwrite evidentiary history. Use the governed correction, disable, archive or supersede process.',
        'Record the authority/reference and reason for consequential actions so another authorised officer can reconstruct the decision later.',
    ];
    $safeguardTone = $tone ?? 'info';
@endphp
<div class="alert alert-{{ $safeguardTone }}" role="note" style="margin-bottom:16px;border-left-width:5px">
    <div style="display:flex;gap:10px;align-items:flex-start">
        <div aria-hidden="true" style="font-size:1.2rem">⚖</div>
        <div style="min-width:0">
            <strong>{{ $safeguardTitle }}</strong>
            <div class="md-body-sm" style="margin-top:4px">{{ $safeguardPurpose }}</div>
            <details style="margin-top:8px">
                <summary style="cursor:pointer;font-weight:700">Before you act — review safeguards</summary>
                <ol style="margin:8px 0 0 20px;padding:0">
                    @foreach ($safeguardItems as $item)
                        <li style="margin:5px 0">{{ $item }}</li>
                    @endforeach
                </ol>
            </details>
        </div>
    </div>
</div>
