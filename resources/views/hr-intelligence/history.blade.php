@extends('layouts.app')

@section('title', 'Employee HR Ownership History')

@section('content')
    <div class="page-header">
        <div>
            <div class="page-eyebrow">Employee responsibility history</div>
            <h1 class="page-title">{{ $employee->display_name }}</h1>
            <p class="page-subtitle">Employee record #{{ $employee->id }} · Ownership observations and work acceptance</p>
        </div>
        <a class="md-btn md-btn--outlined" href="{{ route('hr-intelligence.index') }}">Back to HR intelligence</a>
    </div>
    <div class="md-card" style="padding:20px;margin-bottom:20px;">
        <p>Observation times show when the application detected a state, not an invented historical effective date. Position
            and officer names below are their current display names; the underlying identities remain recorded. Use Position
            Assignments & History for the original dated assignment and handover records.</p>
        <a class="md-btn md-btn--text" href="{{ route('hr-responsibilities.index') }}">Position assignments & history</a>
    </div>
    <section class="md-card" style="padding:20px;margin-bottom:20px;">
        <h2 class="md-title-md">Ownership timeline</h2>
        @forelse ($events as $event)
            <article style="padding:16px 0;border-bottom:1px solid var(--md-outline-variant);">
                <h3 class="md-title-sm">{{ ucwords(str_replace('_', ' ', $event->kind)) }} · {{ $event->observed_at }}</h3>
                @foreach (['previous_state' => 'Before', 'current_state' => 'After'] as $field => $label)
                    @if ($event->{$field} === null)
                        <p><strong>{{ $label }}:</strong> No prior recorded observation.</p>
                    @else
                        <p><strong>{{ $label }}:</strong>
                            {{ $positions->get($event->{$field}['position_id'], 'No position') }} ·
                            {{ $event->{$field}['active'] ? 'Employee record active' : 'Employee record inactive' }} ·
                            Officers:
                            {{ collect($event->{$field}['owner_ids'])->map(fn($id) => $names->get($id, 'Former officer #' . $id))->join(', ') ?:'Unassigned' }}
                        </p>
                    @endif
                @endforeach
            </article>
        @empty
            <p>No ownership observations yet. Ask a manager to refresh HR intelligence.</p>
        @endforelse
        {{ $events->withQueryString()->links() }}
    </section>
    <section class="md-card" style="padding:20px;">
        <h2 class="md-title-md">Work reassignment decisions</h2>
        @forelse ($cases as $case)
            <article style="padding:16px 0;border-bottom:1px solid var(--md-outline-variant);">
                <p><strong>Case #{{ $case->id }} — {{ ucwords(str_replace('_', ' ', $case->status)) }}</strong></p>
                <p>Proposed officer: {{ $case->proposedOfficer?->name ?? 'Not selected' }}.</p>
                @if ($case->accepted_at)
                    <p>Accepted by {{ $case->acceptedOfficer?->name }} at {{ $case->accepted_at->format('d M Y H:i') }}.
                    </p>
                    <p>Transferred {{ count($case->work_transfer['data_quality_issues'] ?? []) }} data-quality issues,
                        {{ count($case->work_transfer['employee_increments'] ?? []) }} increments and
                        {{ count($case->work_transfer['retirement_projects'] ?? []) }} retirement projects.</p>
                @endif
                <p style="white-space:pre-wrap;">{{ $case->review_note }}</p>
            </article>
        @empty
            <p>No work reassignment decisions recorded.</p>
        @endforelse
        {{ $cases->withQueryString()->links() }}
    </section>
@endsection
