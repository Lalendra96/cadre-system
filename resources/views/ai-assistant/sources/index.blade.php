@extends('layouts.app')

@section('title', 'Assistant Knowledge Source Register')

@section('content')
    <div class="md-page-header">
        <div>
            <div class="md-label-md" style="color: var(--md-primary);">GOVERNED KNOWLEDGE</div>
            <h1 class="page-title">Assistant Knowledge Source Register</h1>
            <p class="page-subtitle">
                Control which local manuals, policies, circulars and planning references may support assistant answers.
                Adding a source does not make it authoritative: it remains excluded from governed KB answers until verified.
            </p>
        </div>
        <a href="{{ route('offline-assistant.sources.create') }}" class="md-btn md-btn--filled">Add source</a>
    </div>

    <div class="md-card" style="margin-bottom: 16px; border-left: 4px solid #d4a72c;">
        <div class="md-card__content">
            <strong>Knowledge governance safeguard</strong>
            <p style="margin: 6px 0 0; color: var(--md-on-surface-variant);">
                Verify the issuing authority, reference number, effective date, version and source text against the official record before verification.
                Disable superseded or unreliable material instead of deleting it so the audit trail remains reconstructable.
            </p>
        </div>
    </div>

    <div class="md-card">
        <div class="md-card__content" style="overflow-x: auto;">
            <table class="md-table">
                <thead>
                    <tr>
                        <th>Source</th>
                        <th>Type / Language</th>
                        <th>Authority / Reference</th>
                        <th>Status</th>
                        <th>Dates</th>
                        <th>Governance action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sources as $source)
                        <tr>
                            <td>
                                <strong>{{ $source->title }}</strong>
                                <div class="md-caption">{{ $source->source_location ?: 'Local KB record' }}</div>
                            </td>
                            <td>{{ str_replace('_', ' ', ucfirst($source->source_type)) }} · {{ strtoupper($source->language) }}</td>
                            <td>
                                {{ $source->issuing_authority ?: '—' }}
                                <div class="md-caption">{{ $source->reference_no ?: 'No reference recorded' }}</div>
                            </td>
                            <td>
                                <span class="md-chip">{{ $source->is_verified ? 'Verified' : 'Unverified' }}</span>
                                <span class="md-chip">{{ $source->is_active ? 'Active' : 'Disabled' }}</span>
                                <div class="md-caption">{{ ucfirst($source->classification) }}</div>
                            </td>
                            <td>
                                Effective: {{ optional($source->effective_date)->format('Y-m-d') ?: '—' }}<br>
                                <span class="md-caption">Expiry: {{ optional($source->expiry_date)->format('Y-m-d') ?: 'Not recorded' }}</span>
                            </td>
                            <td style="min-width: 260px;">
                                @if ($source->is_active && ! $source->is_verified)
                                    <form method="POST" action="{{ route('offline-assistant.sources.verify', $source) }}">
                                        @csrf
                                        <label style="display:flex;gap:7px;align-items:flex-start;font-size:11px;margin-bottom:7px;">
                                            <input type="checkbox" name="verification_confirmed" value="1" required>
                                            <span>I checked the official source and metadata.</span>
                                        </label>
                                        <button class="md-btn md-btn--tonal" type="submit">Verify source</button>
                                    </form>
                                @elseif ($source->is_active)
                                    <form method="POST" action="{{ route('offline-assistant.sources.disable', $source) }}">
                                        @csrf
                                        <label class="md-label" for="disable-{{ $source->id }}">Reason to disable / supersede</label>
                                        <input class="md-input" id="disable-{{ $source->id }}" name="disable_reason" required maxlength="500">
                                        <button class="md-btn md-btn--text" type="submit" style="margin-top:6px;">Disable</button>
                                    </form>
                                @else
                                    <span class="md-caption">Preserved for audit history.</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">No knowledge sources are registered.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top: 16px;">{{ $sources->links() }}</div>
@endsection
