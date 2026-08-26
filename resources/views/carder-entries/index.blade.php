@extends('layouts.app')
@section('title', 'My Monthly Entries')

@push('head')
<style>
/* Amendment request inline modal */
.amend-overlay {
    display: none; position: fixed; inset: 0; z-index: 9500;
    background: rgba(0,0,0,.65);
    align-items: center; justify-content: center;
}
.amend-overlay.open { display: flex; }
.amend-box {
    background: var(--md-surface-container-low);
    border-radius: var(--md-shape-md);
    padding: 24px; max-width: 480px; width: 100%;
    box-shadow: var(--md-elevation-3);
}
/* Status badges */
.status-submitted         { background: color-mix(in srgb, var(--md-primary) 15%, transparent);
                             color: var(--md-primary); }
.status-verified          { background: color-mix(in srgb, #4caf50 15%, transparent);
                             color: #4caf50; }
.status-amendment_requested{ background: color-mix(in srgb, #ff9800 15%, transparent);
                              color: #ff9800; }
.status-cancelled         { background: color-mix(in srgb, var(--md-error) 12%, transparent);
                             color: var(--md-error); }
</style>
@endpush

@section('content')

{{-- Header --}}
<div style="display:flex;align-items:flex-start;justify-content:space-between;
            margin-bottom:16px;flex-wrap:wrap;gap:10px;">
    <div>
        <h2 class="md-headline-sm">My Monthly Entries</h2>
        <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
            Submitted entries for your assigned subject codes.
            <strong>Verified</strong> entries are locked — use <em>Request Amendment</em> if a correction is needed.
        </p>
    </div>
    <a href="{{ route('carder-entries.create') }}" class="md-btn md-btn--filled">
        + New Entry
    </a>
</div>

@if(session('success'))
<div style="background:var(--md-success-container,#1b3a2d);color:var(--md-on-success-container,#9ef0b3);
            padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
    ✓ {{ session('success') }}
</div>
@endif
@if(session('warning'))
<div style="background:color-mix(in srgb,#ff9800 15%,transparent);color:#ff9800;
            padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
    ⚠ {{ session('warning') }}
</div>
@endif
@if(session('info'))
<div style="background:var(--md-surface-container);color:var(--md-on-surface);
            padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
    ℹ {{ session('info') }}
</div>
@endif
@if(session('error'))
<div style="background:var(--md-error-container);color:var(--md-on-error-container);
            padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
    {{ session('error') }}
</div>
@endif

{{-- ── Quick glance: approved vs actual vs vacancy ──────────────────── --}}
@if($quickGlance->isNotEmpty())
<div class="md-card md-card--elevated" style="padding:16px 20px;margin-bottom:16px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
        <span class="md-label-md">
            Quick Glance — {{ \DateTime::createFromFormat('!m', $glanceMonth)->format('F') }} {{ $glanceYear }}
        </span>
        <span class="md-body-sm" style="color:var(--md-on-surface-variant);">
            Approved vs. actual headcount for your positions
        </span>
    </div>
    <div style="overflow-x:auto;">
        <table class="md-table" style="font-size:12.5px;">
            <thead>
                <tr>
                    <th>Position</th>
                    <th style="text-align:right;">Approved</th>
                    <th style="text-align:right;">Actual</th>
                    <th style="text-align:right;">Vacancy</th>
                    <th style="text-align:right;">Fill %</th>
                </tr>
            </thead>
            <tbody>
                @foreach($quickGlance as $g)
                @php
                    $fillColor = $g->fillPct >= 90 ? 'var(--md-success,#2e7d32)' : ($g->fillPct >= 70 ? '#ff9800' : 'var(--md-error)');
                @endphp
                <tr>
                    <td class="md-label-md" style="font-size:12.5px;">{{ $g->title }}</td>
                    <td style="text-align:right;">{{ number_format($g->approved) }}</td>
                    <td style="text-align:right;font-weight:600;">{{ number_format($g->actual) }}</td>
                    <td style="text-align:right;color:{{ $g->vacancy > 0 ? 'var(--md-error)' : 'var(--md-on-surface-variant)' }};">
                        {{ number_format($g->vacancy) }}
                    </td>
                    <td style="text-align:right;color:{{ $fillColor }};font-weight:600;">{{ $g->fillPct }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:10px;margin-bottom:0;">
        ℹ "Actual" uses your Employee Profile records where you maintain them for a position; for positions
        with no profiles recorded yet, it falls back to your most recent monthly entry submission
        ({{ \DateTime::createFromFormat('!m', $glanceMonth)->format('F') }} {{ $glanceYear }}, or the most recent prior entry if none was filed this month).
    </p>
</div>
@endif

{{-- Assigned subject codes strip --}}
<div class="md-card md-card--elevated" style="padding:12px 16px;margin-bottom:16px;">
    <div class="md-label-md" style="margin-bottom:8px;color:var(--md-on-surface-variant);">
        Your assigned subject codes:
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:8px;">
        @forelse($assignedCodes as $code)
            <span class="md-badge md-badge--info"
                  style="font-size:12px;padding:4px 10px;">
                <strong>{{ $code->code }}</strong> — {{ $code->name }}
                @if($code->positions->isNotEmpty())
                    <span style="opacity:.75;">({{ $code->positions->pluck('title')->join(', ') }})</span>
                @endif
            </span>
        @empty
            <span class="md-body-sm" style="color:var(--md-error);">
                No subject codes assigned — contact the system administrator.
            </span>
        @endforelse
    </div>
</div>

{{-- Entries table --}}
<div class="md-card md-card--elevated">
    {{-- Filters --}}
    <div style="padding:12px 16px;border-bottom:1px solid var(--md-outline-variant);
                display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
            <div class="md-field" style="max-width:100px;">
                <label class="md-field__label">Year</label>
                <select name="year" class="md-field__input" onchange="this.form.submit()">
                    @foreach(collect(range(now()->year - 2, now()->year))->reverse() as $y)
                        <option value="{{ $y }}" {{ request('year', now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md-field" style="max-width:130px;">
                <label class="md-field__label">Status</label>
                <select name="status" class="md-field__input" onchange="this.form.submit()">
                    <option value="">All</option>
                    <option value="submitted"            {{ request('status') === 'submitted' ? 'selected' : '' }}>Submitted</option>
                    <option value="verified"             {{ request('status') === 'verified' ? 'selected' : '' }}>Verified</option>
                    <option value="amendment_requested"  {{ request('status') === 'amendment_requested' ? 'selected' : '' }}>Amendment Pending</option>
                    <option value="cancelled"            {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
        </form>
    </div>

    <div style="overflow-x:auto;">
        <table class="md-table">
            <thead>
                <tr>
                    <th>Month / Year</th>
                    <th>Subject Code</th>
                    <th>Position</th>
                    <th style="text-align:right;">Males</th>
                    <th style="text-align:right;">Females</th>
                    <th style="text-align:right;">In Post</th>
                    <th style="text-align:right;">T/In</th>
                    <th style="text-align:right;">T/Out</th>
                    <th style="text-align:right;">No-Pay</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($entries as $e)
                @php
                    $monthName  = \DateTime::createFromFormat('!m', $e->month)->format('M');
                    $statusLabel = [
                        'submitted'           => 'Submitted',
                        'verified'            => 'Verified ✓',
                        'amendment_requested' => 'Amendment Pending',
                        'cancelled'           => 'Cancelled',
                    ][$e->status] ?? $e->status;
                    $canEdit    = ! $e->is_locked && $e->status === \App\Models\CarderMonthlyEntry::STATUS_SUBMITTED;
                    $canRequest = $e->is_locked && $e->status === \App\Models\CarderMonthlyEntry::STATUS_VERIFIED;
                    $isPending  = $e->status === \App\Models\CarderMonthlyEntry::STATUS_AMENDMENT_REQUESTED;
                @endphp
                <tr style="{{ $e->is_locked ? 'opacity:.85;' : '' }}">
                    <td class="md-label-md">
                        {{ $monthName }} {{ $e->year }}
                        @if($e->is_locked)
                            <span title="Locked — verified or pending amendment"
                                  style="font-size:13px;margin-left:4px;">🔒</span>
                        @endif
                    </td>
                    <td>
                        <span class="md-badge md-badge--neutral">{{ $e->subjectCode->code ?? '—' }}</span>
                    </td>
                    <td class="md-body-sm">{{ $e->position->title ?? '—' }}</td>
                    <td style="text-align:right;">{{ $e->males }}</td>
                    <td style="text-align:right;">{{ $e->females }}</td>
                    <td style="text-align:right;font-weight:600;">{{ $e->males + $e->females + $e->no_pay_leave }}</td>
                    <td style="text-align:right;color:var(--md-on-surface-variant);">{{ $e->transferred_in }}</td>
                    <td style="text-align:right;color:var(--md-on-surface-variant);">{{ $e->transferred_out }}</td>
                    <td style="text-align:right;color:var(--md-on-surface-variant);">{{ $e->no_pay_leave }}</td>

                    {{-- Status badge --}}
                    <td>
                        <span class="md-badge status-{{ $e->status }}"
                              style="font-size:11px;white-space:nowrap;">
                            {{ $statusLabel }}
                        </span>
                        @if($e->status === 'amendment_requested')
                            <div class="md-body-sm"
                                 style="color:var(--md-on-surface-variant);font-size:10px;margin-top:2px;max-width:140px;word-break:break-word;"
                                 title="{{ $e->amendment_reason }}">
                                "{{ \Str::limit($e->amendment_reason, 40) }}"
                            </div>
                        @endif
                        @if($e->status === 'verified')
                            <div style="font-size:10px;color:var(--md-on-surface-variant);margin-top:2px;">
                                by {{ $e->verifiedBy->name ?? '—' }}
                            </div>
                        @endif
                    </td>

                    {{-- Actions --}}
                    <td>
                        <div style="display:flex;gap:4px;align-items:center;flex-wrap:wrap;">

                            {{-- Edit — only for unlocked submitted entries --}}
                            @if($canEdit)
                                <a href="{{ route('carder-entries.edit', $e) }}"
                                   class="md-btn md-btn--icon" title="Edit entry">✏️</a>
                            @endif

                            {{-- Request Amendment — only for verified (locked) entries --}}
                            @if($canRequest)
                                <button type="button"
                                        class="md-btn md-btn--outlined md-btn--sm"
                                        style="font-size:11px;white-space:nowrap;"
                                        onclick="openAmendModal({{ $e->id }}, '{{ $monthName }} {{ $e->year }}', '{{ addslashes($e->position->title ?? '') }}')"
                                        title="Request a correction to this verified entry">
                                    ✎ Request Amendment
                                </button>
                            @endif

                            {{-- Amendment pending label --}}
                            @if($isPending)
                                <span class="md-body-sm"
                                      style="color:#ff9800;font-size:11px;white-space:nowrap;">
                                    ⏳ Awaiting approval
                                </span>
                            @endif

                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" class="md-table__empty">
                        No entries found.
                        <a href="{{ route('carder-entries.create') }}" style="color:var(--md-primary);">
                            Submit your first entry →
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($entries->hasPages())
    <div class="md-card__footer">{{ $entries->links('vendor.pagination.material') }}</div>
    @endif
</div>

{{-- ── Amendment Request Modal ─────────────────────────────────────────── --}}
<div class="amend-overlay" id="amendModal">
    <div class="amend-box">
        <h3 class="md-title-lg" style="margin-bottom:6px;">✎ Request Amendment</h3>
        <p class="md-body-sm" style="color:var(--md-on-surface-variant);margin-bottom:4px;"
           id="amendModalDesc">
            <!-- filled by JS -->
        </p>
        <p class="md-body-sm" style="color:var(--md-on-surface-variant);margin-bottom:16px;">
            Your request will be reviewed by the Planning Officer.
            Once approved, the entry will be unlocked for editing.
        </p>

        <form id="amendForm" method="POST" action="">
            @csrf
            <div class="md-field" style="margin-bottom:16px;">
                <label class="md-field__label">
                    Reason for amendment
                    <span style="color:var(--md-error)">*</span>
                </label>
                <textarea name="reason"
                          id="amendReason"
                          class="md-field__input"
                          rows="4"
                          required
                          minlength="10"
                          maxlength="500"
                          placeholder="Describe what needs to be corrected and why. Minimum 10 characters."></textarea>
                <div class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:4px;">
                    This reason is permanently recorded in the audit log.
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:10px;">
                <button type="button" class="md-btn md-btn--text"
                        onclick="closeAmendModal()">Cancel</button>
                <button type="submit" class="md-btn md-btn--filled">
                    Submit Amendment Request
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function openAmendModal(entryId, period, position) {
    document.getElementById('amendModalDesc').textContent =
        'Requesting amendment for: ' + position + ' — ' + period;

    document.getElementById('amendForm').action =
        '{{ url("carder-entries") }}/' + entryId + '/request-amendment';

    document.getElementById('amendReason').value = '';
    document.getElementById('amendModal').classList.add('open');
    setTimeout(function () {
        document.getElementById('amendReason').focus();
    }, 80);
}

function closeAmendModal() {
    document.getElementById('amendModal').classList.remove('open');
}

// Close on overlay click or Escape key
document.getElementById('amendModal').addEventListener('click', function (e) {
    if (e.target === this) closeAmendModal();
});
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeAmendModal();
});
</script>
@endpush
