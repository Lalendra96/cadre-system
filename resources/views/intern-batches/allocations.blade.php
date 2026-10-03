@extends('layouts.app')
@section('title', 'Capacity — ' . $batch->name)
@section('content')

    <style>
        .allocation-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }

        .allocation-summary-card {
            padding: 16px;
            border: 1px solid var(--md-outline-variant);
            border-radius: 12px;
            background: var(--md-surface-container-lowest, var(--md-surface));
        }

        .allocation-number {
            font-size: 22px;
            font-weight: 700;
            margin-top: 4px;
        }

        .allocation-subtle {
            color: var(--md-on-surface-variant);
            font-size: 12px;
        }
    </style>

    <div
        style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:10px;">
            <a href="{{ route('intern-batches.index') }}" class="md-btn md-btn--icon">&#8592;</a>
            <div>
                <h2 class="md-headline-sm">{{ $batch->name }} — Rotation Capacity & Allocation</h2>
                <p class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:4px;">
                    {{ $batch->start_date?->format('d M Y') ?? 'No start date' }} –
                    {{ $batch->end_date?->format('d M Y') ?? 'No end date' }}
                    · Responsible officer: {{ $batch->assignedSubjectOfficer?->name ?? 'Unassigned' }}
                </p>
            </div>
        </div>

        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
            <a href="{{ route('intern-batches.analysis') }}" class="md-btn md-btn--outlined">Allocation Analysis</a>
            @unless ($canEdit)
                <span class="md-badge md-badge--neutral">Read-only oversight</span>
            @endunless
        </div>
    </div>

    @if (session('success'))
        <div
            style="background:var(--md-success-container,#1b3a2d);color:var(--md-on-success-container,#9ef0b3);padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
            ✓ {{ session('success') }}
        </div>
    @endif

    @if (!$canEdit)
        <div class="md-card"
            style="padding:14px;margin-bottom:16px;background:var(--md-secondary-container);color:var(--md-on-secondary-container);">
            Only the Subject Officer assigned to this active batch can change capacity. You may review current capacity and
            allocation figures.
        </div>
    @endif

    <div class="allocation-summary-grid">
        <div class="allocation-summary-card">
            <div class="allocation-subtle">Active interns in this batch</div>
            <div class="allocation-number">{{ $allocationSummary['active_interns'] }}</div>
        </div>
        <div class="allocation-summary-card">
            <div class="allocation-subtle">1st Appointment allocated</div>
            <div class="allocation-number">{{ $allocationSummary['first_assigned'] }} /
                {{ $allocationSummary['active_interns'] }}</div>
            <div class="allocation-subtle">{{ $allocationSummary['first_remaining_interns'] }} intern(s) still require a
                1st Appointment allocation.</div>
        </div>
        <div class="allocation-summary-card">
            <div class="allocation-subtle">2nd Appointment allocated</div>
            <div class="allocation-number">{{ $allocationSummary['second_assigned'] }} /
                {{ $allocationSummary['active_interns'] }}</div>
            <div class="allocation-subtle">{{ $allocationSummary['second_remaining_interns'] }} intern(s) still require a
                2nd Appointment allocation.</div>
        </div>
    </div>

    <form method="POST" action="{{ route('intern-batches.allocations.update', $batch) }}"
        class="md-card md-card--elevated">
        @csrf
        @method('PUT')

        <div class="md-card__body">
            <h3 class="md-title-md">Rotation Unit-wise Breakdown</h3>
            <p class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:4px;">
                Capacity is the approved number of slots. Allocated is the number of active interns currently assigned.
                Remaining slots are calculated from those two values.
            </p>
        </div>

        <div style="overflow-x:auto;">
            <table class="md-table">
                <thead>
                    <tr>
                        <th rowspan="2">Rotation Unit</th>
                        <th colspan="3" style="text-align:center;">1st Appointment</th>
                        <th colspan="3" style="text-align:center;">2nd Appointment</th>
                    </tr>
                    <tr>
                        <th style="text-align:center;">Capacity</th>
                        <th style="text-align:center;">Allocated</th>
                        <th style="text-align:center;">Remaining Slots</th>
                        <th style="text-align:center;">Capacity</th>
                        <th style="text-align:center;">Allocated</th>
                        <th style="text-align:center;">Remaining Slots</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($allocationBreakdown as $row)
                        <tr>
                            <td class="md-label-md">{{ $row['unit']->name }}</td>
                            <td style="text-align:center;">
                                @if ($canEdit)
                                    <input type="number" min="0" max="50" class="md-field__input"
                                        style="width:80px;text-align:center;" name="capacity[{{ $row['unit']->id }}][1]"
                                        value="{{ old('capacity.' . $row['unit']->id . '.1', $row['first_capacity']) }}">
                                @else
                                    {{ $row['first_capacity'] }}
                                @endif
                            </td>
                            <td style="text-align:center;">{{ $row['first_assigned'] }}</td>
                            <td style="text-align:center;">{{ $row['first_remaining_slots'] }}</td>
                            <td style="text-align:center;">
                                @if ($canEdit)
                                    <input type="number" min="0" max="50" class="md-field__input"
                                        style="width:80px;text-align:center;" name="capacity[{{ $row['unit']->id }}][2]"
                                        value="{{ old('capacity.' . $row['unit']->id . '.2', $row['second_capacity']) }}">
                                @else
                                    {{ $row['second_capacity'] }}
                                @endif
                            </td>
                            <td style="text-align:center;">{{ $row['second_assigned'] }}</td>
                            <td style="text-align:center;">{{ $row['second_remaining_slots'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="md-card__footer" style="display:flex;justify-content:flex-end;gap:10px;">
            <a href="{{ route('intern-batches.index') }}" class="md-btn md-btn--outlined">Back</a>
            @if ($canEdit)
                <button type="submit" class="md-btn md-btn--filled">Save Capacity</button>
            @endif
        </div>
    </form>

    <div class="md-card" style="margin-top:16px;padding:16px;">
        <strong>How the counts are calculated</strong>
        <p class="md-body-sm" style="margin:6px 0 0;color:var(--md-on-surface-variant);">
            Remaining intern counts use active interns in this batch only. Disabled intern records are excluded. Closed
            batches are excluded from the institution-wide Allocation Analysis screen. No placement or allocation is
            inferred automatically.
        </p>
    </div>

    <div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap;">
        <a href="{{ route('intern-batches.interns.index', $batch) }}" class="md-btn md-btn--outlined">Next: Intern List
            →</a>
        <a href="{{ route('intern-batches.rho-placements.index', $batch) }}" class="md-btn md-btn--text">RHO Placements</a>
    </div>
@endsection
