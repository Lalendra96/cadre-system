@extends('layouts.app')
@section('title', 'Historical Snapshot')
@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
    <h2 class="md-headline-sm">Historical Snapshot — {{ \DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}</h2>
    <div style="display:flex;gap:8px;">
        <a href="{{ route('reports.export.moh-csv', ['year'=>$year,'month'=>$month]) }}" class="md-btn md-btn--outlined">⬇ CSV</a>
        <a href="{{ route('reports.snapshot.pdf', ['year'=>$year,'month'=>$month]) }}" class="md-btn md-btn--outlined">📄 PDF</a>
        <a href="{{ route('reports.export.moh-print', ['year'=>$year,'month'=>$month]) }}" class="md-btn md-btn--outlined" target="_blank">🖨 Print</a>
        <a href="{{ route('reports.export.carder-register', ['year'=>$year]) }}" class="md-btn md-btn--outlined" target="_blank">📋 Carder Register</a>
    </div>
</div>

<form method="GET" style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;">
    <select name="year" class="md-field__input" style="height:40px;max-width:120px;" onchange="this.form.submit()">
        @for($y = now()->year; $y >= now()->year - 5; $y--)
            <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
        @endfor
    </select>
    <select name="month" class="md-field__input" style="height:40px;max-width:150px;" onchange="this.form.submit()">
        @for($m = 1; $m <= 12; $m++)
            <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>{{ \DateTime::createFromFormat('!m', $m)->format('F') }}</option>
        @endfor
    </select>
</form>

<div class="md-card md-card--elevated">
    <div style="overflow-x:auto;">
        <table class="md-table">
            <thead><tr><th>Position</th><th style="text-align:right;">Approved</th><th style="text-align:right;">Male</th><th style="text-align:right;">Female</th><th style="text-align:right;">In Position</th><th style="text-align:right;">Vacancy</th><th style="text-align:right;">No-Pay</th><th>Fill</th></tr></thead>
            <tbody>
                @forelse($rows as $r)
                <tr>
                    <td>{{ $r->title }}@if($r->carried) <span class="md-badge md-badge--info" style="font-size:10px;margin-left:4px;">↻</span>@endif</td>
                    <td style="text-align:right;font-weight:600;">{{ $r->approved }}</td>
                    <td style="text-align:right;">{{ $r->males }}</td>
                    <td style="text-align:right;">{{ $r->females }}</td>
                    <td style="text-align:right;font-weight:600;">{{ $r->in_position }}</td>
                    <td style="text-align:right;">
                        @if($r->vacancy > 0)<span class="md-badge md-badge--warning">{{ $r->vacancy }}</span>
                        @else<span class="md-badge md-badge--success">0</span>@endif
                    </td>
                    <td style="text-align:right;">{{ $r->no_pay > 0 ? $r->no_pay : '—' }}</td>
                    <td>
                        @php($pct = $r->approved > 0 ? round($r->in_position/$r->approved*100) : 0)
                        <div style="display:flex;align-items:center;gap:6px;">
                            <div style="width:60px;height:5px;background:var(--md-outline-variant);border-radius:3px;overflow:hidden;">
                                <div style="width:{{ min($pct,100) }}%;height:100%;background:{{ $pct>=90?'var(--md-success)':($pct>=60?'var(--md-warning)':'var(--md-error)') }};border-radius:3px;"></div>
                            </div>
                            <span style="font-size:11px;color:var(--md-on-surface-variant);">{{ $pct }}%</span>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="md-table__empty">No data for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
