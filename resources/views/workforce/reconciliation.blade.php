@extends('layouts.app')
@section('title', 'Workforce Reconciliation')
@section('content')
    <div class="page-header">
        <div>
            <div class="page-eyebrow">Workforce Management</div>
            <h1 class="page-title">Carder Reconciliation</h1>
            <p class="page-subtitle">Compare approved cadre, unit allocations, employee profiles and monthly returns.</p>
        </div>
    </div>
    <form method="GET" style="display:flex;gap:8px;margin:16px 0;flex-wrap:wrap">
        <input class="md-input" style="max-width:130px" type="number" name="year" value="{{ $year }}">
        <input class="md-input" style="max-width:100px" type="number" min="1" max="12" name="month"
            value="{{ $month }}">
        <button class="md-btn--primary">Apply</button>
    </form>
    <div class="workforce-kpi-grid">
        <div class="workforce-kpi">
            <div class="workforce-kpi__label">Positions Checked</div>
            <div class="workforce-kpi__value">{{ $summary['positions'] }}</div>
        </div>
        <div class="workforce-kpi">
            <div class="workforce-kpi__label">Mismatched</div>
            <div class="workforce-kpi__value">{{ $summary['mismatched'] }}</div>
        </div>
        <div class="workforce-kpi">
            <div class="workforce-kpi__label">Critical Variance</div>
            <div class="workforce-kpi__value">{{ $summary['critical'] }}</div>
        </div>
        <div class="workforce-kpi">
            <div class="workforce-kpi__label">Largest Count Spread</div>
            <div class="workforce-kpi__value">{{ $summary['max_spread'] }}</div>
        </div>
    </div>
    <section class="workforce-panel">
        <div style="overflow:auto">
            <table class="md-table" style="width:100%">
                <thead>
                    <tr>
                        <th>Position</th>
                        <th>Approved</th>
                        <th>Unit Actual</th>
                        <th>Employee Master</th>
                        <th>Monthly Return</th>
                        <th>Employee ↔ Monthly</th>
                        <th>Unit ↔ Employee</th>
                        <th>Spread</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td>
                                <strong>{{ $row['p']->title }}</strong>
                            </td>
                            <td>{{ $row['vals']['approved'] }}</td>
                            <td>{{ $row['vals']['unit'] }}</td>
                            <td>{{ $row['vals']['employees'] }}</td>
                            <td>{{ $row['vals']['monthly'] }}</td>
                            <td>{{ $row['employee_vs_monthly'] > 0 ? '+' : '' }}{{ $row['employee_vs_monthly'] }}</td>
                            <td>{{ $row['unit_vs_employee'] > 0 ? '+' : '' }}{{ $row['unit_vs_employee'] }}</td>
                            <td>{{ $row['spread'] }} <span class="md-body-sm">({{ $row['spread_pct'] }}%)</span>
                            </td>
                            <td>
                                <span
                                    class="status-chip {{ $row['severity'] === 'aligned' ? 'status-chip--good' : ($row['severity'] === 'critical' ? 'status-chip--bad' : '') }}">{{ strtoupper($row['severity']) }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection
