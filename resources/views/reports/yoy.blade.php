@extends('layouts.app')
@section('title', 'Year-on-Year Comparison')
@section('content')
<h2 class="md-headline-sm" style="margin-bottom:16px;">Year-on-Year Comparison</h2>

<form method="GET" style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;align-items:flex-end;">
    <div class="md-field" style="max-width:120px;">
        <label class="md-field__label">Year 1</label>
        <select name="year1" class="md-field__input" onchange="this.form.submit()">
            @for($y = now()->year; $y >= now()->year - 4; $y--)
                <option value="{{ $y }}" {{ $y == $year1 ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
        </select>
    </div>
    <div class="md-field" style="max-width:120px;">
        <label class="md-field__label">Year 2</label>
        <select name="year2" class="md-field__input" onchange="this.form.submit()">
            @for($y = now()->year; $y >= now()->year - 4; $y--)
                <option value="{{ $y }}" {{ $y == $year2 ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
        </select>
    </div>
    <div class="md-field" style="max-width:150px;">
        <label class="md-field__label">Month</label>
        <select name="month" class="md-field__input" onchange="this.form.submit()">
            @for($m = 1; $m <= 12; $m++)
                <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>{{ \DateTime::createFromFormat('!m', $m)->format('F') }}</option>
            @endfor
        </select>
    </div>
    <a href="{{ route('reports.export.moh-csv', ['year'=>$year2,'month'=>$month]) }}" class="md-btn md-btn--outlined">⬇ CSV Export</a>
    <a href="{{ route('reports.yoy.pdf', ['year1'=>$year1,'year2'=>$year2,'month'=>$month]) }}" class="md-btn md-btn--outlined">📄 PDF</a>
</form>

<div class="md-card md-card--elevated">
    <div style="overflow-x:auto;">
        <table class="md-table">
            <thead>
                <tr>
                    <th>Position</th>
                    <th style="text-align:right;">Approved {{ $year1 }}</th>
                    <th style="text-align:right;">In Position {{ $year1 }}</th>
                    <th style="text-align:right;">Approved {{ $year2 }}</th>
                    <th style="text-align:right;">In Position {{ $year2 }}</th>
                    <th style="text-align:right;">Change</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $r)
                <tr>
                    <td class="md-label-md">{{ $r->title }}</td>
                    <td style="text-align:right;">{{ $r->approved1 }}</td>
                    <td style="text-align:right;">{{ $r->in_pos1 }}</td>
                    <td style="text-align:right;">{{ $r->approved2 }}</td>
                    <td style="text-align:right;">{{ $r->in_pos2 }}</td>
                    <td style="text-align:right;">
                        @if($r->change_in_pos > 0)
                            <span class="md-badge md-badge--success">▲ {{ $r->change_in_pos }}</span>
                        @elseif($r->change_in_pos < 0)
                            <span class="md-badge md-badge--critical">▼ {{ abs($r->change_in_pos) }}</span>
                        @else
                            <span class="md-badge md-badge--neutral">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="md-table__empty">No data for selected periods.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
