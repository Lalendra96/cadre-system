@extends('layouts.app')
@section('title', 'Service Letter — ' . $serviceLetter->subject)

@push('head')
<style>
.sl-status { display:inline-block; padding:3px 12px; border-radius:999px; font-size:12px; font-weight:600; }
.sl-status-draft            { background:var(--md-surface-container-highest); color:var(--md-on-surface-variant); }
.sl-status-pending_approval { background:color-mix(in srgb,#ff9800 15%,transparent); color:#ff9800; }
.sl-status-approved         { background:color-mix(in srgb,#4caf50 15%,transparent); color:#4caf50; }
.sl-status-rejected         { background:color-mix(in srgb,var(--md-error) 12%,transparent); color:var(--md-error); }
.sl-letter-body {
    background:#fff; color:#1a1a1a; padding:36px 40px; border-radius:var(--md-shape-sm);
    font-size:14px; line-height:1.8; white-space:pre-wrap; word-break:break-word;
    box-shadow:0 1px 4px rgba(0,0,0,.15); min-height:300px;
}
</style>
@endpush

@section('content')
@php($user = auth()->user())
@php($isApprover = $user->isAdministrativeOfficer() || $user->isSuperAdmin())
@php($isOwner = $serviceLetter->drafted_by === $user->id)
@php($canEdit = $serviceLetter->isEditable() && ($isOwner || $user->isSuperAdmin()))

<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
    <div style="display:flex;align-items:center;gap:10px;">
        <a href="{{ route('service-letters.index') }}" class="md-btn md-btn--icon">&#8592;</a>
        <div>
            <h2 class="md-headline-sm">{{ $serviceLetter->subject }}</h2>
            <div style="display:flex;align-items:center;gap:8px;margin-top:4px;">
                <span class="sl-status sl-status-{{ $serviceLetter->status }}">
                    {{ ucwords(str_replace('_', ' ', $serviceLetter->status)) }}
                </span>
                <span class="md-body-sm" style="color:var(--md-on-surface-variant);">
                    for {{ $serviceLetter->employee->display_name }}
                    ({{ $serviceLetter->employee->pay_no ?? '—' }})
                </span>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
<div style="background:var(--md-success-container,#1b3a2d);color:var(--md-on-success-container,#9ef0b3);
            padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
    ✓ {{ session('success') }}
</div>
@endif
@if(session('error'))
<div style="background:var(--md-error-container);color:var(--md-on-error-container);
            padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
    {{ session('error') }}
</div>
@endif
@if($errors->any())
<div style="background:var(--md-error-container);color:var(--md-on-error-container);
            padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
    <ul style="margin:0;padding-left:16px;">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;align-items:start;">

    {{-- Letter body --}}
    <div>
        <div class="sl-letter-body">{{ $serviceLetter->rendered_body }}</div>

        @if($serviceLetter->status === 'approved' && $serviceLetter->eSignature)
        <div style="margin-top:16px;background:var(--md-surface-container);border-radius:var(--md-shape-sm);
                    padding:16px;display:flex;align-items:center;gap:16px;">
            <img src="{{ route('e-signatures.show', $serviceLetter->eSignature) }}"
                 alt="Approver's signature" style="max-height:60px;background:#fff;border-radius:4px;padding:4px;">
            <div>
                <div class="md-label-md">{{ $serviceLetter->approvedBy->name ?? '—' }}</div>
                <div class="md-body-sm" style="color:var(--md-on-surface-variant);">
                    Administrative Officer · Approved {{ $serviceLetter->approved_at?->format('d M Y H:i') }}
                </div>
            </div>
        </div>
        @endif

        @if($serviceLetter->status === 'rejected')
        <div style="margin-top:16px;background:var(--md-error-container);color:var(--md-on-error-container);
                    border-radius:var(--md-shape-sm);padding:16px;">
            <div class="md-label-md" style="margin-bottom:4px;">Rejection reason</div>
            <div class="md-body-sm">{{ $serviceLetter->rejection_reason }}</div>
        </div>
        @endif
    </div>

    {{-- Sidebar: metadata + actions --}}
    <div style="display:flex;flex-direction:column;gap:14px;">

        <div class="md-card md-card--elevated" style="padding:16px;">
            <div class="md-title-sm" style="margin-bottom:10px;color:var(--md-primary);">Details</div>
            <div class="md-body-sm" style="display:flex;flex-direction:column;gap:8px;color:var(--md-on-surface-variant);">
                <div><strong style="color:var(--md-on-surface);">Employee:</strong> {{ $serviceLetter->employee->display_name }}</div>
                <div><strong style="color:var(--md-on-surface);">Position:</strong> {{ $serviceLetter->employee->position->title ?? '—' }}</div>
                <div><strong style="color:var(--md-on-surface);">Subject Code:</strong> {{ $serviceLetter->employee->subjectCode->code ?? '—' }}</div>
                <div><strong style="color:var(--md-on-surface);">Drafted by:</strong> {{ $serviceLetter->draftedBy->name ?? '—' }}</div>
                <div><strong style="color:var(--md-on-surface);">Template:</strong> {{ $serviceLetter->template->name ?? 'Written from scratch' }}</div>
                <div><strong style="color:var(--md-on-surface);">Last updated:</strong> {{ $serviceLetter->updated_at->format('d M Y H:i') }}</div>
            </div>
        </div>

        {{-- Submit for approval — visible to the drafting officer while draft/rejected --}}
        @if($canEdit && in_array($serviceLetter->status, ['draft', 'rejected']))
        <form method="POST" action="{{ route('service-letters.submit', $serviceLetter) }}"
              onsubmit="return confirm('Submit this letter to the Administrative Officer for approval?');">
            @csrf
            <button type="submit" class="md-btn md-btn--filled" style="width:100%;">
                📤 Submit for AO Approval
            </button>
        </form>
        @endif

        {{-- Approve / Reject — visible only to AO / Super Admin while pending --}}
        @if($isApprover && $serviceLetter->status === 'pending_approval')
        <div class="md-card md-card--elevated" style="padding:16px;">
            <div class="md-title-sm" style="margin-bottom:10px;color:var(--md-primary);">Your Decision</div>

            <form method="POST" action="{{ route('service-letters.approve', $serviceLetter) }}"
                  onsubmit="return confirm('E-sign and approve this service letter? This cannot be undone.');"
                  style="margin-bottom:10px;">
                @csrf
                <label style="display:flex;align-items:flex-start;gap:8px;font-size:12px;
                              color:var(--md-on-surface-variant);margin-bottom:10px;cursor:pointer;">
                    <input type="checkbox" name="confirm_signature" value="1" required style="margin-top:2px;">
                    I confirm I am e-signing this letter as the Administrative Officer.
                </label>
                <button type="submit" class="md-btn md-btn--filled" style="width:100%;background:var(--md-success,#2e7d32);">
                    ✓ Approve &amp; E-Sign
                </button>
            </form>

            <details>
                <summary class="md-body-sm" style="cursor:pointer;color:var(--md-error);">Reject instead</summary>
                <form method="POST" action="{{ route('service-letters.reject', $serviceLetter) }}" style="margin-top:10px;">
                    @csrf
                    <textarea name="rejection_reason" rows="3" required minlength="10" maxlength="500"
                              class="md-field__input" style="margin-bottom:8px;"
                              placeholder="Reason for rejection (minimum 10 characters)"></textarea>
                    <button type="submit" class="md-btn md-btn--outlined" style="width:100%;color:var(--md-error);">
                        ✕ Reject Letter
                    </button>
                </form>
            </details>
        </div>
        @endif

    </div>
</div>

@endsection
