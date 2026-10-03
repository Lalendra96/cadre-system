@extends('layouts.app')

@section('title', $assessment->reference_no)

@section('content')
    <div class="page-header">
        <div>
            <div class="page-eyebrow">Hospital Planning · {{ $assessment->reference_no }}</div>
            <h1 class="page-title">{{ $assessment->title }}</h1>
            <p class="page-subtitle">{{ \App\Models\HospitalPlanningAssessment::AREAS[$assessment->planning_area] ?? $assessment->planning_area }}</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a class="md-btn" href="{{ route('planning.assessments.index') }}">← Register</a>
            @if ($assessment->status !== \App\Models\HospitalPlanningAssessment::STATUS_REFERRED)
                <a class="md-btn md-btn--primary" href="{{ route('planning.assessments.edit', $assessment) }}">Edit worksheet</a>
            @endif
        </div>
    </div>

    <div class="alert alert-warning" role="note" style="border-left:5px solid var(--md-warning);margin-bottom:16px">
        <strong>Decision-support only.</strong>
        This assessment documents a Planning Officer’s analysis and recommendation. It is not an appointment, procurement approval,
        financial approval, service-change authority or final institutional decision. The competent authority must decide through the applicable official process.
    </div>

    <section class="md-card" style="padding:18px;margin-bottom:16px">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px">
            <div><span class="md-body-sm">Status</span><br><strong>{{ \App\Models\HospitalPlanningAssessment::STATUSES[$assessment->status] ?? $assessment->status }}</strong></div>
            <div><span class="md-body-sm">Prepared by</span><br><strong>{{ $assessment->preparer?->name ?? '—' }}</strong></div>
            <div><span class="md-body-sm">Data as at</span><br><strong>{{ $assessment->data_as_at ?: 'Not stated' }}</strong></div>
            <div><span class="md-body-sm">Authority reference</span><br><strong>{{ $assessment->authority_reference ?: 'Not stated' }}</strong></div>
        </div>
    </section>

    @foreach ([
        'Planning question' => $assessment->planning_question,
        'Objective' => $assessment->objective,
        'Evidence summary' => $assessment->evidence_summary,
        'Assumptions / limitations' => $assessment->assumptions,
        'Options considered' => $assessment->options_considered,
        'Planning recommendation' => $assessment->recommended_option,
        'Workforce impact' => $assessment->workforce_impact,
        'Financial / resource impact' => $assessment->financial_impact,
        'Service-delivery impact' => $assessment->service_delivery_impact,
        'Equity / access considerations' => $assessment->equity_and_access_considerations,
        'Risks and mitigations' => $assessment->risks_and_mitigations,
    ] as $label => $value)
        <section class="md-card" style="padding:18px;margin-bottom:12px">
            <h2 class="md-title-sm">{{ $label }}</h2>
            <div style="white-space:pre-line">{{ $value ?: 'Not recorded.' }}</div>
        </section>
    @endforeach

    <section class="md-card" style="padding:18px;margin-bottom:16px">
        <h2 class="md-title-md">Safeguard completion</h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;margin-top:12px">
            @foreach ([
                'Support-only boundary' => $assessment->support_only_acknowledged,
                'Evidence verified' => $assessment->evidence_verified,
                'Alternatives considered' => $assessment->alternatives_considered,
                'Risks considered' => $assessment->risks_considered,
                'Minimum necessary data' => $assessment->minimum_necessary_confirmed,
            ] as $label => $complete)
                <div class="md-card" style="padding:12px">
                    <strong>{{ $complete ? '✓' : '!' }} {{ $label }}</strong>
                </div>
            @endforeach
        </div>
    </section>

    @if ($assessment->status === \App\Models\HospitalPlanningAssessment::STATUS_DRAFT)
        <section class="md-card" style="padding:18px;margin-bottom:16px">
            <h2 class="md-title-md">Mark ready for authorised review</h2>
            <p class="md-body-sm">This confirms the planning worksheet is ready to be reviewed. It does not approve the recommendation.</p>
            @include('planning.assessments._attestation-form', [
                'action' => route('planning.assessments.ready', $assessment),
                'button' => 'Mark ready for review',
            ])
        </section>
    @elseif ($assessment->status === \App\Models\HospitalPlanningAssessment::STATUS_READY)
        <section class="md-card" style="padding:18px;margin-bottom:16px">
            <h2 class="md-title-md">Refer planning recommendation</h2>
            <p class="md-body-sm">Referral sends the recommendation onward as advisory material. The authorised authority remains responsible for the final decision.</p>
            @include('planning.assessments._attestation-form', [
                'action' => route('planning.assessments.refer', $assessment),
                'button' => 'Refer for authorised decision',
            ])
        </section>
    @else
        <div class="alert alert-success" role="status">
            <strong>Referred as planning advice.</strong> No final institutional approval is recorded by this planning worksheet.
        </div>
    @endif
@endsection
