@extends('layouts.app')
@section('title', 'Vacancy Availability')

@push('head')
<style>
.vac-card {
    background: var(--md-surface-container);
    border-radius: var(--md-shape-md);
    padding: 18px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    box-shadow: var(--md-elevation-1, 0 1px 3px rgba(0,0,0,.15));
}
.vac-count {
    font-size: 30px; font-weight: 700; color: var(--md-primary);
    line-height: 1;
}
.vac-days-badge {
    font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 999px;
    white-space: nowrap;
}
.vac-days-fresh    { background: color-mix(in srgb, #4caf50 15%, transparent); color: #4caf50; }
.vac-days-closing  { background: color-mix(in srgb, #ff9800 15%, transparent); color: #ff9800; }
.vac-expired       { background: var(--md-surface-container-highest); color: var(--md-on-surface-variant); }
</style>
@endpush

@section('content')
@php($user = auth()->user())
@php($isSubjectOfficer = $user->isSubjectOfficer() && !$user->isSuperAdmin() && !$user->isPlanningOfficer() && !$user->isAdminGroup())
@php($canIssue = $user->isSuperAdmin() || $user->isAdminGroup())

<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
    <div>
        <h2 class="md-headline-sm">Vacancy Availability</h2>
        <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
            @if($isSubjectOfficer)
                Positions with a currently declared, recruitable vacancy under your assigned subject codes.
            @else
                All e-signed vacancy availability letters. Each declaration is valid for 90 days from issue.
            @endif
        </p>
    </div>
    @if($canIssue)
        <a href="{{ route('vacancy-availability-letters.create') }}" class="md-btn md-btn--filled">
            + Issue Vacancy Letter
        </a>
    @endif
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

{{-- Filter: currently-available only vs full history --}}
<div style="display:flex;gap:8px;margin-bottom:18px;">
    <a href="{{ route('vacancy-availability-letters.index', ['only_available' => 1]) }}"
       class="md-btn {{ $onlyAvailable ? 'md-btn--tonal' : 'md-btn--outlined' }}" style="font-size:12px;">
        ✓ Currently Available
    </a>
    <a href="{{ route('vacancy-availability-letters.index', ['only_available' => 0]) }}"
       class="md-btn {{ !$onlyAvailable ? 'md-btn--tonal' : 'md-btn--outlined' }}" style="font-size:12px;">
        Full History
    </a>
</div>

@if($letters->isEmpty())
    <div class="md-card md-card--elevated" style="padding:48px;text-align:center;color:var(--md-on-surface-variant);">
        <div style="font-size:32px;margin-bottom:10px;">📭</div>
        @if($onlyAvailable)
            <p class="md-body-sm">
                No currently available vacancies
                @if($isSubjectOfficer) for your subject codes @endif right now.
            </p>
        @else
            <p class="md-body-sm">No vacancy availability letters have been issued yet.</p>
        @endif
    </div>
@else

{{-- Card grid for currently-available view — the primary Subject Officer use case --}}
@if($onlyAvailable)
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:14px;">
    @foreach($letters as $l)
    @php($daysLeft = $l->daysRemainingInCooldown())
    @php($badgeClass = $daysLeft <= 14 ? 'vac-days-closing' : 'vac-days-fresh')
    <div class="vac-card">
        <div>
            <div class="md-label-md" style="margin-bottom:2px;">{{ $l->position->title ?? '—' }}</div>
            <div class="md-body-sm" style="color:var(--md-on-surface-variant);margin-bottom:8px;">
                {{ $l->subjectCode->code ?? '—' }} — {{ $l->subjectCode->name ?? '' }}
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
                <span class="vac-days-badge {{ $badgeClass }}">
                    {{ $daysLeft }} day(s) remaining
                </span>
                @if($l->reference_no)
                    <span class="md-body-sm" style="color:var(--md-on-surface-variant);">Ref: {{ $l->reference_no }}</span>
                @endif
            </div>
        </div>
        <div style="text-align:right;flex-shrink:0;">
            <div class="vac-count">{{ $l->vacancy_count }}</div>
            <div class="md-body-sm" style="color:var(--md-on-surface-variant);">
                {{ \Illuminate\Support\Str::plural('vacancy', $l->vacancy_count) }}
            </div>
        </div>
    </div>
    @endforeach
</div>
@else

{{-- Full history table --}}
<div class="md-card md-card--elevated">
    <div style="overflow-x:auto;">
        <table class="md-table">
            <thead>
                <tr>
                    <th>Position</th>
                    <th>Subject Code</th>
                    <th style="text-align:right;">Vacancies</th>
                    <th>Issued</th>
                    <th>Issued By</th>
                    <th>Valid Until</th>
                    <th style="text-align:center;">Status</th>
                    <th>Reference / Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse($letters as $l)
                @php($inCooldown = $l->isInCooldown())
                <tr>
                    <td class="md-label-md">{{ $l->position->title ?? '—' }}</td>
                    <td class="md-body-sm">{{ $l->subjectCode->code ?? '—' }}</td>
                    <td style="text-align:right;font-weight:600;">{{ $l->vacancy_count }}</td>
                    <td class="md-body-sm">{{ $l->issued_at->format('d M Y') }}</td>
                    <td class="md-body-sm" style="color:var(--md-on-surface-variant);">{{ $l->issuedBy->name ?? '—' }}</td>
                    <td class="md-body-sm">{{ $l->cooldown_until->format('d M Y') }}</td>
                    <td style="text-align:center;">
                        @if($inCooldown)
                            <span class="vac-days-badge {{ $l->daysRemainingInCooldown() <= 14 ? 'vac-days-closing' : 'vac-days-fresh' }}">
                                {{ $l->daysRemainingInCooldown() }}d left
                            </span>
                        @else
                            <span class="vac-days-badge vac-expired">Expired</span>
                        @endif
                    </td>
                    <td class="md-body-sm" style="color:var(--md-on-surface-variant);max-width:220px;">
                        {{ $l->reference_no ?: '—' }}
                        @if($l->remarks)
                            <div style="font-size:10.5px;margin-top:2px;">{{ \Illuminate\Support\Str::limit($l->remarks, 60) }}</div>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="md-table__empty">No records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="md-card__footer">{{ $letters->links('vendor.pagination.material') }}</div>
</div>
@endif

@endif

@if($onlyAvailable && $letters->hasPages())
<div style="margin-top:16px;">{{ $letters->links('vendor.pagination.material') }}</div>
@endif

@endsection
