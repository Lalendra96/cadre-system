@extends('pdf.layout')
@section('content')
<table>
    <thead>
        <tr>
            <th>Position</th>
            <th class="text-right">Approved {{ $year1 }}</th>
            <th class="text-right">In Post {{ $year1 }}</th>
            <th class="text-right">Approved {{ $year2 }}</th>
            <th class="text-right">In Post {{ $year2 }}</th>
            <th class="text-right">Change</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $r)
        <tr>
            <td>{{ $r->title }}</td>
            <td class="text-right">{{ number_format($r->approved1) }}</td>
            <td class="text-right">{{ number_format($r->in_pos1) }}</td>
            <td class="text-right">{{ number_format($r->approved2) }}</td>
            <td class="text-right">{{ number_format($r->in_pos2) }}</td>
            <td class="text-right {{ $r->change_in_pos < 0 ? 'vacancy-critical' : ($r->change_in_pos > 0 ? 'vacancy-ok' : '') }}">
                {{ $r->change_in_pos > 0 ? '+' : '' }}{{ number_format($r->change_in_pos) }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection
