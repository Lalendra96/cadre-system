@extends('pdf.layout')
@section('content')
<table>
    <thead>
        <tr>
            <th>Position</th>
            <th class="text-right">Approved</th>
            <th class="text-right">In Position</th>
            <th class="text-right">Males</th>
            <th class="text-right">Females</th>
            <th class="text-right">Vacancy</th>
            <th class="text-right">No-Pay Leave</th>
            <th class="text-center">Source</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $r)
        @php
            $vacPct = $r->approved > 0 ? round($r->vacancy / $r->approved * 100) : 0;
            $vacClass = $vacPct >= 20 ? 'vacancy-critical' : ($vacPct >= 10 ? 'vacancy-warn' : 'vacancy-ok');
        @endphp
        <tr>
            <td>{{ $r->title }}</td>
            <td class="text-right">{{ number_format($r->approved) }}</td>
            <td class="text-right bold">{{ number_format($r->in_position) }}</td>
            <td class="text-right">{{ number_format($r->males) }}</td>
            <td class="text-right">{{ number_format($r->females) }}</td>
            <td class="text-right {{ $vacClass }}">{{ number_format($r->vacancy) }} ({{ $vacPct }}%)</td>
            <td class="text-right">{{ number_format($r->no_pay) }}</td>
            <td class="text-center">
                @if($r->carried)
                    <span class="badge badge-carried">Carried Fwd</span>
                @else
                    <span class="badge badge-success">Actual</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td>TOTAL</td>
            <td class="text-right">{{ number_format($rows->sum('approved')) }}</td>
            <td class="text-right">{{ number_format($rows->sum('in_position')) }}</td>
            <td class="text-right">{{ number_format($rows->sum('males')) }}</td>
            <td class="text-right">{{ number_format($rows->sum('females')) }}</td>
            <td class="text-right">{{ number_format($rows->sum('vacancy')) }}</td>
            <td class="text-right">{{ number_format($rows->sum('no_pay')) }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>
<p class="text-muted" style="font-size:8pt;">
    "Carried Fwd" indicates no entry was submitted for this exact period; the most recent prior submission is shown instead.
</p>
@endsection
