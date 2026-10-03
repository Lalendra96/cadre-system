@extends('layouts.app')
@section('title', 'Intern Allocation')
@section('content')

    <style>
        .intern-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }

        .intern-summary-card {
            padding: 16px;
            border: 1px solid var(--md-outline-variant);
            border-radius: 12px;
            background: var(--md-surface-container-lowest, var(--md-surface));
        }

        .intern-career-shell {
            border: 1px solid var(--md-outline-variant);
            border-radius: 16px;
            background: var(--md-surface);
            overflow: hidden;
            margin-bottom: 16px;
            box-shadow: 0 6px 18px rgba(29, 61, 101, 0.06);
        }

        .intern-career-header {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            align-items: flex-start;
            padding: 18px 20px;
            border-bottom: 1px solid var(--md-outline-variant);
            flex-wrap: wrap;
        }

        .intern-career-header__notice {
            max-width: 390px;
            padding: 10px 12px;
            border-radius: 10px;
            background: var(--md-secondary-container);
            color: var(--md-on-secondary-container);
            font-size: 12px;
        }

        .intern-career-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            padding: 12px 20px;
            border-bottom: 1px solid var(--md-outline-variant);
        }

        .intern-career-summary article {
            padding: 12px 14px;
            border: 1px solid var(--md-outline-variant);
            border-radius: 12px;
            background: var(--md-surface-container-lowest, var(--md-surface));
        }

        .intern-career-summary strong {
            display: block;
            font-size: 22px;
            line-height: 1.2;
        }

        .intern-career-summary span {
            display: block;
            margin-top: 3px;
            color: var(--md-on-surface-variant);
            font-size: 11px;
        }

        .intern-career-legend {
            display: flex;
            gap: 12px 18px;
            align-items: center;
            flex-wrap: wrap;
            padding: 10px 20px;
            border-bottom: 1px solid var(--md-outline-variant);
            font-size: 11px;
            font-weight: 700;
        }

        .intern-career-legend span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .intern-career-legend i {
            width: 10px;
            height: 10px;
            border-radius: 3px;
        }

        .intern-career-legend__ongoing i {
            background: #2ebf82;
        }

        .intern-career-legend__upcoming i {
            background: #1685e5;
        }

        .intern-career-legend__open i {
            background: #e6a800;
        }

        .intern-career-legend__ended i {
            background: #ff9a3c;
        }

        .intern-career-legend__closed i {
            background: #7e91ac;
        }

        .intern-career-scroll {
            overflow-x: auto;
        }

        .intern-career-table {
            width: 100%;
            min-width: 1420px;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 12px;
        }

        .intern-career-table th,
        .intern-career-table td {
            padding: 10px;
            border-right: 1px solid var(--md-outline-variant);
            border-bottom: 1px solid var(--md-outline-variant);
            background: var(--md-surface);
            vertical-align: middle;
        }

        .intern-career-table thead th {
            position: sticky;
            top: 0;
            z-index: 8;
            font-weight: 800;
        }

        .intern-career-sticky {
            position: sticky;
            z-index: 5;
            background: var(--md-surface) !important;
        }

        .intern-career-batch-col {
            left: 0;
            width: 230px;
            min-width: 230px;
        }

        .intern-career-owner-col {
            left: 230px;
            width: 180px;
            min-width: 180px;
        }

        .intern-career-status-col {
            left: 410px;
            width: 110px;
            min-width: 110px;
        }

        .intern-career-next-col {
            left: 520px;
            width: 150px;
            min-width: 150px;
        }

        .intern-career-rho-col {
            left: 670px;
            width: 120px;
            min-width: 120px;
        }

        .intern-career-timeline-col {
            min-width: 720px;
            padding: 0 !important;
        }

        .intern-career-month-grid {
            display: grid;
            grid-template-columns: repeat(var(--intern-month-count), minmax(54px, 1fr));
            min-height: 46px;
        }

        .intern-career-month-grid span {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            border-right: 1px solid var(--md-outline-variant);
            font-size: 10px;
        }

        .intern-career-month-grid small {
            color: var(--md-on-surface-variant);
            font-size: 9px;
        }

        .intern-career-track {
            position: relative;
            height: 70px;
            background-image: linear-gradient(to right, var(--md-outline-variant) 1px, transparent 1px);
            background-size: calc(100% / var(--intern-month-count)) 100%;
        }

        .intern-career-today {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e85151;
            z-index: 3;
        }

        .intern-career-today em {
            position: absolute;
            top: 3px;
            left: 4px;
            padding: 2px 5px;
            border-radius: 6px;
            background: #e85151;
            color: #ffffff;
            font-size: 9px;
            font-style: normal;
        }

        .intern-career-bar {
            position: absolute;
            top: 23px;
            height: 24px;
            border-radius: 999px;
            min-width: 12px;
            padding: 3px 8px;
            color: #ffffff;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
            font-size: 10px;
            font-weight: 800;
            line-height: 18px;
        }

        .intern-career-bar--ongoing {
            background: #2ebf82;
        }

        .intern-career-bar--upcoming {
            background: #1685e5;
        }

        .intern-career-bar--open {
            background: #e6a800;
        }

        .intern-career-bar--ended {
            background: #ff9a3c;
        }

        .intern-career-bar--closed {
            background: #7e91ac;
        }

        .intern-career-rho-progress {
            height: 6px;
            overflow: hidden;
            border-radius: 999px;
            background: var(--md-surface-container-high, #e8edf5);
            margin-top: 5px;
        }

        .intern-career-rho-progress span {
            display: block;
            height: 100%;
            background: var(--md-primary);
            border-radius: inherit;
        }

        @media (max-width: 900px) {
            .intern-career-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .intern-readonly-note {
            padding: 10px 12px;
            border-radius: 10px;
            background: var(--md-secondary-container);
            color: var(--md-on-secondary-container);
            font-size: 12px;
        }
    </style>

    <div
        style="display:flex;justify-content:space-between;align-items:flex-start;gap:14px;margin-bottom:16px;flex-wrap:wrap;">
        <div>
            <h2 class="md-headline-sm">Intern Medical Officer Allocation</h2>
            <p class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:4px;">
                Governed batch ownership, allocation timeline and RHO placement follow-up.
            </p>
        </div>

        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a href="{{ route('intern-batches.analysis') }}" class="md-btn md-btn--outlined">Allocation Analysis</a>
            @if ($canCreate)
                <a href="{{ route('intern-batches.create') }}" class="md-btn md-btn--filled">+ New Batch</a>
            @endif
        </div>
    </div>

    @if (session('success'))
        <div
            style="background:var(--md-success-container,#1b3a2d);color:var(--md-on-success-container,#9ef0b3);padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
            ✓ {{ session('success') }}
        </div>
    @endif

    @if (auth()->user()->isSubjectOfficer())
        <div class="intern-summary-grid">
            <div class="intern-summary-card">
                <div class="md-body-sm" style="color:var(--md-on-surface-variant);">My assigned batches</div>
                <div class="md-headline-sm" style="margin-top:4px;">{{ $myBatches->count() }}</div>
            </div>
            <div class="intern-summary-card">
                <div class="md-body-sm" style="color:var(--md-on-surface-variant);">Ongoing</div>
                <div class="md-headline-sm" style="margin-top:4px;">
                    {{ $myBatches->filter(fn($batch) => $batch->operational_status === 'ongoing')->count() }}
                </div>
            </div>
            <div class="intern-summary-card">
                <div class="md-body-sm" style="color:var(--md-on-surface-variant);">Ended / RHO follow-up</div>
                <div class="md-headline-sm" style="margin-top:4px;">
                    {{ $myBatches->filter(fn($batch) => $batch->operational_status === 'ended')->count() }}
                </div>
            </div>
        </div>
    @endif

    <section class="intern-career-shell" style="--intern-month-count: {{ max(1, $timelineMonths->count()) }};">
        <div class="intern-career-header">
            <div>
                <div class="md-label-sm" style="color:var(--md-primary);text-transform:uppercase;letter-spacing:.06em;">
                    Intern workforce planning & allocation governance
                </div>
                <h3 class="md-headline-sm" style="margin-top:5px;">Batch Timeline</h3>
                <p class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:4px;max-width:760px;">
                    Track recorded batch periods, responsible Subject Officers, RHO follow-up and upcoming milestones.
                    Timeline markers are based only on official batch dates; they do not recommend or predict allocation
                    decisions.
                </p>
            </div>
            <div class="intern-career-header__notice">
                <strong>Governed timeline</strong><br>
                Closed batches remain visible for history and RHO follow-up but are excluded from current allocation
                analysis.
            </div>
        </div>

        <div class="intern-career-summary">
            <article>
                <strong>{{ $timelineSummary['ongoing'] }}</strong>
                <span>Ongoing batches</span>
            </article>
            <article>
                <strong>{{ $timelineSummary['upcoming'] }}</strong>
                <span>Upcoming batches</span>
            </article>
            <article>
                <strong>{{ $timelineSummary['active_interns'] }}</strong>
                <span>Interns in active batches</span>
            </article>
            <article>
                <strong>{{ $timelineSummary['rho_pending'] }}</strong>
                <span>RHO placements pending from closed batches</span>
            </article>
        </div>

        <div class="intern-career-legend">
            <span class="intern-career-legend__ongoing"><i></i> Ongoing</span>
            <span class="intern-career-legend__upcoming"><i></i> Upcoming</span>
            <span class="intern-career-legend__open"><i></i> Open / dates incomplete</span>
            <span class="intern-career-legend__ended"><i></i> Ended / awaiting lifecycle close</span>
            <span class="intern-career-legend__closed"><i></i> Closed / historical</span>
            <span style="margin-left:auto;color:var(--md-on-surface-variant);font-weight:500;">
                {{ $timelineStart->format('M Y') }} – {{ $timelineEnd->format('M Y') }}
            </span>
        </div>

        <div class="intern-career-scroll">
            <table class="intern-career-table">
                <thead>
                    <tr>
                        <th class="intern-career-sticky intern-career-batch-col">Batch</th>
                        <th class="intern-career-sticky intern-career-owner-col">Responsible Officer</th>
                        <th class="intern-career-sticky intern-career-status-col">Status</th>
                        <th class="intern-career-sticky intern-career-next-col">Next / Final Milestone</th>
                        <th class="intern-career-sticky intern-career-rho-col">RHO Follow-up</th>
                        <th class="intern-career-timeline-col">
                            <div class="intern-career-month-grid">
                                @foreach ($timelineMonths as $month)
                                    <span title="{{ $month['label'] }}">
                                        <strong>{{ $month['month'] }}</strong>
                                        <small>{{ $month['year'] }}</small>
                                    </span>
                                @endforeach
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @if ($timelineRows->isEmpty())
                        <tr>
                            <td colspan="6" class="md-table__empty" style="padding:24px;">
                                No dated batches are available for the timeline.
                            </td>
                        </tr>
                    @else
                        @foreach ($timelineRows as $row)
                            <tr>
                                <td class="intern-career-sticky intern-career-batch-col">
                                    <strong>{{ $row['name'] }}</strong>
                                    <small style="display:block;margin-top:4px;color:var(--md-on-surface-variant);">
                                        {{ $row['period'] }}
                                    </small>
                                </td>
                                <td class="intern-career-sticky intern-career-owner-col">
                                    {{ $row['officer'] }}
                                </td>
                                <td class="intern-career-sticky intern-career-status-col">
                                    <span
                                        class="md-badge {{ $row['status'] === 'ongoing' ? 'md-badge--success' : 'md-badge--neutral' }}">
                                        {{ $row['status_label'] }}
                                    </span>
                                </td>
                                <td class="intern-career-sticky intern-career-next-col">
                                    <strong>{{ $row['milestone_label'] }}</strong>
                                    <small style="display:block;margin-top:3px;color:var(--md-on-surface-variant);">
                                        {{ $row['milestone_date'] }}
                                    </small>
                                </td>
                                <td class="intern-career-sticky intern-career-rho-col">
                                    <strong>{{ $row['rho_placed'] }} / {{ $row['interns'] }}</strong>
                                    <small style="display:block;margin-top:3px;color:var(--md-on-surface-variant);">
                                        {{ $row['rho_pending'] }} pending
                                    </small>
                                    <div class="intern-career-rho-progress"
                                        title="{{ $row['rho_percent'] }}% RHO placement recorded">
                                        <span style="width: {{ min(100, $row['rho_percent']) }}%;"></span>
                                    </div>
                                </td>
                                <td class="intern-career-timeline-col">
                                    <div class="intern-career-track">
                                        @if ($todayOffset !== null)
                                            <span class="intern-career-today" style="left: {{ $todayOffset }}%;">
                                                <em>Today</em>
                                            </span>
                                        @endif
                                        <div class="intern-career-bar intern-career-bar--{{ $row['status'] }}"
                                            style="left: {{ $row['left'] }}%; width: {{ $row['width'] }}%;"
                                            title="{{ $row['tooltip'] }}">
                                            {{ $row['period'] }}
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </section>

    <div class="md-card md-card--elevated">
        <div class="md-card__body"
            style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <h3 class="md-title-md">Batches</h3>
                <p class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:4px;">
                    Only the formally assigned Subject Officer has operational write access.
                </p>
            </div>
            @unless (auth()->user()->isSubjectOfficer())
                <div class="intern-readonly-note">Oversight mode: operational allocation fields are read-only.</div>
            @endunless
        </div>

        <div style="overflow-x:auto;">
            <table class="md-table">
                <thead>
                    <tr>
                        <th>Batch</th>
                        <th>Period</th>
                        <th>Responsible Subject Officer</th>
                        <th>Status</th>
                        <th style="text-align:right;">Interns</th>
                        <th style="text-align:right;">RHO Placed</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batches as $batch)
                        @php
                            $canEditBatch = \App\Services\InternAllocationAccessService::canEditBatch(
                                auth()->user(),
                                $batch,
                            );
                            $ownsBatch = \App\Services\InternAllocationAccessService::ownsBatch(auth()->user(), $batch);
                        @endphp
                        <tr>
                            <td>
                                <div class="md-label-md">{{ $batch->name }}</div>
                                @if ($ownsBatch)
                                    <span class="md-badge md-badge--success" style="margin-top:4px;">My
                                        responsibility</span>
                                    <details style="margin-top:6px;">
                                        <summary class="md-body-sm" style="cursor:pointer;color:var(--md-primary);">Edit
                                            batch dates</summary>
                                        <form method="POST" action="{{ route('intern-batches.period.update', $batch) }}"
                                            style="margin-top:8px;min-width:260px;">
                                            @csrf
                                            @method('PATCH')
                                            <input type="date" name="start_date" class="md-field__input"
                                                value="{{ $batch->start_date?->format('Y-m-d') }}" required>
                                            <input type="date" name="end_date" class="md-field__input"
                                                value="{{ $batch->end_date?->format('Y-m-d') }}" required
                                                style="margin-top:6px;">
                                            <input type="text" name="reason" class="md-field__input"
                                                placeholder="Reason for date change" minlength="5" maxlength="500"
                                                required style="margin-top:6px;">
                                            <button type="submit" class="md-btn md-btn--outlined"
                                                style="margin-top:6px;">Save Dates</button>
                                        </form>
                                    </details>
                                @endif
                            </td>
                            <td>
                                <div>{{ $batch->start_date?->format('d M Y') ?? 'Not set' }}</div>
                                <div class="md-body-sm" style="color:var(--md-on-surface-variant);">
                                    to {{ $batch->end_date?->format('d M Y') ?? 'Not set' }}
                                </div>
                            </td>
                            <td>
                                <div>{{ $batch->assignedSubjectOfficer?->name ?? 'Unassigned' }}</div>
                                @if ($canManageResponsibility)
                                    <details style="margin-top:6px;">
                                        <summary class="md-body-sm" style="cursor:pointer;color:var(--md-primary);">
                                            Reassign</summary>
                                        <form method="POST"
                                            action="{{ route('intern-batches.responsibility.update', $batch) }}"
                                            style="margin-top:8px;min-width:260px;">
                                            @csrf
                                            @method('PATCH')
                                            <select name="assigned_subject_officer_id" class="md-field__input" required>
                                                <option value="">Select responsible Subject Officer</option>
                                                @foreach ($subjectOfficers as $officer)
                                                    <option value="{{ $officer->id }}" @selected((int) $batch->assigned_subject_officer_id === (int) $officer->id)>
                                                        {{ $officer->name }} — {{ $officer->email }}
                                                    </option>
                                                @endforeach
                                            </select>

                                            @if ($subjectOfficers->isEmpty())
                                                <div class="md-body-sm" style="margin-top:6px;color:var(--md-error);">
                                                    No active Subject Officer accounts are available. Check Users → Roles
                                                    and ensure the officer has the Subject Officer role and is active.
                                                </div>
                                            @endif
                                            <label class="md-field" style="display:block;margin-top:6px;">
                                                <span class="md-field__label">Effective Date *</span>
                                                <input type="date" name="effective_date" class="md-field__input"
                                                    value="{{ old('effective_date', now()->format('Y-m-d')) }}" required>
                                            </label>

                                            <input type="text" name="reference_no" class="md-field__input"
                                                placeholder="Reference No. (optional)" maxlength="100"
                                                value="{{ old('reference_no') }}" style="margin-top:6px;">

                                            <textarea name="reason" class="md-field__input" placeholder="Reason / authority for assignment or reassignment"
                                                minlength="5" maxlength="500" required rows="2" style="margin-top:6px;">{{ old('reason') }}</textarea>

                                            <button type="submit" class="md-btn md-btn--outlined"
                                                style="margin-top:6px;" @disabled($subjectOfficers->isEmpty())>
                                                {{ $batch->assigned_subject_officer_id ? 'Confirm Reassignment' : 'Assign Responsibility' }}
                                            </button>
                                        </form>
                                    </details>
                                @endif
                            </td>
                            <td>
                                <span
                                    class="md-badge {{ $batch->operational_status === 'ongoing' ? 'md-badge--success' : 'md-badge--neutral' }}">
                                    {{ ucfirst($batch->operational_status) }}
                                </span>
                            </td>
                            <td style="text-align:right;">{{ $batch->interns_count }}</td>
                            <td style="text-align:right;">{{ $batch->rho_placed_count }} / {{ $batch->interns_count }}
                            </td>
                            <td>
                                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                    <a href="{{ route('intern-batches.allocations.edit', $batch) }}"
                                        class="md-btn md-btn--outlined" style="font-size:11px;">Capacity</a>
                                    <a href="{{ route('intern-batches.interns.index', $batch) }}"
                                        class="md-btn md-btn--outlined" style="font-size:11px;">Interns</a>
                                    <a href="{{ route('intern-batches.assignments.show', $batch) }}"
                                        class="md-btn md-btn--outlined" style="font-size:11px;">Assignments</a>
                                    <a href="{{ route('intern-batches.rho-placements.index', $batch) }}"
                                        class="md-btn md-btn--outlined" style="font-size:11px;">RHO Placements</a>
                                    @if ($batch->is_active)
                                        <a href="{{ route('intern-selection.show', $batch) }}" target="_blank"
                                            class="md-btn md-btn--text" style="font-size:11px;">Public Selection</a>
                                    @endif
                                    @if ($ownsBatch && $batch->is_active)
                                        <form method="POST" action="{{ route('intern-batches.toggle', $batch) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="md-btn md-btn--text"
                                                style="font-size:11px;">Close Batch</button>
                                        </form>
                                    @elseif(!$batch->is_active)
                                        <span class="md-badge md-badge--neutral">
                                            {{ $batch->closed_automatically ? 'Auto closed' : 'Closed' }}
                                        </span>
                                    @elseif(!$canEditBatch)
                                        <span class="md-badge md-badge--neutral">Read only</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="md-table__empty">No batches yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="md-card" style="margin-top:16px;padding:16px;">
        <strong>Governance safeguards</strong>
        <p class="md-body-sm" style="margin:6px 0 0;color:var(--md-on-surface-variant);">
            Batch dates and placements are official administrative records. Changes are restricted to the assigned Subject
            Officer,
            responsibility changes are reserved for Super Admin, exports are auditable, and RHO placement data is recorded
            only from
            authoritative decisions entered by an authorised officer. The system does not recommend placements
            automatically.
        </p>
    </div>
@endsection
