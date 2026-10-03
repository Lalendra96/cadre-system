@extends('layouts.app')
@section('content')
    @include('enterprise._head', [
        'title' => 'Employee Service Record',
        'subtitle' =>
            'A unified service-book view from appointment through retirement, including movements, grade, EB, increments, competencies, registrations and verified documents.',
    ])
    <div class="enterprise-grid">
        @foreach ($metrics as $k => $v)
            <div class="enterprise-kpi">
                <span>{{ ucwords(str_replace('_', ' ', $k)) }}</span><strong>{{ number_format($v) }}</strong>
            </div>
        @endforeach
    </div>
    <div class="enterprise-grid">
        <div class="enterprise-card">
            <h3>Employee 360</h3>
            <p>Identity, appointment, service dates, status, retirement, profile completeness and lifecycle timeline.</p><a
                class="enterprise-link" href="{{ route('employees.index') }}">Find employee →</a>
        </div>
        <div class="enterprise-card">
            <h3>Movements & Career</h3>
            <p>Transfers, service periods, grade history, confirmations, acting appointments and interdictions.</p><a
                class="enterprise-link" href="{{ route('workforce.movements') }}">Movement register →</a>
        </div>
        <div class="enterprise-card">
            <h3>Professional Readiness</h3>
            <p>Training, competency assessments, qualifications and registration expiry monitoring.</p><a
                class="enterprise-link" href="{{ route('data-quality.index') }}">Check data quality →</a>
        </div>
        <div class="enterprise-card">
            <h3>Documents & Corrections</h3>
            <p>Verified employee documents, supersession history and employee/self-service correction workflow.</p><a
                class="enterprise-link" href="{{ route('employees.index') }}">Employee records →</a>
        </div>
    </div>
@endsection
