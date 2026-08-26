@extends('pdf.layout')
@section('content')
<table>
    <thead>
        <tr>
            <th>Pay No.</th>
            <th>Name</th>
            <th>Position</th>
            <th>Date of Birth</th>
            <th class="text-right">Age</th>
            <th>Retirement Date</th>
            <th class="text-right">Months Left</th>
            <th class="text-center">Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($employees as $e)
        @php
            $badgeClass = match($e->status) {
                'overdue'  => 'badge-danger',
                'critical' => 'badge-warning',
                'soon'     => 'badge-warning',
                'upcoming' => 'badge-info',
                default    => 'badge-neutral',
            };
        @endphp
        <tr>
            <td>{{ $e->pay_no }}</td>
            <td>{{ $e->name }}</td>
            <td>{{ $e->position }}</td>
            <td>{{ $e->dob }}</td>
            <td class="text-right">{{ $e->age }}</td>
            <td>{{ $e->retire_date }}</td>
            <td class="text-right {{ $e->months_left < 0 ? 'vacancy-critical' : '' }}">{{ $e->months_left }}</td>
            <td class="text-center"><span class="badge {{ $badgeClass }}">{{ ucfirst($e->status) }}</span></td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection
