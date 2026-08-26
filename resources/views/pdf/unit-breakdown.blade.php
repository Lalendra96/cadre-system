@extends('pdf.layout')
@section('content')
<table style="font-size:7pt;">
    <thead>
        <tr>
            <th>Position</th>
            @foreach($units as $u)
                <th class="text-right">{{ $u->code }}</th>
            @endforeach
            <th class="text-right">Approved</th>
            <th class="text-right">In Post</th>
            <th class="text-right">Vacancy</th>
            <th class="text-right">Fill %</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $r)
        @php
            $fillClass = $r->fillPct >= 90 ? 'vacancy-ok' : ($r->fillPct >= 70 ? 'vacancy-warn' : 'vacancy-critical');
        @endphp
        <tr>
            <td>{{ $r->position->title }}</td>
            @foreach($r->unitCells as $cell)
                <td class="text-right">{{ $cell ?: '—' }}</td>
            @endforeach
            <td class="text-right">{{ number_format($r->approvedAmt) }}</td>
            <td class="text-right bold">{{ number_format($r->totalInPos) }}</td>
            <td class="text-right {{ $r->vacancy > 0 ? 'vacancy-warn' : 'vacancy-ok' }}">{{ number_format($r->vacancy) }}</td>
            <td class="text-right {{ $fillClass }}">{{ $r->fillPct }}%</td>
        </tr>
        @empty
        <tr><td colspan="{{ $units->count() + 5 }}" class="text-muted">No data available for the selected period.</td></tr>
        @endforelse
    </tbody>
</table>
<p class="text-muted" style="font-size:7.5pt;">
    Column headers are unit codes. In-post figures are sourced from Unit Post Allocations
    where entered, otherwise from employee profile records.
    Positions with fill % below {{ $threshold ?? 20 }}% are highlighted.
</p>
@endsection
