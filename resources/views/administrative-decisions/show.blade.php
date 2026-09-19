@extends('layouts.app')

@section('title', 'Administrative Decision Review')

@section('content')
@php
    $user = auth()->user();
    $canDecide = $decision->status === 'pending'
        && $decision->canBeDecidedBy($user);
@endphp

<div class="page-header">
    <div>
        <a href="{{ route('administrative-decisions.index') }}">
            ← Decision Queue
        </a>

        <h1 class="page-title">
            ✅ Administrative Decision Review
        </h1>

        <p class="page-subtitle">
            {{ $decision->summary }}
        </p>
    </div>
</div>

@if (session('success'))
    <div
        class="workforce-panel"
        style="
            border-left: 4px solid var(--md-success);
            margin-bottom: 16px;
        "
    >
        {{ session('success') }}
    </div>
@endif

<div class="workforce-panel" style="margin-bottom: 16px;">
    <div class="panel-title">
        Request
    </div>

    <div class="md-form-row" style="margin-top: 12px;">
        <div>
            <strong>Employee</strong>
            <div>{{ $decision->employee?->display_name ?? '—' }}</div>
        </div>

        <div>
            <strong>Decision Type</strong>
            <div>
                {{
                    \App\Models\AdministrativeDecision::TYPES[
                        $decision->decision_type
                    ]
                    ?? $decision->decision_type
                }}
            </div>
        </div>

        <div>
            <strong>Requested By</strong>
            <div>{{ $decision->requester?->name ?? '—' }}</div>
        </div>

        <div>
            <strong>Requested At</strong>
            <div>{{ $decision->requested_at?->format('d M Y H:i') }}</div>
        </div>

        <div>
            <strong>Recorded Source / Reference</strong>
            <div>{{ $decision->source_reference ?: 'Not supplied' }}</div>
        </div>

        <div>
            <strong>Business Rule</strong>
            <div>{{ $decision->businessRule?->name ?? 'Not linked' }}</div>
        </div>
    </div>
</div>

<div class="workforce-panel" style="margin-bottom: 16px;">
    <div class="panel-title">
        Proposed Data
    </div>

    <table class="md-table" style="margin-top: 12px;">
        <tbody>
            @foreach ($decision->payload as $field => $value)
                <tr>
                    <th style="width: 35%;">
                        {{ ucwords(str_replace('_', ' ', $field)) }}
                    </th>
                    <td>
                        {{ is_array($value) ? json_encode($value) : ($value ?? '—') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if ($canDecide)
    <div
        class="workforce-panel"
        style="
            border-left: 4px solid var(--md-warning);
            margin-bottom: 16px;
        "
    >
        <div class="panel-title">
            Independent Human Decision
        </div>

        <p class="md-body-sm">
            Check the underlying service record and applicable Act, regulation,
            Establishments Code provision, circular, service minute, PSC decision,
            Ministry instruction or other authoritative document before deciding.
        </p>

        <form
            method="POST"
            action="{{ route('administrative-decisions.approve', $decision) }}"
            style="margin-top: 14px;"
        >
            @csrf

            <label
                style="
                    display: flex;
                    gap: 8px;
                    align-items: flex-start;
                    margin-bottom: 12px;
                "
            >
                <input
                    type="checkbox"
                    name="confirm_authority_checked"
                    value="1"
                    required
                >

                <span>
                    I confirm that I reviewed the underlying record and the
                    applicable official authority. I am making this administrative
                    decision as an authorised human officer, not relying solely on
                    a system calculation, alert or AI output.
                </span>
            </label>

            <div class="md-form-group">
                <label class="md-label">
                    Approval reason / authority checked *
                </label>

                <textarea
                    class="md-input"
                    name="decision_reason"
                    rows="3"
                    minlength="10"
                    maxlength="1000"
                    required
                ></textarea>
            </div>

            <button class="md-btn md-btn--filled">
                Approve & Apply Record
            </button>
        </form>

        <form
            method="POST"
            action="{{ route('administrative-decisions.reject', $decision) }}"
            style="margin-top: 18px;"
        >
            @csrf

            <div class="md-form-group">
                <label class="md-label">
                    Rejection / return reason *
                </label>

                <textarea
                    class="md-input"
                    name="decision_reason"
                    rows="3"
                    minlength="10"
                    maxlength="1000"
                    required
                ></textarea>
            </div>

            <button class="md-btn md-btn--outlined">
                Reject / Return Without Applying
            </button>
        </form>
    </div>
@endif

<div class="workforce-panel">
    <div class="panel-title">
        Decision History
    </div>

    <table class="md-table" style="margin-top: 12px;">
        <thead>
            <tr>
                <th>Date</th>
                <th>Event</th>
                <th>Actor</th>
                <th>Reason</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($decision->events as $event)
                <tr>
                    <td>{{ $event->created_at?->format('d M Y H:i') }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $event->event)) }}</td>
                    <td>{{ $event->actor?->name ?? 'System' }}</td>
                    <td>{{ $event->reason ?: '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">No decision history.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
