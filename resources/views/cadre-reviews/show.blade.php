@extends('layouts.app')
@section('title', 'Cadre Review Proposal')

@section('content')

@php
$statusBadge = [
    'draft'             => 'md-badge--neutral',
    'submitted'         => 'md-badge--info',
    'director_approved' => 'md-badge--success',
    'moh_submitted'     => 'md-badge--warning',
    'approved'          => 'md-badge--success',
    'rejected'          => 'md-badge--critical',
][$proposal->status] ?? 'md-badge--neutral';

$statusLabel = \App\Models\CadreReviewProposal::STATUS_LABELS[$proposal->status] ?? $proposal->status;
$netTotal = $proposal->items->sum(fn ($i) => $i->proposed_amount - $i->current_approved);
@endphp

{{-- Header --}}
<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
            <a href="{{ route('cadre-reviews.index') }}" class="md-btn md-btn--icon">&#8592;</a>
            <h2 class="md-headline-sm">{{ $proposal->title }}</h2>
            <span class="md-badge {{ $statusBadge }}">{{ $statusLabel }}</span>
        </div>
        <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
            Proposed year: <strong>{{ $proposal->proposal_year }}</strong>
            &middot; Created by {{ $proposal->creator->name ?? '—' }}
            on {{ $proposal->created_at->format('d M Y') }}
        </p>
    </div>

    {{-- Workflow actions --}}
    @php($isOwnerOrAdmin = auth()->user()->isSuperAdmin() || $proposal->created_by === auth()->id())
    @php($isDirectorOrAdmin = auth()->user()->isSuperAdmin() || auth()->user()->isDirector())
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        @if($proposal->status === 'draft')
            @if($isOwnerOrAdmin)
            <a href="{{ route('cadre-reviews.edit', $proposal) }}" class="md-btn md-btn--outlined">✏️ Edit</a>
            <form method="POST" action="{{ route('cadre-reviews.submit', $proposal) }}">
                @csrf
                <button class="md-btn md-btn--filled">Submit for Approval</button>
            </form>
            @else
            <span class="md-body-sm" style="color:var(--md-on-surface-variant);">Awaiting submission by the proposal's creator.</span>
            @endif
        @elseif($proposal->status === 'submitted')
            @if($isDirectorOrAdmin)
            <form method="POST" action="{{ route('cadre-reviews.approve', $proposal) }}" style="display:flex;gap:6px;">
                @csrf
                <input type="hidden" name="approval_remarks" id="approveRemarks">
                <button type="button" class="md-btn md-btn--filled" style="background:var(--md-success);color:var(--md-on-success);"
                    onclick="promptAndSubmit(this.form, 'approveRemarks', 'Director approval remarks (optional):', false)">
                    ✓ Director Approve
                </button>
            </form>
            <form method="POST" action="{{ route('cadre-reviews.reject', $proposal) }}" style="display:flex;gap:6px;">
                @csrf
                <input type="hidden" name="approval_remarks" id="rejectRemarks">
                <button type="button" class="md-btn md-btn--danger"
                    onclick="promptAndSubmit(this.form, 'rejectRemarks', 'Rejection reason (required):', true)">
                    ✗ Reject
                </button>
            </form>
            @else
            <span class="md-body-sm" style="color:var(--md-on-surface-variant);">Awaiting Director approval.</span>
            @endif
        @elseif($proposal->status === 'director_approved')
            @if($isDirectorOrAdmin)
            <form method="POST" action="{{ route('cadre-reviews.approve', $proposal) }}">
                @csrf
                <button class="md-btn md-btn--filled">Mark as Submitted to MoH</button>
            </form>
            @else
            <span class="md-body-sm" style="color:var(--md-on-surface-variant);">Director-approved — awaiting MoH submission.</span>
            @endif
        @elseif($proposal->status === 'moh_submitted')
            @if($isDirectorOrAdmin)
            <form method="POST" action="{{ route('cadre-reviews.approve', $proposal) }}" style="display:flex;gap:6px;">
                @csrf
                <input type="hidden" name="approval_remarks" id="mohApproveRemarks">
                <button type="button" class="md-btn md-btn--filled" style="background:var(--md-success);color:var(--md-on-success);"
                    onclick="promptAndSubmit(this.form, 'mohApproveRemarks', 'MoH reference / remarks:', false)">
                    ✓ Mark MoH Approved
                </button>
            </form>
            @else
            <span class="md-body-sm" style="color:var(--md-on-surface-variant);">Submitted to MoH — awaiting their decision.</span>
            @endif
        @elseif($proposal->status === 'approved')
            @if($isDirectorOrAdmin)
            <form method="POST" action="{{ route('cadre-reviews.apply', $proposal) }}"
                  onsubmit="return confirm('This will update the Approved Carder for {{ $proposal->proposal_year }} based on this proposal. Continue?');">
                @csrf
                <button class="md-btn md-btn--filled" style="background:var(--md-success);color:var(--md-on-success);">
                    ⚡ Apply to Approved Carder
                </button>
            </form>
            @else
            <span class="md-body-sm" style="color:var(--md-on-surface-variant);">Approved — awaiting Director to apply it.</span>
            @endif
        @endif
    </div>
</div>

@if(session('success'))
    <div style="background:var(--md-success-container);color:var(--md-on-success-container);padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
        {{ session('success') }}
    </div>
@endif

{{-- KPI strip --}}
<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:20px;">
    @php
    $increases = $proposal->items->where('change_type', 'increase')->count();
    $decreases = $proposal->items->where('change_type', 'decrease')->count();
    $unchanged = $proposal->items->where('change_type', 'no_change')->count();
    @endphp
    @foreach([
        ['label'=>'Positions',    'value'=>$proposal->items->count(),             'color'=>'var(--md-primary)'],
        ['label'=>'Current Total','value'=>$proposal->items->sum('current_approved'), 'color'=>'var(--md-on-surface-variant)'],
        ['label'=>'Proposed Total','value'=>$proposal->items->sum('proposed_amount'), 'color'=>'var(--md-secondary)'],
        ['label'=>'Net Change',   'value'=>($netTotal >= 0 ? '+' : '').$netTotal, 'color'=> $netTotal > 0 ? 'var(--md-success)' : ($netTotal < 0 ? 'var(--md-error)' : 'var(--md-on-surface-variant)')],
        ['label'=>'Increases / Decreases', 'value'=>"▲{$increases} / ▼{$decreases}", 'color'=>'var(--md-on-surface)'],
    ] as $k)
    <div style="background:var(--md-surface-container);border-radius:var(--md-shape-md);padding:14px;text-align:center;">
        <div style="font-size:22px;font-weight:700;color:{{ $k['color'] }};">{{ $k['value'] }}</div>
        <div style="font-size:10px;color:var(--md-on-surface-variant);text-transform:uppercase;letter-spacing:.6px;margin-top:4px;">{{ $k['label'] }}</div>
    </div>
    @endforeach
</div>

{{-- Items table --}}
<div class="md-card md-card--elevated" style="margin-bottom:16px;">
    <div class="md-card__header"><span class="md-title-md">Position Changes</span></div>
    <div style="overflow-x:auto;">
        <table class="md-table">
            <thead>
                <tr>
                    <th>Position</th>
                    <th style="text-align:right;">Current Approved</th>
                    <th style="text-align:right;">Proposed ({{ $proposal->proposal_year }})</th>
                    <th style="text-align:center;">Change</th>
                    <th>Justification</th>
                </tr>
            </thead>
            <tbody>
                @forelse($proposal->items as $item)
                <tr>
                    <td class="md-label-md">{{ $item->position->title ?? '—' }}</td>
                    <td style="text-align:right;">{{ number_format($item->current_approved) }}</td>
                    <td style="text-align:right;font-weight:600;">{{ number_format($item->proposed_amount) }}</td>
                    <td style="text-align:center;">
                        @php($net = $item->net_change)
                        @if($net > 0)
                            <span class="md-badge md-badge--success">▲ +{{ $net }}</span>
                        @elseif($net < 0)
                            <span class="md-badge md-badge--critical">▼ {{ $net }}</span>
                        @else
                            <span class="md-badge md-badge--neutral">— No change</span>
                        @endif
                    </td>
                    <td class="md-body-sm" style="color:var(--md-on-surface-variant);">{{ $item->justification ?: '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="md-table__empty">No position items in this proposal.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Justification & audit --}}
@if($proposal->justification)
<div class="md-card md-card--elevated" style="margin-bottom:16px;">
    <div class="md-card__header"><span class="md-title-md">Covering Justification</span></div>
    <div class="md-card__body md-body-md">{{ $proposal->justification }}</div>
</div>
@endif

@if($proposal->approval_remarks)
<div class="md-card md-card--elevated" style="margin-bottom:16px;border-left:4px solid {{ $proposal->status === 'rejected' ? 'var(--md-error)' : 'var(--md-success)' }};">
    <div class="md-card__header">
        <span class="md-title-md">{{ $proposal->status === 'rejected' ? 'Rejection Remarks' : 'Approval Remarks' }}</span>
        <span class="md-body-sm" style="color:var(--md-on-surface-variant);">
            {{ $proposal->approver->name ?? '—' }} · {{ $proposal->approved_at?->format('d M Y') }}
        </span>
    </div>
    <div class="md-card__body md-body-md">{{ $proposal->approval_remarks }}</div>
</div>
@endif

@endsection

@push('scripts')
<script>
function promptAndSubmit(form, inputId, message, required) {
    var val = prompt(message, '');
    if (val === null) return;   // user cancelled
    if (required && !val.trim()) {
        alert('This field is required.');
        return;
    }
    document.getElementById(inputId).value = val;
    form.submit();
}
</script>
@endpush
