@extends('layouts.app')

@section('title', 'Incident & Correction Register')

@section('content')
<div class="page-header">
    <div>
        <div class="page-eyebrow">
            Governance & correction history
        </div>

        <h1 class="page-title">
            ⚠ Incident & Correction Register
        </h1>

        <p class="page-subtitle">
            Report incorrect data, calculations, reports, workflow behaviour,
            integrations, privacy/security concerns or other operational errors.
        </p>
    </div>

    <a
        class="md-btn md-btn--filled"
        href="{{ route('incidents.create') }}"
    >
        + Report Incident / Error
    </a>
</div>

<div
    style="
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 16px;
    "
>
    <div class="workforce-panel">
        <div class="md-body-sm">Open / Active</div>
        <div class="md-headline-sm">{{ $kpis['open'] }}</div>
    </div>

    <div class="workforce-panel">
        <div class="md-body-sm">Critical Open</div>
        <div class="md-headline-sm">{{ $kpis['critical'] }}</div>
    </div>

    <div class="workforce-panel">
        <div class="md-body-sm">Under Review / Correction</div>
        <div class="md-headline-sm">{{ $kpis['investigating'] }}</div>
    </div>

    <div class="workforce-panel">
        <div class="md-body-sm">Resolved — Pending Closure</div>
        <div class="md-headline-sm">{{ $kpis['resolved'] }}</div>
    </div>
</div>

<div
    class="workforce-panel"
    style="
        border-left: 4px solid var(--md-warning);
        margin-bottom: 16px;
    "
>
    <strong>Operational governance record</strong>

    <div class="md-body-sm">
        Use this register to preserve what was reported, the assessed impact,
        root cause, corrective action, before/after correction evidence,
        verification, resolution and closure. This register does not replace
        any separate mandatory cyber-security, data-protection, Ministry,
        institutional or statutory incident-notification process.
    </div>
</div>

<div class="workforce-panel">
    <form
        method="GET"
        style="
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        "
    >
        <select
            class="md-select"
            name="status"
        >
            <option value="">All statuses</option>

            @foreach (\App\Models\IncidentReport::STATUSES as $value => $label)
                <option
                    value="{{ $value }}"
                    @selected(request('status') === $value)
                >
                    {{ $label }}
                </option>
            @endforeach
        </select>

        <select
            class="md-select"
            name="severity"
        >
            <option value="">All severities</option>

            @foreach (\App\Models\IncidentReport::SEVERITIES as $value => $label)
                <option
                    value="{{ $value }}"
                    @selected(request('severity') === $value)
                >
                    {{ $label }}
                </option>
            @endforeach
        </select>

        <select
            class="md-select"
            name="category"
        >
            <option value="">All categories</option>

            @foreach (\App\Models\IncidentReport::CATEGORIES as $value => $label)
                <option
                    value="{{ $value }}"
                    @selected(request('category') === $value)
                >
                    {{ $label }}
                </option>
            @endforeach
        </select>

        <button class="md-btn md-btn--outlined">
            Apply Filters
        </button>
    </form>

    <div style="overflow-x: auto;">
        <table class="md-table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Reported</th>
                    <th>Incident</th>
                    <th>Severity</th>
                    <th>Status</th>
                    <th>Assigned</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($incidents as $incident)
                    <tr>
                        <td>
                            <strong>{{ $incident->reference_no }}</strong>
                        </td>

                        <td>
                            {{ $incident->reported_at?->format('d M Y H:i') }}

                            <div class="md-body-sm">
                                {{ $incident->reporter?->name }}
                            </div>
                        </td>

                        <td>
                            <strong>{{ $incident->title }}</strong>

                            <div class="md-body-sm">
                                {{ $incident->category_label }}
                            </div>
                        </td>

                        <td>
                            <span
                                class="md-chip"
                                style="{{
                                    $incident->severity === 'critical'
                                        ? 'color: var(--md-error); font-weight: 700;'
                                        : ''
                                }}"
                            >
                                {{ $incident->severity_label }}
                            </span>
                        </td>

                        <td>
                            {{ $incident->status_label }}
                        </td>

                        <td>
                            {{ $incident->assignee?->name ?? 'Unassigned' }}
                        </td>

                        <td>
                            <a
                                class="md-btn md-btn--text"
                                href="{{ route('incidents.show', $incident) }}"
                            >
                                Open →
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td
                            colspan="7"
                            class="md-table__empty"
                        >
                            No incidents found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="md-card__footer">
        {{ $incidents->links('vendor.pagination.material') }}
    </div>
</div>
@endsection
