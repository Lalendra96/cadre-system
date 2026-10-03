<div class="sec-editor-top">
    <div>
        <div class="sec-title-row">
            <a href="{{ route('service-letters.index') }}" class="md-btn md-btn--icon" aria-label="Back">←</a>
            <div>
                <h2 class="md-headline-sm" style="margin:0;">Secure Letter Editor</h2>
                <div class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:3px;">Official correspondence
                    workspace · {{ ucwords(str_replace('_', ' ', $serviceLetter->status)) }}</div>
            </div>
            <span class="sec-state"><span id="saveDot" class="sec-dot"></span><span
                    id="saveState">{{ $readonly ? 'Read-only' : 'Saved' }}</span></span>
        </div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a class="md-btn md-btn--outlined" href="{{ route('service-letters.print', $serviceLetter) }}"
            target="_blank">Preview / Print</a>
        <a class="md-btn md-btn--outlined" href="{{ route('service-letters.show', $serviceLetter) }}">Document
            Record</a>
        @if ($canEdit)
            <form id="submitForReview" method="POST" action="{{ route('service-letters.submit', $serviceLetter) }}">
                @csrf
                <button class="md-btn md-btn--filled" type="submit">Send for Review</button>
            </form>
        @endif
    </div>
</div>

@if (session('success'))
    <div class="sec-success">✓ {{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="sec-alert danger" style="margin-bottom:12px;"><strong>Action needed:</strong>
        <ul style="margin:6px 0 0 18px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
@if ($readonly)
    <div class="sec-lock-banner">🔒 This document is in
        <strong>{{ ucwords(str_replace('_', ' ', $serviceLetter->status)) }}</strong> state. The official text is
        locked.
        You can review, comment and view history without changing the record.
    </div>
@endif
