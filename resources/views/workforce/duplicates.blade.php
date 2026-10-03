@extends('layouts.app')
@section('title', 'Duplicate Review')
@section('content')
    <h1 class="md-headline-md">Potential Duplicate Employees</h1>
    <p class="md-body-md">Review records sharing an NIC or Pay Number. No automatic merge or deletion is performed.</p>
    <div class="workforce-panel">
        <div style="overflow:auto">
            <table class="md-table">
                <thead>
                    <tr>
                        <th>Pay No.</th>
                        <th>NIC</th>
                        <th>Name</th>
                        <th>Position</th>
                        <th>Unit</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($duplicates as $e)
                        <tr>
                            <td>{{ $e->pay_no }}</td>
                            <td>{{ $e->masked_nic }}</td>
                            <td>{{ $e->display_name }}</td>
                            <td>{{ $e->position?->title }}</td>
                            <td>{{ $e->unit?->name }}</td>
                            <td>
                                <a href="{{ route('employees.show', $e) }}">Compare / review</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">No duplicate NIC or Pay Number groups detected.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
