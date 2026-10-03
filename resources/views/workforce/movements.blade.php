@extends('layouts.app')
@section('title', 'Employee Movements')
@section('content')
    <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap">
        <div>
            <h1 class="md-headline-md">Employee Movement Dashboard</h1>
            <p class="md-body-md">Joined, transferred, retired and other workforce movements for a selected period.</p>
        </div>
        <form method="GET" style="display:flex;gap:8px;align-items:end">
            <label>From<input class="md-input" type="date" name="from" value="{{ $from->toDateString() }}">
            </label>
            <label>To<input class="md-input" type="date" name="to" value="{{ $to->toDateString() }}">
            </label>
            <button class="md-btn--primary">Apply</button>
        </form>
    </div>
    <div class="workforce-kpi-grid">
        @foreach (['joined' => 'Joined / Activated', 'transfer_in' => 'Transferred In', 'transfer_out' => 'Transferred Out', 'retired' => 'Retired', 'resigned' => 'Resigned', 'deceased' => 'Deceased'] as $k => $l)
            <div class="workforce-kpi">
                <div class="workforce-kpi__label">{{ $l }}</div>
                <div class="workforce-kpi__value">{{ $counts[$k] }}</div>
            </div>
        @endforeach
    </div>
    <div class="workforce-panel">
        <h2 class="md-title-lg">Movement Detail</h2>
        <div style="overflow:auto">
            <table class="md-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Employee</th>
                        <th>Event</th>
                        <th>Position</th>
                        <th>Unit</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($events->sortByDesc('effective_date') as $e)
                        <tr>
                            <td>{{ $e->effective_date?->format('d M Y') }}</td>
                            <td>
                                <a href="{{ route('employees.show', $e->employee) }}">{{ $e->employee?->display_name }}</a>
                            </td>
                            <td>{{ ucwords(str_replace('_', ' ', $e->event_type)) }}</td>
                            <td>{{ $e->employee?->position?->title }}</td>
                            <td>{{ $e->employee?->unit?->name }}</td>
                            <td>{{ $e->details }}</td>
                        </tr>
                    @endforeach
                    @foreach ($transfers->sortByDesc('effective_date') as $e)
                        <tr>
                            <td>{{ $e->effective_date?->format('d M Y') }}</td>
                            <td>
                                <a
                                    href="{{ route('employees.show', $e->employee) }}">{{ $e->employee?->display_name }}</a>
                            </td>
                            <td>Transfer {{ strtoupper($e->direction) }}</td>
                            <td>{{ $e->employee?->position?->title }}</td>
                            <td>{{ $e->employee?->unit?->name }}</td>
                            <td>{{ $e->from_location }} → {{ $e->to_location }}</td>
                        </tr>
                    @endforeach
                    @if ($events->isEmpty() && $transfers->isEmpty())
                        <tr>
                            <td colspan="6">No movement records in this period.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
@endsection
