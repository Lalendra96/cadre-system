@extends('layouts.app')
@section('content')
    @include('enterprise._head', [
        'title' => 'Cadre & Establishment',
        'subtitle' =>
            'Approved establishment, filled posts, vacancies, excesses, organizational structure and effective historical cadre controls.',
    ])
    <div class="enterprise-grid">
        @foreach ([['Approved posts', $approved], ['Active employees', $activeEmployees], ['Vacancies', $vacancies], ['Excess', $excess], ['Positions', $positions], ['Units', $units]] as [$l, $v])
            <div class="enterprise-kpi"><span>{{ $l }} ·
                    {{ $year }}</span><strong>{{ number_format($v) }}</strong></div>
        @endforeach
    </div>
    <div class="enterprise-grid">
        <div class="enterprise-card">
            <h3>Approved Establishment</h3>
            <p>Manage approved cadre by position, reference, year and source authority.</p>
            <div class="enterprise-actions"><a class="enterprise-link" href="{{ route('approved-carders.index') }}">Open
                    approved cadre →</a></div>
        </div>
        <div class="enterprise-card">
            <h3>Organizational Structure</h3>
            <p>Positions, categories, units, subcategories and unit-position bindings.</p>
            <div class="enterprise-actions"><a class="enterprise-link" href="{{ route('positions.index') }}">Positions</a><a
                    class="enterprise-link" href="{{ route('units.index') }}">Units</a></div>
        </div>
        <div class="enterprise-card">
            <h3>Vacancy & Excess Control</h3>
            <p>Use reconciled staffing information to identify shortages, excesses and recruitment pressure.</p>
            <div class="enterprise-actions"><a class="enterprise-link"
                    href="{{ route('workforce.reconciliation') }}">Reconciliation</a></div>
        </div>
        <div class="enterprise-card">
            <h3>Historical Cadre</h3>
            <p>{{ number_format($reviews) }} cadre review proposal(s) recorded. Keep effective dates and approval references
                as evidence.</p>
            <div class="enterprise-actions"><a class="enterprise-link" href="{{ route('cadre-reviews.index') }}">Cadre
                    reviews</a></div>
        </div>
    </div>
@endsection
