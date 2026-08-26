@extends('pdf.layout')
@section('content')
<table>
    <thead>
        <tr>
            <th>Month</th>
            <th class="text-right">In Position</th>
            <th class="text-right">Approved</th>
            <th class="text-right">Vacancy</th>
            <th class="text-right">No-Pay Leave</th>
        </tr>
    </thead>
    <tbody>
        @foreach($trendData['months'] as $m)
        <tr>
            <td>{{ $m['label'] }} {{ $trendData['year'] }}</td>
            <td class="text-right">{{ $m['in_position'] !== null ? number_format($m['in_position']) : '—' }}</td>
            <td class="text-right">{{ number_format($trendData['approved']) }}</td>
            <td class="text-right {{ ($m['vacancy'] ?? 0) > 0 ? 'vacancy-warn' : 'vacancy-ok' }}">
                {{ $m['vacancy'] !== null ? number_format($m['vacancy']) : '—' }}
            </td>
            <td class="text-right">{{ $m['no_pay_leave'] ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection
