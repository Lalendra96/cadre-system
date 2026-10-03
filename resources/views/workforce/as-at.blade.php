@extends('layouts.app')
@section('title', 'Historical Employee Register')
@section('content')
    <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap">
        <div>
            <h1 class="md-headline-md">Employee Register — As At</h1>
            <p class="md-body-md">Historical establishment view derived from employee start dates and recorded terminal
                lifecycle events.</p>
        </div>
        <form method="GET" style="display:flex;gap:8px;align-items:end">
            <label>As at<input class="md-input" type="date" name="date" value="{{ $date->toDateString() }}">
            </label>
            <button class="md-btn--primary">Load</button>
        </form>
    </div>
    <div class="workforce-kpi-grid">
        <div class="workforce-kpi">
            <div class="workforce-kpi__label">Employees as at {{ $date->format('d M Y') }}</div>
            <div class="workforce-kpi__value">{{ $employees->count() }}</div>
        </div>
    </div>
    <div class="workforce-panel">
        <div style="overflow:auto">
            <table class="md-table">
                <thead>
                    <tr>
                        <th>Pay No.</th>
                        <th>Name</th>
                        <th>Position</th>
                        <th>Unit</th>
                        <th>Subject Code</th>
                        <th>Joined Service</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $e)
                        <tr>
                            <td>{{ $e->pay_no }}</td>
                            <td>
                                <a href="{{ route('employees.show', $e) }}">{{ $e->display_name }}</a>
                            </td>
                            <td>{{ $e->position?->title }}</td>
                            <td>{{ $e->unit?->name }}</td>
                            <td>{{ $e->subjectCode?->code }}</td>
                            <td>{{ $e->date_joined_public_service?->format('d M Y') ?? $e->date_of_appointment?->format('d M Y') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">No employees matched this date.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
