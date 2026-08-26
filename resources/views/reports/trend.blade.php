@extends('layouts.app')
@section('title', '12-Month Trend')
@push('head')
<style>
.trend-bar-wrap { display:flex; align-items:center; gap:8px; }
.trend-bar-bg   { flex:1; height:8px; background:var(--md-outline-variant); border-radius:4px; overflow:hidden; }
.trend-bar-fill { height:100%; border-radius:4px; transition:width .6s ease; }
</style>
@endpush
@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
<h2 class="md-headline-sm">12-Month Trend</h2>
@if($positionId)
<a href="{{ route('reports.trend.pdf', ['position_id'=>$positionId,'year'=>$year]) }}" class="md-btn md-btn--outlined">📄 PDF</a>
@endif
</div>

<form method="GET" style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;align-items:flex-end;">
    <div class="md-field" style="max-width:280px;">
        <label class="md-field__label">Position</label>
        <select name="position_id" class="md-field__input" onchange="this.form.submit()">
            <option value="">— Select a position —</option>
            @foreach($positions as $p)
                <option value="{{ $p->id }}" {{ $positionId == $p->id ? 'selected' : '' }}>{{ $p->title }}</option>
            @endforeach
        </select>
    </div>
    <div class="md-field" style="max-width:120px;">
        <label class="md-field__label">Year</label>
        <select name="year" class="md-field__input" onchange="this.form.submit()">
            @for($y = now()->year; $y >= now()->year - 3; $y--)
                <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
        </select>
    </div>
</form>

@if($trendData)
<div class="md-card md-card--elevated" style="margin-bottom:20px;">
    <div class="md-card__header">
        <span class="md-title-md">{{ $trendData['position']->title }} — {{ $year }}</span>
        <span class="md-badge md-badge--info">Approved: {{ $trendData['approved'] }}</span>
    </div>
    <div style="overflow-x:auto;padding:20px;">
        <div style="min-width:600px;height:300px;" class="md-chart-container">
            <canvas id="trendChart"></canvas>
        </div>
    </div>
    <div style="overflow-x:auto;">
        <table class="md-table">
            <thead><tr><th>Month</th><th style="text-align:right;">Approved</th><th style="text-align:right;">In Position</th><th style="text-align:right;">Vacancy</th><th style="text-align:right;">No-Pay</th><th>Fill Rate</th></tr></thead>
            <tbody>
                @foreach($trendData['months'] as $m)
                <tr>
                    <td class="md-label-md">{{ $m['label'] }}</td>
                    <td style="text-align:right;">{{ $trendData['approved'] }}</td>
                    <td style="text-align:right;">{{ $m['in_position'] ?? '—' }}</td>
                    <td style="text-align:right;">{{ $m['vacancy'] ?? '—' }}</td>
                    <td style="text-align:right;">{{ $m['no_pay_leave'] ?? '—' }}</td>
                    <td style="min-width:120px;">
                        @if($m['in_position'] !== null)
                        @php($pct = $trendData['approved'] > 0 ? round($m['in_position']/$trendData['approved']*100) : 0)
                        <div class="trend-bar-wrap">
                            <div class="trend-bar-bg">
                                <div class="trend-bar-fill" style="width:{{ $pct }}%;background:{{ $pct >= 90 ? 'var(--md-success)' : ($pct >= 60 ? 'var(--md-warning)' : 'var(--md-error)') }};"></div>
                            </div>
                            <span style="font-size:12px;width:36px;color:var(--md-on-surface-variant);">{{ $pct }}%</span>
                        </div>
                        @else
                        <span class="md-body-sm" style="color:var(--md-on-surface-variant);">No data</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: @json($trendData['months']->pluck('label')),
        datasets: [
            { label: 'Approved',     data: @json(array_fill(0, 12, $trendData['approved'])), borderColor: 'rgba(168,200,255,.4)', borderDash:[6,3], pointRadius:0, tension:0 },
            { label: 'In Position',  data: @json($trendData['months']->pluck('in_position')), borderColor:'#7DD996', backgroundColor:'rgba(125,217,150,.12)', fill:true, tension:.4, spanGaps:true },
        ]
    },
    options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{labels:{color:'#C3C6CF'}} }, scales:{ x:{ticks:{color:'#C3C6CF'}}, y:{ticks:{color:'#C3C6CF'}, beginAtZero:true} } }
});
</script>
@endpush
@else
<div class="md-card md-card--elevated" style="padding:60px;text-align:center;color:var(--md-on-surface-variant);">
    <p class="md-title-md">Select a position above to see its 12-month trend.</p>
</div>
@endif
@endsection
