@extends('layouts.app')

@section('title', 'Service History')

@section('content')
<div class="page-header">
    <div>
        <a href="{{ route('employees.show', $employee) }}">
            ← Employee 360
        </a>

        <h1 class="page-title">
            📂 Service History — {{ $employee->display_name }}
        </h1>

        <p class="page-subtitle">
            Record each institution/posting without losing continuous service or
            grade history.
        </p>
    </div>

    <a
        class="md-btn md-btn--filled"
        href="{{ route('employee-service-periods.create', $employee) }}"
    >
        + Add Service Period
    </a>
</div>

<div class="workforce-panel">
    <div style="display: grid; gap: 0;">
        @forelse ($periods as $period)
            <div
                style="
                    display: grid;
                    grid-template-columns: 150px 18px 1fr auto;
                    gap: 12px;
                    min-height: 90px;
                "
            >
                <div>
                    <strong>
                        {{ $period->start_date->format('d M Y') }}
                    </strong>

                    <div class="md-body-sm">
                        {{ $period->end_date?->format('d M Y') ?? 'Present' }}
                    </div>
                </div>

                <div style="position: relative;">
                    <span
                        style="
                            display: block;
                            width: 10px;
                            height: 10px;
                            border-radius: 50%;
                            background: var(--md-primary);
                            margin-top: 5px;
                        "
                    ></span>

                    <span
                        style="
                            position: absolute;
                            left: 4px;
                            top: 18px;
                            bottom: 0;
                            border-left: 2px solid var(--md-outline-variant);
                        "
                    ></span>
                </div>

                <div>
                    <strong>{{ $period->institution_name }}</strong>

                    <div>
                        {{ $period->display_position }}

                        @if ($period->display_grade)
                            · {{ $period->display_grade }}
                        @endif
                    </div>

                    <div class="md-body-sm">
                        {{ $period->service_name ?: 'Service not specified' }}
                        ·
                        {{
                            $period->movement_type
                                ? (
                                    \App\Models\EmployeeServicePeriod::MOVEMENT_TYPES[$period->movement_type]
                                    ?? ucwords(str_replace('_', ' ', $period->movement_type))
                                )
                                : 'Posting'
                        }}
                    </div>

                    <div class="md-body-sm">
                        Evidence:
                        {{ $period->sourceDocument?->title ?? 'No document linked' }}
                        ·
                        {{ $period->verification_label }}
                    </div>
                </div>

                <a
                    href="{{ route('employee-service-periods.edit', [$employee, $period]) }}"
                >
                    Edit
                </a>
            </div>
        @empty
            <div class="empty-state">
                No structured service history yet. Add previous institutions and
                the current hospital posting.
            </div>
        @endforelse
    </div>
</div>
@endsection
