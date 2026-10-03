@extends('layouts.app')

@section('title', $assessment->exists ? 'Edit Planning Assessment' : 'New Planning Assessment')

@section('content')
    <div class="page-header">
        <div>
            <div class="page-eyebrow">Hospital Planning · evidence-led worksheet</div>
            <h1 class="page-title">{{ $assessment->exists ? 'Edit Planning Assessment' : 'New Planning Assessment' }}</h1>
            <p class="page-subtitle">A structured worksheet for human planning judgement. It is not an approval form.</p>
        </div>
        <a href="{{ route('planning.assessments.index') }}" class="md-btn">← Planning register</a>
    </div>

    @include('partials.governance-legal-safeguard', [
        'title' => 'Decision-support boundary',
        'purpose' => 'Document evidence, assumptions, options and a planning recommendation. Do not use this worksheet as legal authority, procurement approval, financial approval, appointment authority or service-change approval.',
    ])

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <strong>Please correct the highlighted information.</strong>
            <ul style="margin:8px 0 0 20px">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $assessment->exists ? route('planning.assessments.update', $assessment) : route('planning.assessments.store') }}">
        @csrf
        @if ($assessment->exists)
            @method('PUT')
        @endif

        <section class="md-card" style="padding:20px;margin-bottom:16px">
            <h2 class="md-title-md">1. Define the planning need</h2>
            <p class="md-body-sm">State the hospital planning problem neutrally before choosing a preferred option.</p>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px;margin-top:14px">
                <label>
                    <span class="md-body-sm">Title *</span>
                    <input class="md-input" name="title" required maxlength="200" value="{{ old('title', $assessment->title) }}">
                </label>
                <label>
                    <span class="md-body-sm">Planning area *</span>
                    <select class="md-input" name="planning_area" required>
                        <option value="">Select area</option>
                        @foreach ($areas as $value => $label)
                            <option value="{{ $value }}" @selected(old('planning_area', $assessment->planning_area) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span class="md-body-sm">Data as at</span>
                    <input class="md-input" name="data_as_at" maxlength="80" placeholder="e.g. 30 Sep 2026 / August 2026 return" value="{{ old('data_as_at', $assessment->data_as_at) }}">
                </label>
                <label>
                    <span class="md-body-sm">Authority / policy reference</span>
                    <input class="md-input" name="authority_reference" maxlength="200" placeholder="Circular, minute, approved plan, allocation or other source" value="{{ old('authority_reference', $assessment->authority_reference) }}">
                </label>
            </div>
            <label style="display:block;margin-top:14px">
                <span class="md-body-sm">Planning question *</span>
                <textarea class="md-input" name="planning_question" rows="3" required placeholder="What decision or resource-planning question needs to be considered?">{{ old('planning_question', $assessment->planning_question) }}</textarea>
            </label>
            <label style="display:block;margin-top:14px">
                <span class="md-body-sm">Objective *</span>
                <textarea class="md-input" name="objective" rows="3" required placeholder="What outcome is the hospital trying to achieve?">{{ old('objective', $assessment->objective) }}</textarea>
            </label>
        </section>

        <section class="md-card" style="padding:20px;margin-bottom:16px">
            <h2 class="md-title-md">2. Evidence and assumptions</h2>
            <p class="md-body-sm">Separate source evidence from assumptions so reviewers can understand what is known and what is estimated.</p>
            <label style="display:block;margin-top:14px">
                <span class="md-body-sm">Evidence summary *</span>
                <textarea class="md-input" name="evidence_summary" rows="5" required placeholder="Approved cadre, workload, service statistics, patient demand, finance, infrastructure constraints, incident history, official reports, etc.">{{ old('evidence_summary', $assessment->evidence_summary) }}</textarea>
            </label>
            <label style="display:block;margin-top:14px">
                <span class="md-body-sm">Assumptions / limitations</span>
                <textarea class="md-input" name="assumptions" rows="4" placeholder="State forecast assumptions, missing data, uncertainty and limitations.">{{ old('assumptions', $assessment->assumptions) }}</textarea>
            </label>
        </section>

        <section class="md-card" style="padding:20px;margin-bottom:16px">
            <h2 class="md-title-md">3. Compare reasonable options</h2>
            <p class="md-body-sm">Record alternatives, including maintaining the current arrangement when that is a realistic option.</p>
            <label style="display:block;margin-top:14px">
                <span class="md-body-sm">Options considered *</span>
                <textarea class="md-input" name="options_considered" rows="6" required placeholder="Option A — ...\nOption B — ...\nOption C — ...">{{ old('options_considered', $assessment->options_considered) }}</textarea>
            </label>
            <label style="display:block;margin-top:14px">
                <span class="md-body-sm">Planning recommendation</span>
                <textarea class="md-input" name="recommended_option" rows="4" placeholder="Preferred planning option and why. This remains advisory until authorised decision.">{{ old('recommended_option', $assessment->recommended_option) }}</textarea>
            </label>
        </section>

        <section class="md-card" style="padding:20px;margin-bottom:16px">
            <h2 class="md-title-md">4. Impact and risk review</h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:14px;margin-top:14px">
                <label>
                    <span class="md-body-sm">Workforce impact</span>
                    <textarea class="md-input" name="workforce_impact" rows="4">{{ old('workforce_impact', $assessment->workforce_impact) }}</textarea>
                </label>
                <label>
                    <span class="md-body-sm">Financial / resource impact</span>
                    <textarea class="md-input" name="financial_impact" rows="4">{{ old('financial_impact', $assessment->financial_impact) }}</textarea>
                </label>
                <label>
                    <span class="md-body-sm">Service-delivery impact</span>
                    <textarea class="md-input" name="service_delivery_impact" rows="4">{{ old('service_delivery_impact', $assessment->service_delivery_impact) }}</textarea>
                </label>
                <label>
                    <span class="md-body-sm">Equity / access considerations</span>
                    <textarea class="md-input" name="equity_and_access_considerations" rows="4">{{ old('equity_and_access_considerations', $assessment->equity_and_access_considerations) }}</textarea>
                </label>
            </div>
            <label style="display:block;margin-top:14px">
                <span class="md-body-sm">Risks and mitigations</span>
                <textarea class="md-input" name="risks_and_mitigations" rows="5" placeholder="Operational, patient/service, workforce, financial, legal/governance, information-quality and implementation risks.">{{ old('risks_and_mitigations', $assessment->risks_and_mitigations) }}</textarea>
            </label>
        </section>

        <section class="md-card" style="padding:20px;margin-bottom:16px;border-left:5px solid var(--md-primary)">
            <h2 class="md-title-md">5. Planning safeguard confirmations</h2>
            <p class="md-body-sm">All confirmations are required before saving this governed planning worksheet.</p>
            @foreach ([
                'support_only_acknowledged' => 'I understand this is a decision-support record only and does not constitute institutional approval or legal authority.',
                'evidence_verified' => 'I reviewed the available source evidence and identified material limitations or uncertainty.',
                'alternatives_considered' => 'I considered reasonable alternatives rather than treating one system/model output as the only option.',
                'risks_considered' => 'I considered relevant workforce, financial, service-delivery, equity/access and implementation risks.',
                'minimum_necessary_confirmed' => 'I used only the minimum information necessary for this planning purpose and avoided unnecessary personal data.',
            ] as $field => $label)
                <label style="display:flex;gap:10px;align-items:flex-start;margin-top:10px">
                    <input type="checkbox" name="{{ $field }}" value="1" required @checked(old($field, $assessment->{$field}))>
                    <span>{{ $label }}</span>
                </label>
            @endforeach
        </section>

        <div style="display:flex;gap:10px;justify-content:flex-end">
            <a class="md-btn" href="{{ $assessment->exists ? route('planning.assessments.show', $assessment) : route('planning.assessments.index') }}">Cancel</a>
            <button class="md-btn md-btn--primary" type="submit">Save planning assessment</button>
        </div>
    </form>
@endsection
