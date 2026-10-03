@extends('layouts.app')

@section('content')
    @include('roster.partials.style')

    <div class="container-fluid py-4 roster-shell">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="roster-title mb-1">Roster</h3>
                <div class="text-muted">Plan and manage unit assignments with time slots.</div>
            </div>
            <a href="{{ route('roster.templates.create') }}" class="btn btn-steel">+ Create Template</a>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <div class="card roster-card stat-card">
                    <div class="card-body">
                        <div class="small text-muted">Templates</div>
                        <div class="stat-value">{{ $stats['templates'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card roster-card stat-card">
                    <div class="card-body">
                        <div class="small text-muted">Assignments</div>
                        <div class="stat-value">{{ $stats['assignments'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card roster-card stat-card">
                    <div class="card-body">
                        <div class="small text-muted">Pending Approvals</div>
                        <div class="stat-value">{{ $stats['pending'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card roster-card stat-card">
                    <div class="card-body">
                        <div class="small text-muted">Active Rosters</div>
                        <div class="stat-value">{{ $stats['active'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card roster-card">
            <div class="card-header bg-white d-flex justify-content-between">
                <strong>Upcoming Assignments</strong>
                <a href="{{ route('roster.plans.index') }}">View all plans</a>
            </div>
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Employee</th>
                            <th>Duty Unit</th>
                            <th>Duty</th>
                            <th>Coverage</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($upcoming as $assignment)
                            <tr>
                                <td>{{ $assignment->duty_date?->format('d M Y') ?? '—' }}</td>
                                <td>
                                    {{ substr((string) $assignment->start_time, 0, 5) }}–{{ substr((string) $assignment->end_time, 0, 5) }}
                                </td>
                                <td>{{ $assignment->employee?->display_name ?? '—' }}</td>
                                <td>{{ $assignment->dutyUnit?->name ?? '—' }}</td>
                                <td>{{ $assignment->duty_role ?: '—' }}</td>
                                <td>
                                    <span class="status-pill {{ $assignment->coverage_type === 'cross_unit' ? 'cross-unit' : 'home-unit' }}">
                                        {{ ucwords(str_replace('_', ' ', $assignment->coverage_type)) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No upcoming assignments.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
