@extends('layouts.app')
@section('content')
    @include('enterprise._head', [
        'title' => 'Workforce Intelligence',
        'subtitle' =>
            'Forward-looking workforce planning for retirement, vacancy pressure, workload, staffing gaps, succession readiness and accountable dashboards.',
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
            <h3>Retirement Projection</h3>
            <p>Upcoming retirements, replacement requirement, pension/clearance/handover readiness.</p><a
                class="enterprise-link" href="{{ route('retirement-projects.index') }}">Retirement projects →</a>
        </div>
        <div class="enterprise-card">
            <h3>Vacancy Pressure & Forecast</h3>
            <p>Compare establishment demand with workforce availability and forward vacancy risk.</p><a
                class="enterprise-link" href="{{ route('workforce.forecast') }}">Forecast →</a>
        </div>
        <div class="enterprise-card">
            <h3>Officer Workload</h3>
            <p>Responsibility ownership, unassigned files, duplicate ownership, reassignment queue and workload.</p><a
                class="enterprise-link" href="{{ route('hr-intelligence.index') }}">HR intelligence →</a>
        </div>
        <div class="enterprise-card">
            <h3>Succession Readiness</h3>
            <p>Use grade, competency, training and retirement evidence to identify development gaps for critical posts.</p>
            <a class="enterprise-link" href="{{ route('workforce.action-center') }}">Action centre →</a>
        </div>
    </div>
@endsection
