@extends('layouts.app')
@section('title', 'Scenario Comparison')
@section('content')
    <div class="page-header">
        <div>
            <div class="page-eyebrow">Workforce planning</div>
            <h1 class="page-title">Scenario Comparison</h1>
            <p class="page-subtitle">Compare recruitment and retirement assumptions side by side before making an
                establishment decision.</p>
        </div>
    </div>
    <section class="md-card" style="padding:20px;margin-bottom:16px">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap">
            <label>Base recruitment<input class="md-input" type="number" name="base_recruitment" min="0"
                    value="0">
            </label>
            <label>Planned recruitment<input class="md-input" type="number" name="planned_recruitment" min="0"
                    value="0">
            </label>
            <label>Additional retirements<input class="md-input" type="number" name="high_retirement" min="0"
                    value="0">
            </label>
            <button class="md-btn--primary">Compare scenarios</button>
        </form>
    </section>
    <section class="md-card" style="padding:20px">
        <table class="md-table" style="width:100%">
            <thead>
                <tr>
                    <th>Scenario</th>
                    <th>Recruitment</th>
                    <th>Retirements</th>
                    <th>Projected headcount</th>
                    <th>Projected gap</th>
                    <th>Fill rate</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($scenarios as $scenario)
                    <tr>
                        <td>
                            <strong>{{ $scenario['name'] }}</strong>
                        </td>
                        <td>{{ $scenario['recruitment'] }}</td>
                        <td>{{ $scenario['retirements'] }}</td>
                        <td>{{ $scenario['projected_headcount'] }}</td>
                        <td>{{ $scenario['projected_gap'] }}</td>
                        <td>{{ $scenario['fill_rate'] ?? '—' }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
@endsection
