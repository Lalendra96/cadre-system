@extends('layouts.app')
@section('title', 'Intern Allocation Analysis')
@section('content')

    <style>
        .analysis-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }

        .analysis-card {
            padding: 16px;
            border: 1px solid var(--md-outline-variant);
            border-radius: 12px;
            background: var(--md-surface-container-lowest, var(--md-surface));
        }

        .analysis-value {
            font-size: 24px;
            font-weight: 700;
            margin-top: 4px;
        }

        .analysis-note {
            color: var(--md-on-surface-variant);
            font-size: 12px;
        }

        .analysis-warning {
            border: 1px solid var(--md-error);
            border-radius: 10px;
            padding: 10px 12px;
            margin-top: 8px;
            color: var(--md-error);
            background: color-mix(in srgb, var(--md-error) 6%, transparent);
        }
    </style>

    <div
        style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:18px;flex-wrap:wrap;">
        <div style="display:flex;gap:10px;align-items:flex-start;">
            <a href="{{ route('intern-batches.index') }}" class="md-btn md-btn--icon">&#8592;</a>
            <div>
                <h2 class="md-headline-sm">Intern Medical Officer Allocation Analysis</h2>
                <p class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:4px;">
                    Current capacity, allocation and remaining-intern analysis. Closed batches are excluded from every
                    current total.
                </p>
            </div>
        </div>
    </div>

    <div class="analysis-grid">
        <div class="analysis-card">
            <div class="analysis-note">Active batches included</div>
            <div class="analysis-value">{{ $summary['active_batches'] }}</div>
        </div>
        <div class="analysis-card">
            <div class="analysis-note">Active interns</div>
            <div class="analysis-value">{{ $summary['active_interns'] }}</div>
        </div>
        <div class="analysis-card">
            <div class="analysis-note">1st Appointment allocated</div>
            <div class="analysis-value">{{ $summary['first_assigned'] }} / {{ $summary['active_interns'] }}</div>
            <div class="analysis-note">{{ $summary['first_remaining_interns'] }} intern(s) remaining ·
                {{ $summary['first_utilisation_pct'] }}% of configured slots used</div>
        </div>
        <div class="analysis-card">
            <div class="analysis-note">2nd Appointment allocated</div>
            <div class="analysis-value">{{ $summary['second_assigned'] }} / {{ $summary['active_interns'] }}</div>
            <div class="analysis-note">{{ $summary['second_remaining_interns'] }} intern(s) remaining ·
                {{ $summary['second_utilisation_pct'] }}% of configured slots used</div>
        </div>
        <div class="analysis-card">
            <div class="analysis-note">Fully allocated interns</div>
            <div class="analysis-value">{{ $summary['fully_assigned_interns'] }}</div>
            <div class="analysis-note">Both 1st and 2nd Appointment recorded</div>
        </div>
        <div class="analysis-card">
            <div class="analysis-note">Not fully allocated</div>
            <div class="analysis-value">{{ $summary['not_fully_assigned_interns'] }}</div>
            <div class="analysis-note">At least one appointment still missing</div>
        </div>
    </div>

    @if ($alerts->isNotEmpty())
        <div class="md-card md-card--elevated" style="padding:16px;margin-bottom:16px;">
            <h3 class="md-title-md">Allocation Data Quality Alerts</h3>
            <p class="analysis-note" style="margin-top:4px;">These alerts highlight recorded inconsistencies. They do not
                make allocation decisions.</p>
            @foreach ($alerts as $alert)
                <div class="analysis-warning">{{ $alert }}</div>
            @endforeach
        </div>
    @endif

    <div class="md-card md-card--elevated" style="margin-bottom:16px;">
        <div class="md-card__body">
            <h3 class="md-title-md">Rotation Unit-wise Breakdown</h3>
            <p class="analysis-note" style="margin-top:4px;">
                Aggregated across the active batches visible to your role. Closed batches are not counted.
            </p>
        </div>
        <div style="overflow-x:auto;">
            <table class="md-table">
                <thead>
                    <tr>
                        <th rowspan="2">Rotation Unit</th>
                        <th colspan="4" style="text-align:center;">1st Appointment</th>
                        <th colspan="4" style="text-align:center;">2nd Appointment</th>
                    </tr>
                    <tr>
                        <th style="text-align:right;">Capacity</th>
                        <th style="text-align:right;">Allocated</th>
                        <th style="text-align:right;">Remaining Slots</th>
                        <th style="text-align:right;">Over Capacity</th>
                        <th style="text-align:right;">Capacity</th>
                        <th style="text-align:right;">Allocated</th>
                        <th style="text-align:right;">Remaining Slots</th>
                        <th style="text-align:right;">Over Capacity</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rotation_rows as $row)
                        <tr>
                            <td class="md-label-md">{{ $row['rotation_unit'] }}</td>
                            <td style="text-align:right;">{{ $row['first_capacity'] }}</td>
                            <td style="text-align:right;">{{ $row['first_assigned'] }}</td>
                            <td style="text-align:right;">{{ $row['first_remaining_slots'] }}</td>
                            <td style="text-align:right;">{{ $row['first_overallocated'] }}</td>
                            <td style="text-align:right;">{{ $row['second_capacity'] }}</td>
                            <td style="text-align:right;">{{ $row['second_assigned'] }}</td>
                            <td style="text-align:right;">{{ $row['second_remaining_slots'] }}</td>
                            <td style="text-align:right;">{{ $row['second_overallocated'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="md-card md-card--elevated" style="margin-bottom:16px;">
        <div class="md-card__body">
            <h3 class="md-title-md">Active Batch Breakdown</h3>
            <p class="analysis-note" style="margin-top:4px;">
                Remaining Intern Count is calculated separately for the 1st and 2nd Appointment from active intern records
                in each active batch.
            </p>
        </div>
        <div style="overflow-x:auto;">
            <table class="md-table">
                <thead>
                    <tr>
                        <th>Batch</th>
                        <th>Responsible Officer</th>
                        <th style="text-align:right;">Interns</th>
                        <th style="text-align:right;">1st Capacity</th>
                        <th style="text-align:right;">1st Allocated</th>
                        <th style="text-align:right;">1st Remaining Interns</th>
                        <th style="text-align:right;">2nd Capacity</th>
                        <th style="text-align:right;">2nd Allocated</th>
                        <th style="text-align:right;">2nd Remaining Interns</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batch_rows as $row)
                        <tr>
                            <td>
                                <div class="md-label-md">{{ $row['batch']->name }}</div>
                                <div class="analysis-note">
                                    {{ $row['batch']->start_date?->format('d M Y') ?? 'No start date' }} –
                                    {{ $row['batch']->end_date?->format('d M Y') ?? 'No end date' }}
                                </div>
                            </td>
                            <td>{{ $row['batch']->assignedSubjectOfficer?->name ?? 'Unassigned' }}</td>
                            <td style="text-align:right;">{{ $row['interns'] }}</td>
                            <td style="text-align:right;">{{ $row['first_capacity'] }}</td>
                            <td style="text-align:right;">{{ $row['first_assigned'] }}</td>
                            <td style="text-align:right;">{{ $row['first_remaining_interns'] }}</td>
                            <td style="text-align:right;">{{ $row['second_capacity'] }}</td>
                            <td style="text-align:right;">{{ $row['second_assigned'] }}</td>
                            <td style="text-align:right;">{{ $row['second_remaining_interns'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="md-table__empty">No active batches are available for the current
                                analysis scope.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="md-card" style="padding:16px;">
        <strong>Governance & calculation rules</strong>
        <p class="md-body-sm" style="margin:6px 0 0;color:var(--md-on-surface-variant);">
            This page is an operational summary derived from recorded capacities and assignments. Closed batches and
            disabled intern records are excluded. Subject Officers see only batches formally assigned to them; authorised
            oversight roles may see institution-level aggregates. No automated allocation, RHO placement recommendation,
            eligibility decision or personnel assessment is produced by this analysis.
        </p>
    </div>
@endsection
