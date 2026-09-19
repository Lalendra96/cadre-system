@extends('layouts.app')
@section('title', 'Summary & Charts')

@push('head')
<style>
/* ── Page-level layout ───────────────────────────────────────── */
.summary-grid-kpi { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:24px; }
@media(max-width:900px){ .summary-grid-kpi { grid-template-columns:repeat(2,1fr); } }
@media(max-width:500px){ .summary-grid-kpi { grid-template-columns:1fr; } }

/* ── KPI cards with icon + fill bar ─────────────────────────── */
.kpi-card {
  display: flex; flex-direction: column; gap: 12px;
  padding: 20px 20px 16px;
  background: var(--md-surface-container);
  border-radius: var(--md-shape-md);
  border-top: 3px solid var(--kpi-color, var(--md-primary));
  box-shadow: var(--md-elevation-1);
}
.kpi-card__row    { display:flex; align-items:flex-start; justify-content:space-between; }
.kpi-card__icon   { width:40px; height:40px; border-radius:var(--md-shape-sm); background:color-mix(in srgb, var(--kpi-color, var(--md-primary)) 15%, transparent); display:flex; align-items:center; justify-content:center; font-size:20px; }
.kpi-card__value  { font-size:36px; font-weight:700; color:var(--kpi-color, var(--md-primary)); line-height:1; }
.kpi-card__label  { font-size:12px; font-weight:500; text-transform:uppercase; letter-spacing:.8px; color:var(--md-on-surface-variant); }
.kpi-card__bar    { height:4px; border-radius:2px; background:var(--md-outline-variant); overflow:hidden; }
.kpi-card__bar-fill { height:100%; border-radius:2px; background:var(--kpi-color, var(--md-primary)); transition:width .8s ease; }

/* ── Scrollable chart host ───────────────────────────────────── */
.chart-scroll-host {
  position: relative;
  overflow-x: auto;
  overflow-y: hidden;
  cursor: grab;
}
.chart-scroll-host:active { cursor: grabbing; }
.chart-inner {
  position: relative;
  min-width: 100%;     /* JS overrides this to num_labels × px */
  height: 420px;
}
.chart-inner canvas {
  position: absolute; inset: 0;
  width:  100% !important;
  height: 100% !important;
}

/* ── Builder section ─────────────────────────────────────────── */
.builder-controls {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr auto auto;
  gap: 12px;
  align-items: end;
  margin-bottom: 20px;
}
@media(max-width:900px){ .builder-controls { grid-template-columns: 1fr 1fr; } }

.builder-filter-panel {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 12px;
  margin-bottom: 16px;
  padding: 16px;
  background: var(--md-surface-container-high);
  border-radius: var(--md-shape-sm);
}
@media(max-width:700px){ .builder-filter-panel { grid-template-columns: 1fr; } }

/* ── Chart legend pill ───────────────────────────────────────── */
.chart-legend { display:flex; gap:16px; padding:12px 16px; flex-wrap:wrap; border-top:1px solid var(--md-outline-variant); }
.chart-legend-item { display:flex; align-items:center; gap:8px; font-size:12px; color:var(--md-on-surface-variant); }
.chart-legend-swatch { width:12px; height:12px; border-radius:2px; flex-shrink:0; }

/* ── Tab pills for the chart section ────────────────────────── */
.chart-tabs { display:flex; gap:4px; padding:4px; background:var(--md-surface-container-high); border-radius:var(--md-shape-sm); width:fit-content; margin-bottom:16px; }
.chart-tab {
  padding:6px 16px; border-radius:var(--md-shape-xs); font-size:13px; font-weight:500;
  color:var(--md-on-surface-variant); background:transparent; border:none; cursor:pointer;
  transition:all .15s ease; white-space:nowrap;
}
.chart-tab--active { background:var(--md-secondary-container); color:var(--md-on-secondary-container); }
.chart-panel { display:none; }
.chart-panel--active { display:block; }

/* ── Export button ───────────────────────────────────────────── */
.btn-export {
  display:inline-flex; align-items:center; gap:6px; height:40px; padding:0 16px;
  border-radius:var(--md-shape-full); font-size:13px; font-weight:500;
  background:var(--md-surface-container-high); color:var(--md-on-surface-variant);
  border:1px solid var(--md-outline-variant); cursor:pointer; transition:all .15s ease;
}
.btn-export:hover { background:var(--md-primary-container); color:var(--md-on-primary-container); }
</style>
@endpush

@section('content')

{{-- ── Page header ────────────────────────────────────────── --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <h2 class="md-headline-sm">Carder Summary</h2>
        <p class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:2px;">
            {{ \DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}
        </p>
    </div>
    <form method="GET" style="display:flex;gap:10px;align-items:center;">
        <select name="year" class="md-field__input" style="height:40px;max-width:130px;" onchange="this.form.submit()">
            @foreach($years as $y)
                <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
            @endforeach
            @if(!$years->contains($year))
                <option value="{{ $year }}" selected>{{ $year }}</option>
            @endif
        </select>
        <select name="month" class="md-field__input" style="height:40px;max-width:160px;" onchange="this.form.submit()">
            @for($m = 1; $m <= 12; $m++)
                <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>{{ \DateTime::createFromFormat('!m', $m)->format('F') }}</option>
            @endfor
        </select>
    </form>
</div>

{{-- ── KPI Cards ───────────────────────────────────────────── --}}
@php
    $fillApproved  = 100;
    $fillAvailable = $kpis['approved_total'] > 0 ? round(($kpis['available_total'] / $kpis['approved_total']) * 100) : 0;
    $fillVacancy   = $kpis['approved_total'] > 0 ? round(($kpis['vacancy_total']   / $kpis['approved_total']) * 100) : 0;
    $fillNoPayLeave = $kpis['available_total'] > 0 ? round(($kpis['no_pay_leave'] / $kpis['available_total']) * 100) : 0;
@endphp
<div class="summary-grid-kpi">
    <div class="kpi-card" style="--kpi-color:var(--md-primary);">
        <div class="kpi-card__row">
            <div>
                <div class="kpi-card__label">Approved Cadre</div>
                <div class="kpi-card__value">{{ number_format($kpis['approved_total']) }}</div>
            </div>
            <div class="kpi-card__icon">&#9989;</div>
        </div>
        <div class="kpi-card__bar"><div class="kpi-card__bar-fill" data-width="{{ $fillApproved }}"></div></div>
        <div class="md-body-sm" style="color:var(--md-on-surface-variant);">Total ministry-approved headcount</div>
    </div>
    <div class="kpi-card" style="--kpi-color:var(--md-success);">
        <div class="kpi-card__row">
            <div>
                <div class="kpi-card__label">In Position</div>
                <div class="kpi-card__value">{{ number_format($kpis['available_total']) }}</div>
            </div>
            <div class="kpi-card__icon">&#128100;</div>
        </div>
        <div class="kpi-card__bar"><div class="kpi-card__bar-fill" data-width="{{ $fillAvailable }}"></div></div>
        <div class="md-body-sm" style="color:var(--md-on-surface-variant);">{{ $fillAvailable }}% of approved filled</div>
    </div>
    <div class="kpi-card" style="--kpi-color:var(--md-warning);">
        <div class="kpi-card__row">
            <div>
                <div class="kpi-card__label">Vacancies</div>
                <div class="kpi-card__value">{{ number_format($kpis['vacancy_total']) }}</div>
            </div>
            <div class="kpi-card__icon">&#128683;</div>
        </div>
        <div class="kpi-card__bar"><div class="kpi-card__bar-fill" data-width="{{ $fillVacancy }}"></div></div>
        <div class="md-body-sm" style="color:var(--md-on-surface-variant);">{{ $fillVacancy }}% of approved unfilled</div>
    </div>
    <div class="kpi-card" style="--kpi-color:var(--md-error);">
        <div class="kpi-card__row">
            <div>
                <div class="kpi-card__label">No-Pay Leave</div>
                <div class="kpi-card__value">{{ number_format($kpis['no_pay_leave']) }}</div>
            </div>
            <div class="kpi-card__icon">&#128197;</div>
        </div>
        <div class="kpi-card__bar"><div class="kpi-card__bar-fill" data-width="{{ $fillNoPayLeave }}"></div></div>
        <div class="md-body-sm" style="color:var(--md-on-surface-variant);">{{ $fillNoPayLeave }}% of available staff</div>
    </div>
</div>

{{-- ── Fixed charts: tabbed, full-width, scrollable ─────────── --}}
<div class="md-card md-card--elevated" style="margin-bottom:24px;">
    <div class="md-card__header" style="flex-wrap:wrap;gap:12px;">
        <span class="md-title-md">Workforce Charts</span>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <div class="chart-tabs">
                <button class="chart-tab chart-tab--active" onclick="switchChartTab('panel-avail', this)">Approved vs Available</button>
                <button class="chart-tab" onclick="switchChartTab('panel-vacancy', this)">Vacancy</button>
            </div>
            <button class="btn-export" onclick="exportChart('chartApprovedVsAvailable','approved-vs-available')">&#11015; PNG</button>
        </div>
    </div>
    <div class="md-card__body" style="padding:20px 20px 0;">
        <div id="panel-avail" class="chart-panel chart-panel--active">
            <div class="chart-scroll-host" id="scrollApproved">
                <div class="chart-inner" id="innerApproved">
                    <canvas id="chartApprovedVsAvailable"></canvas>
                </div>
            </div>
        </div>
        <div id="panel-vacancy" class="chart-panel">
            <div class="chart-scroll-host" id="scrollVacancy">
                <div class="chart-inner" id="innerVacancy">
                    <canvas id="chartVacancy"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="chart-legend">
        <div class="chart-legend-item"><span class="chart-legend-swatch" style="background:#A8C8FF;"></span> Approved</div>
        <div class="chart-legend-item"><span class="chart-legend-swatch" style="background:#7DD996;"></span> In Position</div>
        <div class="chart-legend-item"><span class="chart-legend-swatch" style="background:#F5C56B;"></span> Vacancy</div>
    </div>
</div>

{{-- ── Custom chart builder ──────────────────────────────────── --}}
<div class="md-card md-card--elevated" style="margin-bottom:24px;">
    <div class="md-card__header">
        <span class="md-title-md">Custom Chart Builder</span>
        <button class="btn-export" onclick="exportChart('chartCustom','custom-chart')">&#11015; Export PNG</button>
    </div>
    <div class="md-card__body">

        {{-- Controls row --}}
        <div class="builder-controls">
            <div>
                <label class="md-field__label">Metric</label>
                <select id="builderMetric" class="md-field__input" onchange="onMetricChange()">
                    <option value="approved_vs_available">Approved vs Available</option>
                    <option value="vacancy_by_position">Vacancy by Position</option>
                    <option value="no_pay_leave_trend">No-Pay Leave Trend</option>
                </select>
            </div>
            <div>
                <label class="md-field__label">Chart Type</label>
                <select id="builderType" class="md-field__input">
                    <option value="bar">Bar (Vertical)</option>
                    <option value="horizontalBar">Bar (Horizontal)</option>
                    <option value="line">Line</option>
                    <option value="pie">Pie / Donut</option>
                    <option value="doughnut">Doughnut</option>
                    <option value="radar">Radar</option>
                </select>
            </div>
            <div id="sortGroup">
                <label class="md-field__label">Sort by</label>
                <select id="builderSort" class="md-field__input">
                    <option value="name">Position Name</option>
                    <option value="desc">Highest First</option>
                    <option value="asc">Lowest First</option>
                </select>
            </div>
            <div>
                <button id="builderRun" class="md-btn md-btn--filled" style="height:44px;gap:8px;" onclick="CarderCharts.runBuilder()">
                    &#9654; Generate
                </button>
            </div>
            <div>
                <button id="toggleFilters" class="btn-export" style="height:44px;" onclick="toggleFilterPanel()">
                    &#9965; Filters
                </button>
            </div>
        </div>

        {{-- Collapsible filter panel --}}
        <div id="filterPanel" style="display:none;">
            <div class="builder-filter-panel">
                <div>
                    <label class="md-field__label">Top N Positions</label>
                    <select id="builderTopN" class="md-field__input">
                        <option value="0">All positions</option>
                        <option value="10">Top 10</option>
                        <option value="20">Top 20</option>
                        <option value="30">Top 30</option>
                    </select>
                </div>
                <div id="yearGroup">
                    <label class="md-field__label">Year</label>
                    <select id="builderYear" class="md-field__input">
                        @foreach($years as $y)
                            <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="monthGroup">
                    <label class="md-field__label">Month</label>
                    <select id="builderMonth" class="md-field__input">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>{{ \DateTime::createFromFormat('!m',$m)->format('F') }}</option>
                        @endfor
                    </select>
                </div>
            </div>

            {{-- Position keyword filter --}}
            <div style="margin-bottom:12px;">
                <label class="md-field__label">Filter positions by keyword</label>
                <input type="text" id="positionKeyword" class="md-field__input" placeholder="e.g. Nurse, Officer, SKS…" style="max-width:340px;">
            </div>
        </div>

        {{-- Chart area --}}
        <div class="chart-scroll-host" id="scrollCustom">
            <div class="chart-inner" id="innerCustom">
                <canvas id="chartCustom"></canvas>
            </div>
        </div>

        {{-- Dynamic legend --}}
        <div class="chart-legend" id="customLegend"></div>
    </div>
</div>

{{-- ── Position detail table ────────────────────────────────── --}}
<div class="md-card md-card--elevated">
    <div class="md-card__header" style="flex-wrap:wrap;gap:8px;">
        <span class="md-title-md">Position Detail</span>
        <input type="text" id="tableSearch" class="md-field__input" placeholder="Filter positions…"
               style="max-width:240px;height:36px;font-size:13px;"
               oninput="filterTable(this.value)">
    </div>
    <div style="overflow-x:auto;">
        <table class="md-table" id="positionTable">
            <thead>
                <tr>
                    <th>Position</th>
                    <th style="text-align:right;">Approved</th>
                    <th style="text-align:right;">Male</th>
                    <th style="text-align:right;">Female</th>
                    <th style="text-align:right;">In Position</th>
                    <th style="text-align:right;">Vacancy</th>
                    <th style="text-align:right;">T-In</th>
                    <th style="text-align:right;">T-Out</th>
                    <th style="text-align:right;">No-Pay</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $r)
                <tr class="pos-row">
                    <td data-label="{{ strtolower($r->position) }}">
                        {{ $r->position }}
                        @if($r->carried_forward)
                            <span class="md-badge md-badge--info" style="font-size:10px;margin-left:4px;" title="Carried forward from last submission">↻</span>
                        @endif
                    </td>
                    <td style="text-align:right;font-weight:600;">{{ $r->approved_total }}</td>
                    <td style="text-align:right;">{{ $r->available_male }}</td>
                    <td style="text-align:right;">{{ $r->available_female }}</td>
                    <td style="text-align:right;font-weight:600;">{{ $r->available_total }}</td>
                    <td style="text-align:right;">
                        @if($r->vacancy > 0)
                            <span class="md-badge md-badge--warning">{{ $r->vacancy }}</span>
                        @else
                            <span class="md-badge md-badge--success">0</span>
                        @endif
                    </td>
                    <td style="text-align:right;">{{ $r->transferred_in }}</td>
                    <td style="text-align:right;">{{ $r->transferred_out }}</td>
                    <td style="text-align:right;">{{ $r->no_pay_leave }}</td>
                </tr>
                @empty
                <tr><td colspan="9" class="md-table__empty">No data available for this period. Monthly entries may not yet be submitted.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/chart-builder.js') }}"></script>
<script>
// Animate KPI fill bars on load
document.querySelectorAll('.kpi-card__bar-fill').forEach(function(el){
    var w = el.getAttribute('data-width') || 0;
    setTimeout(function(){ el.style.width = Math.min(w, 100) + '%'; }, 100);
});

// Chart tab switcher
function switchChartTab(panelId, btn) {
    document.querySelectorAll('.chart-panel').forEach(function(p){ p.classList.remove('chart-panel--active'); });
    document.querySelectorAll('.chart-tab').forEach(function(b){ b.classList.remove('chart-tab--active'); });
    document.getElementById(panelId).classList.add('chart-panel--active');
    btn.classList.add('chart-tab--active');
}

// Filter panel toggle
function toggleFilterPanel() {
    var p = document.getElementById('filterPanel');
    p.style.display = p.style.display === 'none' ? 'block' : 'none';
}

// Hide month picker for trend metric (uses all 12 months)
function onMetricChange() {
    var metric = document.getElementById('builderMetric').value;
    var mg = document.getElementById('monthGroup');
    var sg = document.getElementById('sortGroup');
    if (mg) mg.style.display = metric === 'no_pay_leave_trend' ? 'none' : '';
    if (sg) sg.style.display = metric === 'no_pay_leave_trend' ? 'none' : '';
}

// Export chart as PNG
function exportChart(canvasId, filename) {
    const canvas = document.getElementById(canvasId);

    if (!canvas) {
        return;
    }

    const footerHeight = 70;
    const exportCanvas = document.createElement('canvas');
    exportCanvas.width = canvas.width;
    exportCanvas.height = canvas.height + footerHeight;

    const context = exportCanvas.getContext('2d');
    context.fillStyle = '#ffffff';
    context.fillRect(
        0,
        0,
        exportCanvas.width,
        exportCanvas.height
    );
    context.drawImage(canvas, 0, 0);

    context.fillStyle = '#455a64';
    context.font = '14px Arial';
    context.fillText(
        'INTERNAL DECISION SUPPORT — System-generated indicator; verify official authority before administrative action.',
        16,
        canvas.height + 28
    );
    context.font = '12px Arial';
    context.fillText(
        'Carder Management does not itself constitute Government policy, circular, regulation or an administrative determination.',
        16,
        canvas.height + 50
    );

    const link = document.createElement('a');
    link.download = filename + '-{{ $year }}-{{ $month }}.png';
    link.href = exportCanvas.toDataURL('image/png');
    link.click();
}

// Table keyword filter
function filterTable(keyword) {
    var lc = keyword.toLowerCase();
    document.querySelectorAll('#positionTable .pos-row').forEach(function(row){
        var label = row.querySelector('[data-label]').getAttribute('data-label');
        row.style.display = label.includes(lc) ? '' : 'none';
    });
}

// Drag-to-scroll for chart hosts
document.querySelectorAll('.chart-scroll-host').forEach(function(el){
    var isDown = false, startX, scrollLeft;
    el.addEventListener('mousedown', function(e){ isDown = true; startX = e.pageX - el.offsetLeft; scrollLeft = el.scrollLeft; });
    el.addEventListener('mouseleave', function(){ isDown = false; });
    el.addEventListener('mouseup', function(){ isDown = false; });
    el.addEventListener('mousemove', function(e){
        if (!isDown) return;
        e.preventDefault();
        el.scrollLeft = scrollLeft - (e.pageX - el.offsetLeft - startX);
    });
});

// Initialise charts
CarderCharts.init({
    year:    {{ $year }},
    month:   {{ $month }},
    dataUrl: '{{ route('reports.chart-data') }}',
});
</script>
@endpush
