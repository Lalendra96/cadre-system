@extends('layouts.app')

@section('title', 'Hospital Planning Decision Support')

@section('content')
    <div class="page-header">
        <div>
            <div class="page-eyebrow">Planning Officer · governed decision support</div>
            <h1 class="page-title">Hospital Planning Decision Support</h1>
            <p class="page-subtitle">
                Record planning questions, compare options, document evidence and refer recommendations for authorised human decision.
            </p>
        </div>
        <a href="{{ route('planning.assessments.create') }}" class="md-btn md-btn--primary">+ New planning assessment</a>
    </div>

    @include('partials.governance-legal-safeguard', [
        'title' => 'Support system only — this screen does not approve hospital planning decisions',
        'purpose' => 'Use these assessments to structure planning evidence and recommendations. Final approval, procurement, staffing, financial commitment, service reconfiguration or policy action must follow the applicable authorised institutional process.',
        'items' => [
            'Confirm the planning question and the official purpose before analysing options.',
            'Use current, source-verifiable data and record the data “as at” date.',
            'Compare reasonable alternatives; do not present a model output as the only permissible option.',
            'Document workforce, financial, service-delivery, equity/access and implementation risks where relevant.',
            'A referred recommendation remains advisory until the competent authority records a decision through the applicable official workflow.',
        ],
    ])

    <section class="md-card" style="padding:18px;margin-bottom:16px">
        <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:end">
            <label style="min-width:210px">
                <span class="md-body-sm">Planning area</span>
                <select name="planning_area" class="md-input">
                    <option value="">All areas</option>
                    @foreach ($areas as $value => $label)
                        <option value="{{ $value }}" @selected(request('planning_area') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label style="min-width:210px">
                <span class="md-body-sm">Status</span>
                <select name="status" class="md-input">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <button class="md-btn md-btn--primary" type="submit">Apply filters</button>
            <a class="md-btn" href="{{ route('planning.assessments.index') }}">Clear</a>
        </form>
    </section>

    <section class="md-card" style="overflow:hidden">
        <div class="md-table-wrap">
            <table class="md-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Planning question</th>
                        <th>Area</th>
                        <th>Status</th>
                        <th>Prepared by</th>
                        <th>Updated</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($assessments as $assessment)
                        <tr>
                            <td><strong>{{ $assessment->reference_no }}</strong></td>
                            <td>
                                <strong>{{ $assessment->title }}</strong>
                                <div class="md-body-sm">{{ \Illuminate\Support\Str::limit($assessment->planning_question, 90) }}</div>
                            </td>
                            <td>{{ $areas[$assessment->planning_area] ?? $assessment->planning_area }}</td>
                            <td>
                                <span class="md-badge md-badge--info">
                                    {{ $statuses[$assessment->status] ?? $assessment->status }}
                                </span>
                            </td>
                            <td>{{ $assessment->preparer?->name ?? '—' }}</td>
                            <td>{{ $assessment->updated_at?->format('d M Y H:i') }}</td>
                            <td>
                                <a class="md-btn md-btn--sm" href="{{ route('planning.assessments.show', $assessment) }}">Open</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="md-table__empty">No planning assessments match the selected filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:14px">{{ $assessments->links() }}</div>
    </section>
@endsection
