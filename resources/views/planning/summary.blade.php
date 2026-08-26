@extends('layouts.app')
@section('title', 'Planning Summary')

@section('content')

{{-- Header + period picker --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <h2 class="md-headline-sm">Approved Carder vs Actual</h2>
        <p class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:2px;">
            Planning Officer Summary — {{ \DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}
        </p>
    </div>
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <form method="GET" style="display:flex;gap:8px;">
            <select name="year" class="md-field__input" style="height:40px;max-width:120px;" onchange="this.form.submit()">
                @foreach($years as $y)
                    <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
            <select name="month" class="md-field__input" style="height:40px;max-width:150px;" onchange="this.form.submit()">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>
                        {{ \DateTime::createFromFormat('!m', $m)->format('F') }}
                    </option>
                @endfor
            </select>
        </form>
        <a href="{{ route('approved-carders.create') }}" class="md-btn md-btn--filled">+ Add Approved Carder</a>
    </div>
</div>

{{-- KPI Cards --}}
<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:24px;">
    @php
    $kpiCards = [
        ['label'=>'Total Approved', 'value'=>number_format($kpis['total_approved']), 'color'=>'var(--md-primary)', 'icon'=>'&#9989;'],
        ['label'=>'In Position',    'value'=>number_format($kpis['total_filled']),   'color'=>'var(--md-success)', 'icon'=>'&#128100;'],
        ['label'=>'Vacancies',      'value'=>number_format($kpis['total_vacancy']),  'color'=>'var(--md-warning)', 'icon'=>'&#128683;'],
        ['label'=>'Fill Rate',      'value'=>$kpis['fill_pct_overall'].'%',          'color'=>'var(--md-secondary)','icon'=>'&#128200;'],
        ['label'=>'Zero Filled',    'value'=>number_format($kpis['positions_zero']), 'color'=>'var(--md-error)',   'icon'=>'&#9888;'],
    ];
    @endphp
    @foreach($kpiCards as $k)
        <div style="background:var(--md-surface-container);border-radius:var(--md-shape-md);
                    padding:18px;border-top:3px solid {{ $k['color'] }};box-shadow:var(--md-elevation-1);">
            <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.8px;
                        color:var(--md-on-surface-variant);margin-bottom:6px;">{{ $k['label'] }}</div>
            <div style="font-size:30px;font-weight:700;color:{{ $k['color'] }};line-height:1;">{{ $k['value'] }}</div>
        </div>
    @endforeach
</div>

{{-- Position table --}}
<div class="md-card md-card--elevated">
    <div class="md-card__header" style="flex-wrap:wrap;gap:10px;">
        <span class="md-title-md">Position Breakdown</span>
        <div style="display:flex;gap:10px;align-items:center;">
            <input type="text" id="posSearch" class="md-field__input"
                   placeholder="Filter positions…"
                   style="height:36px;max-width:220px;font-size:13px;"
                   oninput="filterPosTable(this.value)">
            <select id="posFilter" class="md-field__input" style="height:36px;font-size:13px;"
                    onchange="filterByStatus(this.value)">
                <option value="">All positions</option>
                <option value="critical">Critical (0 filled)</option>
                <option value="vacancy">Has vacancies</option>
                <option value="full">Fully staffed</option>
            </select>
        </div>
    </div>
    <div style="overflow-x:auto;">
        <table class="md-table" id="posTable">
            <thead>
                <tr>
                    <th>Position</th>
                    <th style="text-align:right;">Approved</th>
                    <th style="text-align:right;">Male</th>
                    <th style="text-align:right;">Female</th>
                    <th style="text-align:right;">In Position</th>
                    <th style="min-width:120px;">Fill Rate</th>
                    <th style="text-align:right;">Vacancy</th>
                    <th style="text-align:right;">No-Pay</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $r)
                <tr class="pos-row"
                    data-title="{{ strtolower($r->title) }}"
                    data-status="{{ $r->in_position === 0 ? 'critical' : ($r->vacancy > 0 ? 'vacancy' : 'full') }}">
                    <td>
                        {{ $r->title }}
                        @if($r->carried)
                            <span class="md-badge md-badge--info" style="font-size:10px;margin-left:4px;" title="Carried forward">↻</span>
                        @endif
                    </td>
                    <td style="text-align:right;font-weight:600;">{{ $r->approved }}</td>
                    <td style="text-align:right;">{{ $r->males }}</td>
                    <td style="text-align:right;">{{ $r->females }}</td>
                    <td style="text-align:right;font-weight:600;">{{ $r->in_position }}</td>
                    <td style="min-width:120px;">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="flex:1;height:6px;background:var(--md-outline-variant);border-radius:3px;overflow:hidden;">
                                <div style="height:100%;width:{{ min($r->fill_pct,100) }}%;border-radius:3px;
                                            background:{{ $r->fill_pct >= 90 ? 'var(--md-success)' : ($r->fill_pct >= 60 ? 'var(--md-warning)' : 'var(--md-error)') }};
                                            transition:width .6s ease;"></div>
                            </div>
                            <span style="font-size:12px;width:34px;text-align:right;color:var(--md-on-surface-variant);">{{ $r->fill_pct }}%</span>
                        </div>
                    </td>
                    <td style="text-align:right;">
                        @if($r->vacancy > 0)
                            <span class="md-badge md-badge--warning">{{ $r->vacancy }}</span>
                        @else
                            <span class="md-badge md-badge--success">0</span>
                        @endif
                    </td>
                    <td style="text-align:right;">{{ $r->no_pay_leave > 0 ? $r->no_pay_leave : '—' }}</td>
                    <td>
                        <div style="display:flex;gap:4px;">
                            <a href="{{ route('positions.edit', $r->id) }}"
                               class="md-btn md-btn--icon" title="Edit position" style="font-size:14px;">&#9998;</a>
                            @if($r->carder_id)
                                <a href="{{ route('approved-carders.edit', $r->carder_id) }}"
                                   class="md-btn md-btn--icon" title="Edit approved carder" style="font-size:14px;">&#9654;</a>
                            @else
                                <a href="{{ route('approved-carders.create') }}"
                                   class="md-btn md-btn--icon" title="Set approved carder" style="font-size:14px;color:var(--md-warning);">+</a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="md-table__empty">No positions found for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script>
function filterPosTable(kw) {
    var lc = kw.toLowerCase();
    document.querySelectorAll('#posTable .pos-row').forEach(function(row) {
        var match = row.getAttribute('data-title').includes(lc);
        row.style.display = match ? '' : 'none';
    });
}

function filterByStatus(status) {
    document.querySelectorAll('#posTable .pos-row').forEach(function(row) {
        var s = row.getAttribute('data-status');
        row.style.display = (!status || s === status) ? '' : 'none';
    });
    // Reset keyword filter
    document.getElementById('posSearch').value = '';
}
</script>
@endpush
