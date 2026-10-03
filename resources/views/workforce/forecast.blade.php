@extends('layouts.app')
@section('title', 'Workforce Forecast')
@section('content')
    <div class="page-header">
        <div>
            <div class="page-eyebrow">Workforce Management</div>
            <h1 class="page-title">Workforce Forecast</h1>
            <p class="page-subtitle">Project staffing pressure from vacancies, retirements and employee movements.</p>
        </div>
    </div>
    <form method="GET" style="display:flex;gap:8px;margin:16px 0;flex-wrap:wrap">
        <input class="md-input" style="max-width:130px" type="number" name="year" value="{{ $year }}">
        <select class="md-select" style="max-width:160px" name="months">
            @foreach ([6, 12, 24, 36, 60] as $m)
                <option value="{{ $m }}" @selected($months == $m)>{{ $m }} months</option>
            @endforeach
        </select>
        <button class="md-btn--primary">Forecast</button>
    </form>
    <div class="workforce-kpi-grid">
        <div class="workforce-kpi">
            <div class="workforce-kpi__label">Positions</div>
            <div class="workforce-kpi__value">{{ $summary['positions'] }}</div>
        </div>
        <div class="workforce-kpi">
            <div class="workforce-kpi__label">Critical Risk</div>
            <div class="workforce-kpi__value">{{ $summary['critical'] }}</div>
        </div>
        <div class="workforce-kpi">
            <div class="workforce-kpi__label">High Risk</div>
            <div class="workforce-kpi__value">{{ $summary['high'] }}</div>
        </div>
        <div class="workforce-kpi">
            <div class="workforce-kpi__label">Retiring in Horizon</div>
            <div class="workforce-kpi__value">{{ $summary['retiring'] }}</div>
        </div>
        <div class="workforce-kpi">
            <div class="workforce-kpi__label">Projected Vacancies</div>
            <div class="workforce-kpi__value">{{ $summary['projected_vacancies'] }}</div>
        </div>
    </div>
    <section class="workforce-panel">
        <div style="overflow:auto">
            <table class="md-table" style="width:100%">
                <thead>
                    <tr>
                        <th>Position</th>
                        <th>Approved</th>
                        <th>Current</th>
                        <th>Retiring</th>
                        <th>Transfer Out</th>
                        <th>Transfer In</th>
                        <th>Future Headcount</th>
                        <th>Vacancy</th>
                        <th>Fill Rate</th>
                        <th>Retirement Exposure</th>
                        <th>Risk</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td>
                                <strong>{{ $row['position']->title }}</strong>
                            </td>
                            <td>{{ $row['approved'] }}</td>
                            <td>{{ $row['current'] }}</td>
                            <td>{{ $row['retiring'] }}</td>
                            <td>{{ $row['transfer_out'] }}</td>
                            <td>{{ $row['transfer_in'] }}</td>
                            <td>{{ $row['future_headcount'] }}</td>
                            <td>
                                <strong>{{ $row['projected_vacancy'] }}</strong>
                            </td>
                            <td>{{ $row['projected_fill_rate'] === null ? '—' : $row['projected_fill_rate'] . '%' }}</td>
                            <td>{{ $row['retirement_exposure'] }}%</td>
                            <td>
                                <span
                                    class="status-chip {{ $row['risk'] === 'stable' ? 'status-chip--good' : ($row['risk'] === 'critical' ? 'status-chip--bad' : '') }}">{{ strtoupper($row['risk']) }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection
