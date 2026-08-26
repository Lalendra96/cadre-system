@extends('layouts.app')
@section('title', 'Approved Carder')

@push('head')
<style>
.ac-disabled { opacity: .5; }
.ac-disabled td { color: var(--md-on-surface-variant) !important; }
.ac-disabled-badge {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 10px; font-weight: 700; letter-spacing: .4px;
    background: var(--md-error-container); color: var(--md-on-error-container);
    padding: 2px 8px; border-radius: 999px;
}
.ac-reason-box {
    background: var(--md-surface-container-high);
    border-left: 3px solid var(--md-error);
    padding: 6px 10px; border-radius: 0 4px 4px 0;
    font-size: 11px; color: var(--md-on-surface-variant);
    max-width: 300px; word-break: break-word; margin-top: 4px;
}
/* Disable modal */
.modal-overlay {
    display: none; position: fixed; inset: 0; z-index: 9000;
    background: rgba(0,0,0,.6); align-items: center; justify-content: center;
}
.modal-overlay.open { display: flex; }
.modal-box {
    background: var(--md-surface-container-low);
    border-radius: var(--md-shape-md);
    padding: 24px; max-width: 480px; width: 100%;
    box-shadow: var(--md-elevation-3);
}
</style>
@endpush

@section('content')

@php($canWrite = auth()->user()->canManageApprovedCarder())

<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
    <div>
        <h2 class="md-headline-sm">Approved Carder</h2>
        <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
            Ministry-approved headcount per position.
            @if($canWrite)
                Managed by <strong>Director</strong> or Super Admin.
            @else
                Read-only for {{ auth()->user()->isPlanningOfficer() ? 'Planning Officer' : 'your role' }}.
            @endif
        </p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
        @if($canToggleActive)
            <a href="{{ route('approved-carders.index', ['year'=>$year, 'show_inactive' => !$showInactive]) }}"
               class="md-btn md-btn--{{ $showInactive ? 'tonal' : 'outlined' }}">
                {{ $showInactive ? '👁 Hiding Disabled' : '👁 Show Disabled' }}
            </a>
        @endif
        <a href="{{ route('approved-carders.print', ['year'=>$year]) }}"
           class="md-btn md-btn--outlined" target="_blank">🖨 Print</a>
        @if($canWrite)
            <a href="{{ route('approved-carders.create') }}" class="md-btn md-btn--filled">+ New Record</a>
        @endif
    </div>
</div>

{{-- Year selector ───────────────────────────────────────────────────── --}}
<div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;flex-wrap:wrap;">
    <span class="md-label-md">Year:</span>
    @foreach($years as $y)
        <a href="{{ route('approved-carders.index', ['year'=>$y, 'show_inactive'=>$showInactive]) }}"
           class="md-btn {{ $y == $year ? 'md-btn--tonal' : 'md-btn--outlined' }}"
           style="min-width:70px;">{{ $y }}</a>
    @endforeach
</div>

{{-- KPI strip ───────────────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px;">
    @foreach([
        ['val'=>$positionCount,  'lbl'=>'Active Positions',  'color'=>'var(--md-primary)'],
        ['val'=>number_format($totalApproved), 'lbl'=>'Total Approved Posts', 'color'=>'var(--md-secondary)'],
        ['val'=>$year,           'lbl'=>'Financial Year',    'color'=>'var(--md-on-surface-variant)'],
    ] as $k)
    <div style="background:var(--md-surface-container);border-radius:var(--md-shape-md);
                padding:14px;text-align:center;">
        <div style="font-size:26px;font-weight:700;color:{{ $k['color'] }};">{{ $k['val'] }}</div>
        <div style="font-size:10px;color:var(--md-on-surface-variant);text-transform:uppercase;
                    letter-spacing:.7px;margin-top:4px;">{{ $k['lbl'] }}</div>
    </div>
    @endforeach
</div>

{{-- Table ───────────────────────────────────────────────────────────── --}}
<div class="md-card md-card--elevated">
    <div style="overflow-x:auto;">
        <table class="md-table">
            <thead>
                <tr>
                    <th>Position</th>
                    <th style="text-align:right;">Approved Posts</th>
                    <th>MoH Reference</th>
                    <th>Approved Date</th>
                    <th>Remarks</th>
                    <th style="text-align:center;">Status</th>
                    @if($canWrite)
                        <th>Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($carders as $c)
                <tr class="{{ !$c->is_active ? 'ac-disabled' : '' }}">
                    <td>
                        <div class="md-label-md">{{ $c->position->title ?? '—' }}</div>
                        @if(!$c->is_active)
                            <div class="ac-reason-box">
                                <strong>Disabled:</strong> {{ $c->disable_reason }}<br>
                                <span style="font-size:10px;">
                                    by {{ $c->disabledBy->name ?? '—' }}
                                    · {{ $c->disabled_at?->format('d M Y') }}
                                </span>
                            </div>
                        @endif
                    </td>
                    <td style="text-align:right;font-size:18px;font-weight:700;
                               color:{{ $c->is_active ? 'var(--md-primary)' : 'var(--md-on-surface-variant)' }};">
                        {{ number_format($c->approved_amount) }}
                    </td>
                    <td class="md-body-sm">{{ $c->ministry_reference_no ?: '—' }}</td>
                    <td class="md-body-sm">{{ $c->approved_date?->format('d M Y') ?: '—' }}</td>
                    <td class="md-body-sm" style="max-width:180px;word-break:break-word;">
                        {{ $c->remarks ?: '—' }}
                    </td>
                    <td style="text-align:center;">
                        @if($c->is_active)
                            <span class="md-badge md-badge--success">Active</span>
                        @else
                            <span class="ac-disabled-badge">⊘ Disabled</span>
                        @endif
                    </td>
                    @if($canWrite)
                    <td>
                        <div style="display:flex;gap:4px;align-items:center;">
                            @if($c->is_active)
                                <a href="{{ route('approved-carders.edit', $c) }}"
                                   class="md-btn md-btn--icon" title="Edit">✏️</a>
                                {{-- Disable button — opens modal for reason --}}
                                <button type="button"
                                        class="md-btn md-btn--icon"
                                        style="color:var(--md-error);"
                                        title="Disable this record"
                                        onclick="openDisableModal({{ $c->id }}, '{{ addslashes($c->position->title ?? '') }}', {{ $c->year }})">
                                    ⊘
                                </button>
                            @else
                                {{-- Re-enable — no reason required --}}
                                <form method="POST"
                                      action="{{ route('approved-carders.toggle', $c) }}"
                                      onsubmit="return confirm('Re-enable this record? It will appear in reports again.');">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                            class="md-btn md-btn--tonal md-btn--sm"
                                            style="color:var(--md-success);">
                                        ✓ Re-enable
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $canWrite ? 7 : 6 }}" class="md-table__empty">
                        No approved carder records for {{ $year }}.
                        @if($canWrite)
                            <a href="{{ route('approved-carders.create') }}">Add the first one.</a>
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="md-card__footer">{{ $carders->links('vendor.pagination.material') }}</div>
</div>

{{-- ── Disable Reason Modal ─────────────────────────────────────────── --}}
@if($canWrite)
<div class="modal-overlay" id="disableModal">
    <div class="modal-box">
        <h3 class="md-title-lg" style="margin-bottom:8px;color:var(--md-error);">
            ⊘ Disable Approved Carder Record
        </h3>
        <p class="md-body-sm" style="color:var(--md-on-surface-variant);margin-bottom:16px;" id="disableModalDesc">
            Disabling this record removes it from all reports and vacancy calculations for this year.
            The record is preserved and can be re-enabled. This action is logged with your name and timestamp.
        </p>

        <form method="POST" id="disableForm">
            @csrf @method('PATCH')

            <div class="md-field" style="margin-bottom:16px;">
                <label class="md-field__label">
                    Reason for disabling <span style="color:var(--md-error)">*</span>
                </label>
                <textarea name="disable_reason" id="disableReason"
                          class="md-field__input" rows="3" required minlength="10" maxlength="500"
                          placeholder="e.g. Superseded by revised MoH circular Ref/HRD/2026/004 dated 01 Jul 2026">
                </textarea>
                <div class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:4px;">
                    Minimum 10 characters. This text is stored permanently in the audit trail.
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:10px;">
                <button type="button" class="md-btn md-btn--text" onclick="closeDisableModal()">Cancel</button>
                <button type="submit" class="md-btn md-btn--danger">
                    ⊘ Disable Record
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openDisableModal(id, posTitle, year) {
    document.getElementById('disableModalDesc').innerHTML =
        `Disabling <strong>${posTitle} (${year})</strong> removes it from all reports and vacancy ` +
        `calculations for ${year}. The record is preserved and can be re-enabled. ` +
        `This action is logged with your name and timestamp.`;

    document.getElementById('disableForm').action =
        '{{ url("approved-carders") }}/' + id + '/toggle';

    document.getElementById('disableReason').value = '';
    document.getElementById('disableModal').classList.add('open');
    document.getElementById('disableReason').focus();
}

function closeDisableModal() {
    document.getElementById('disableModal').classList.remove('open');
}

// Close on overlay click
document.getElementById('disableModal').addEventListener('click', function (e) {
    if (e.target === this) closeDisableModal();
});

// Close on Escape
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeDisableModal();
});
</script>
@endif

@endsection
