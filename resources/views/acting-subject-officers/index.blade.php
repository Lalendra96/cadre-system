@extends('layouts.app')
@section('title', 'Acting Subject Officers')
@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
    <div>
        <h2 class="md-headline-sm">Acting Subject Officers</h2>
        <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
            Temporary Subject Officer access grants for users covering a subject code they don't permanently hold.
        </p>
    </div>
    <a href="{{ route('acting-subject-officers.create') }}" class="md-btn md-btn--filled">+ Appoint Acting Officer</a>
</div>

@if(session('success'))
<div style="background:var(--md-success-container,#1b3a2d);color:var(--md-on-success-container,#9ef0b3);
            padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
    ✓ {{ session('success') }}
</div>
@endif

<div class="md-card md-card--elevated">
    <div style="overflow-x:auto;">
        <table class="md-table">
            <thead>
                <tr>
                    <th>Officer</th>
                    <th>Subject Code</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Appointed By</th>
                    <th>Reason</th>
                    <th style="text-align:center;">Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($assignments as $a)
                @php
                    $isCurrent = $a->is_active
                        && $a->start_date->lte(now())
                        && (! $a->end_date || $a->end_date->gte(now()));
                @endphp
                <tr style="{{ !$a->is_active ? 'opacity:.5;' : '' }}">
                    <td class="md-label-md">{{ $a->user->name ?? '—' }}</td>
                    <td>
                        <span class="md-badge md-badge--info">{{ $a->subjectCode->code ?? '—' }}</span>
                    </td>
                    <td class="md-body-sm">{{ $a->start_date->format('d M Y') }}</td>
                    <td class="md-body-sm">{{ $a->end_date ? $a->end_date->format('d M Y') : 'Open-ended' }}</td>
                    <td class="md-body-sm" style="color:var(--md-on-surface-variant);">{{ $a->appointedBy->name ?? '—' }}</td>
                    <td class="md-body-sm" style="color:var(--md-on-surface-variant);max-width:200px;">{{ $a->reason ?: '—' }}</td>
                    <td style="text-align:center;">
                        @if(!$a->is_active)
                            <span class="md-badge md-badge--neutral">Disabled</span>
                        @elseif($isCurrent)
                            <span class="md-badge md-badge--success">Currently Active</span>
                        @elseif($a->start_date->isFuture())
                            <span class="md-badge md-badge--info">Upcoming</span>
                        @else
                            <span class="md-badge md-badge--neutral">Expired</span>
                        @endif
                    </td>
                    <td>
                        <x-disable-toggle :record="$a" toggle-route="acting-subject-officers.toggle"
                            label="acting officer appointment" :require-reason="true" :small="true" />
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="md-table__empty">
                        No acting Subject Officer appointments yet.
                        <a href="{{ route('acting-subject-officers.create') }}">Appoint one →</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="md-card__footer">{{ $assignments->links('vendor.pagination.material') }}</div>
</div>

@endsection
