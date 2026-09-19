@extends('layouts.app')

@section('title', 'Administrative Decision Queue')

@section('content')
<div class="page-header">
    <div>
        <div class="page-eyebrow">
            Human approval
        </div>

        <h1 class="page-title">
            ✅ Administrative Decision Queue
        </h1>

        <p class="page-subtitle">
            Consequential HR actions remain pending until an independent authorised
            officer verifies the source record and explicitly approves them.
        </p>
    </div>
</div>

<div
    class="workforce-panel"
    style="
        border-left: 4px solid var(--md-primary);
        margin-bottom: 16px;
    "
>
    <strong>Human decision required</strong>

    <div class="md-body-sm">
        System calculations, alerts and submitted records are decision-support
        information only. Approval must be based on the applicable official
        authority and underlying employee record.
    </div>
</div>

<div class="workforce-panel">
    <table class="md-table">
        <thead>
            <tr>
                <th>Requested</th>
                <th>Type</th>
                <th>Employee</th>
                <th>Summary</th>
                <th>Requested By</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($decisions as $decision)
                <tr>
                    <td>{{ $decision->requested_at?->format('d M Y H:i') }}</td>
                    <td>
                        {{
                            \App\Models\AdministrativeDecision::TYPES[
                                $decision->decision_type
                            ]
                            ?? $decision->decision_type
                        }}
                    </td>
                    <td>{{ $decision->employee?->display_name ?? '—' }}</td>
                    <td>{{ $decision->summary }}</td>
                    <td>{{ $decision->requester?->name ?? '—' }}</td>
                    <td>{{ ucfirst($decision->status) }}</td>
                    <td>
                        <a
                            class="md-btn md-btn--text"
                            href="{{ route('administrative-decisions.show', $decision) }}"
                        >
                            Review →
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="md-table__empty">
                        No decision requests found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="md-card__footer">
        {{ $decisions->links('vendor.pagination.material') }}
    </div>
</div>
@endsection
