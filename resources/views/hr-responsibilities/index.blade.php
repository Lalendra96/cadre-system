@extends('layouts.app')

@section('title', 'HR Responsibilities')

@section('content')
    <div class="page-header">
        <div>
            <div class="page-eyebrow">Workforce administration</div>
            <h1 class="page-title">HR Responsibilities</h1>
            @if (auth()->user()->canAccessHrIntelligence())
                <a class="md-btn md-btn--text" href="{{ route('hr-intelligence.index') }}">Workload, coverage alerts &
                    reassignment queue</a>
            @endif
            <p class="page-subtitle">Assign positions, arrange temporary cover and hand over responsibility with a recorded
                history.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="md-card" role="alert" style="padding:16px;margin-bottom:16px;">
            <strong>Please correct the following:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="md-card" style="padding:20px;margin-bottom:20px;">
        <h2 class="md-title-md">How responsibility works</h2>
        <p>HR access and increment/retirement reminders follow the same position assignments. Temporary cover includes both
            start and end dates, using the hospital server's configured timezone.</p>
        <p>A handover takes effect only after the receiving officer accepts. Future handovers keep the current officer
            responsible until the effective date. Record outstanding work in the handover; employee records and open work
            remain attached to their positions.</p>
        <p>Once an account receives an explicit HR assignment, ending or expiring its final assignment leaves it with no
            position-scoped HR access. Its old Subject Codes do not restore access. Monthly Carder responsibilities still
            use Subject Codes.</p>
        @if (auth()->user()->isSubjectOfficer())
            <h3 class="md-title-sm">Your effective HR positions today</h3>
            @forelse ($effectivePositions as $position)
                <span class="md-chip">{{ $position->title }}</span>
            @empty
                <p>No positions are currently assigned to you.</p>
            @endforelse
            @unless (auth()->user()->hr_scope_configured)
                <p>Legacy Subject Code responsibility currently applies to your account.</p>
            @endunless
        @endif
    </div>

    @if ($manager)
        <details class="md-card" style="padding:20px;margin-bottom:20px;" open>
            <summary class="md-title-md">Responsibility coverage and warnings</summary>
            <p>{{ $health->filter(fn($row) => $row['position']->is_active && $row['owners']->isEmpty())->count() }} active
                positions without an officer;
                {{ $health->filter(fn($row) => $row['position']->is_active && $row['owners']->count() > 1)->count() }}
                positions with shared responsibility;
                {{ $unpositioned }} active employees without a position.</p>
            <p>Shared responsibility may be intentional temporary cover. Review it before making changes. Counts include
                legacy officers who still have effective access.</p>
            <div style="overflow-x:auto;">
                <table class="md-table" style="width:100%;">
                    <thead>
                        <tr>
                            <th>Position</th>
                            <th>Active employees</th>
                            <th>Profile allocation</th>
                            <th>Responsible officers today</th>
                            <th>Review</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($health as $row)
                            <tr>
                                <td>{{ $row['position']->title }}</td>
                                <td>{{ $row['employees'] }}</td>
                                <td>{{ $row['allocated'] }} allocated / {{ $row['unallocated'] }} unallocated</td>
                                <td>{{ $row['owners']->pluck('name')->join(', ') ?: 'No officer' }}</td>
                                <td>
                                    @if (!$row['position']->is_active)
                                        Disabled position — no HR scope granted
                                    @elseif ($row['owners']->isEmpty())
                                        Unassigned — administrator action required
                                    @elseif ($row['owners']->count() > 1)
                                        Shared — confirm intentional cover
                                    @else
                                        Assigned
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    @endif

    @if (auth()->user()->isSuperAdmin())
        <details class="md-card" style="padding:20px;margin-bottom:20px;">
            <summary class="md-title-md">Assign a position or temporary cover</summary>
            <p>The first explicit assignment replaces legacy HR access for this officer. Add every position the officer
                should continue managing. Other officers' assignments are retained.</p>
            <form method="POST" action="{{ route('hr-responsibilities.store') }}"
                style="display:grid;gap:12px;margin-top:16px;">
                @csrf
                <label>Position
                    <select class="md-field__input" name="position_id" required>
                        <option value="">Choose a position</option>
                        @foreach ($positions->where('is_active', true) as $position)
                            <option value="{{ $position->id }}" @selected(old('position_id') == $position->id)>{{ $position->title }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <label>Subject Officer
                    <select class="md-field__input" name="user_id" required>
                        <option value="">Choose an officer</option>
                        @foreach ($officers as $officer)
                            <option value="{{ $officer->id }}" @selected(old('user_id') == $officer->id)>{{ $officer->name }}
                                ({{ $officer->email }})
                            </option>
                        @endforeach
                    </select>
                </label>
                <label>Responsibility type
                    <select class="md-field__input" name="kind" required>
                        <option value="permanent" @selected(old('kind') === 'permanent')>Permanent</option>
                        <option value="temporary" @selected(old('kind') === 'temporary')>Temporary / acting cover</option>
                    </select>
                </label>
                <label>Starts on <input class="md-field__input" type="date" name="starts_on"
                        value="{{ old('starts_on', today()->toDateString()) }}" min="{{ today()->toDateString() }}"
                        required></label>
                <label>Ends on — required for temporary cover; leave blank for permanent
                    <input class="md-field__input" type="date" name="ends_on" value="{{ old('ends_on') }}"
                        min="{{ today()->toDateString() }}">
                </label>
                <label>Reason / assignment notes
                    <textarea class="md-field__input" name="notes" rows="3" maxlength="4000" required>{{ old('notes') }}</textarea>
                </label>
                <button class="md-btn md-btn--filled">Record responsibility</button>
            </form>
        </details>

        <details class="md-card" style="padding:20px;margin-bottom:20px;" open>
            <summary class="md-title-md">Allocate Employee Profiles to Subject Officers</summary>
            <p>Use this when one position is shared by several Subject Officers. Position responsibility defines which
                positions an officer may handle; this allocation defines the individual Employee Profiles they can actually
                open. One employee has one active profile owner at a time.</p>
            <form method="POST" action="{{ route('hr-responsibilities.employee-allocations.store') }}"
                style="display:grid;gap:12px;margin-top:16px;">
                @csrf
                <label>Subject Officer
                    <select class="md-field__input" name="user_id" id="allocation-officer" required>
                        <option value="">Choose an officer</option>
                        @foreach ($allocationOfficers as $officer)
                            <option value="{{ $officer->id }}"
                                data-positions="{{ $officer->effectiveHrPositionIds()->implode(',') }}">
                                {{ $officer->name }} ({{ $officer->email }})</option>
                        @endforeach
                    </select>
                </label>
                <label>Employee Profiles
                    <select class="md-field__input" name="employee_ids[]" id="allocation-employees" multiple size="12"
                        required>
                        @foreach ($allocationEmployees as $employee)
                            @php
                                $owner = $employeeAllocations->firstWhere('employee_id', $employee->id);
                            @endphp
                            <option value="{{ $employee->id }}" data-position="{{ $employee->position_id }}">
                                {{ $employee->name }}{{ $employee->pay_no ? ' · ' . $employee->pay_no : '' }} ·
                                {{ $employee->position?->title ?? 'No position' }}{{ $owner ? ' · currently ' . $owner->user?->name : ' · UNALLOCATED' }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <small>After choosing an officer, only employees in that officer's effective HR positions remain available.
                    Hold Ctrl/Cmd to select several employees.</small>
                <label>Allocation reason / note
                    <textarea class="md-field__input" name="reason" rows="3" maxlength="2000" required
                        placeholder="e.g. Distribution of Nursing Officer profiles among Subject Officers"></textarea>
                </label>
                <button class="md-btn md-btn--filled">Allocate selected profiles</button>
            </form>

            <div style="overflow-x:auto;margin-top:20px;">
                <table class="md-table" style="width:100%;">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Position</th>
                            <th>Allocated Subject Officer</th>
                            <th>Since</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employeeAllocations as $allocation)
                            <tr>
                                <td>{{ $allocation->employee?->name ?? 'Employee #' . $allocation->employee_id }}</td>
                                <td>{{ $allocation->position?->title ?? '—' }}</td>
                                <td>{{ $allocation->user?->name ?? '—' }}</td>
                                <td>{{ $allocation->starts_on?->format('d M Y') }}</td>
                                <td>
                                    <form method="POST"
                                        action="{{ route('hr-responsibilities.employee-allocations.end', $allocation) }}"
                                        style="display:flex;gap:8px;align-items:center;">
                                        @csrf
                                        <input class="md-field__input" name="reason" maxlength="2000" required
                                            placeholder="Reason" style="min-width:180px;">
                                        <button class="md-btn md-btn--outlined">End allocation</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">No Employee Profile allocations recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </details>

        <details class="md-card" style="padding:20px;margin-bottom:20px;">
            <summary class="md-title-md">Arrange a permanent handover</summary>
            <p>Select the outgoing responsibility below. The recipient must accept on or before the effective date. For
                several positions, create one handover for each so acceptance and history remain explicit.</p>
            @forelse ($ongoing as $assignment)
                <details style="padding:12px 0;">
                    <summary>{{ $assignment->position->title }} — {{ $assignment->user->name }}</summary>
                    <form method="POST" action="{{ route('hr-responsibilities.handover', $assignment) }}"
                        style="display:grid;gap:12px;margin-top:12px;">
                        @csrf
                        <label>Receiving Subject Officer
                            <select class="md-field__input" name="to_user_id" required>
                                <option value="">Choose an officer</option>
                                @foreach ($officers->where('id', '!=', $assignment->user_id) as $officer)
<option value="{{ $officer->id }}">{{ $officer->name }} ({{ $officer->email }})</option>
@endforeach
                        </select>
                    </label>
                    <label>Effective date <input class="md-field__input" type="date" name="effective_on" min="{{ today()->toDateString() }}" required></label>
                    <label>Handover notes <textarea class="md-field__input" name="notes" maxlength="4000" rows="3" required></textarea></label>
                    <label>Outstanding actions — write “None” if there are no pending actions
                        <textarea class="md-field__input" name="outstanding_actions" maxlength="6000" rows="4" required></textarea>
                    </label>
                    <button class="md-btn md-btn--filled">Send for acceptance</button>
                </form>
            </details>
        @empty
            <p>No ongoing permanent assignments are available for handover.</p>
@endforelse
    </details>
@endif

<section class="md-card" style="padding:20px;margin-bottom:20px;">
    <h2 class="md-title-md">Handovers and acceptance</h2>
    @forelse ($handovers as $handover)
<article style="padding:16px 0;border-bottom:1px solid var(--md-outline-variant);">
            <h3 class="md-title-sm">#{{ $handover->id }} · {{ $handover->responsibility->position->title }}</h3>
            <p>{{ $handover->responsibility->user->name }} → {{ $handover->recipient->name }} · Effective {{ $handover->effective_on->format('d M Y') }} · {{ ucfirst($handover->status) }}</p>
            @if ($handover->status === 'pending' && $handover->effective_on->lt(today()))
<p role="alert">Overdue: responsibility has not changed. Ask the administrator to cancel and reissue this handover.</p>
@endif
            <p><strong>Notes:</strong> <span style="white-space:pre-wrap;">{{ $handover->notes }}</span></p>
            <p><strong>Outstanding actions:</strong> <span style="white-space:pre-wrap;">{{ $handover->outstanding_actions }}</span></p>
            @if ($handover->accepted_at)
<p>Accepted by {{ $handover->recipient->name }} on {{ $handover->accepted_at->format('d M Y H:i') }}.</p>
@endif
            @if ($handover->cancelled_at)
<p>Cancelled {{ $handover->cancelled_at->format('d M Y H:i') }}: {{ $handover->cancel_reason }}</p>
@endif
            @if ($handover->status === 'pending' && $handover->to_user_id === auth()->id() && !$handover->effective_on->lt(today()))
<form method="POST" action="{{ route('hr-responsibilities.accept', $handover) }}">
                    @csrf
                    <p>Accepting your first explicit responsibility replaces legacy Subject Code HR access. Ask the administrator to assign any other positions you must retain.</p>
                    <button class="md-btn md-btn--filled">Accept responsibility and outstanding actions</button>
                </form>
@endif
            @if ($handover->status === 'pending' && auth()->user()->isSuperAdmin())
<form method="POST" action="{{ route('hr-responsibilities.cancel', $handover) }}" style="margin-top:12px;">
                    @csrf
                    <label>Cancellation reason <input class="md-field__input" name="reason" maxlength="2000" required></label>
                    <button class="md-btn md-btn--outlined">Cancel handover</button>
                </form>
@endif
        </article>
    @empty
        <p>No handovers are recorded.</p>
@endforelse
    {{ $handovers->withQueryString()->links() }}
</section>

<section class="md-card" style="padding:20px;">
    <h2 class="md-title-md">Assignment history</h2>
    <p>Ended and expired assignments remain here. Historical entries do not grant current access.</p>
    <div style="overflow-x:auto;">
        <table class="md-table" style="width:100%;">
            <thead><tr><th>Position / officer</th><th>Type and dates</th><th>Status</th><th>Recorded by / notes</th><th>Action</th></tr></thead>
            <tbody>
                @forelse ($assignments as $assignment)
<tr>
                        <td>{{ $assignment->position->title }}<br>{{ $assignment->user->name }}</td>
                        <td>{{ ucfirst($assignment->kind) }}<br>{{ $assignment->starts_on->format('d M Y') }} – {{ $assignment->ends_on?->format('d M Y') ?? 'Ongoing' }}</td>
                        <td>
                            @if ($assignment->ended_at)
Ended {{ $assignment->ended_at->format('d M Y H:i') }}<br>{{ $assignment->end_reason }}
@elseif ($assignment->ends_on && $assignment->ends_on->lt(today()))
Completed / expired
@elseif (!$assignment->user->is_active || !$assignment->user->isSubjectOfficer())
Inactive officer / role removed — no access
@elseif (!$assignment->position->is_active)
Disabled position — no access
@elseif ($assignment->starts_on->gt(today()))
Scheduled
@else
Effective
@endif
                        </td>
                        <td>{{ $assignment->assignedBy?->name ?? 'Legacy assignment' }}<br><span style="white-space:pre-wrap;">{{ $assignment->notes }}</span></td>
                        <td>
                            @if (auth()->user()->isSuperAdmin() &&
                                    !$assignment->ended_at &&
                                    (!$assignment->ends_on || !$assignment->ends_on->lt(today())))
<form method="POST" action="{{ route('hr-responsibilities.end', $assignment) }}">
                                    @csrf
                                    <label>Reason for ending now <input class="md-field__input" name="reason" maxlength="2000" required></label>
                                    <button class="md-btn md-btn--outlined">End responsibility</button>
                                </form>
@else
History retained
@endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">No explicit assignments recorded.</td></tr>
@endforelse
            </tbody>
        </table>
    </div>
    {{ $assignments->withQueryString()->links() }}
</section>
@push('scripts')
    <script>
        (function() {
            const officer = document.getElementById('allocation-officer');
            const employees = document.getElementById('allocation-employees');
            if (!officer || !employees) return;
            const options = Array.from(employees.options);

            function filterEmployees() {
                const selected = officer.options[officer.selectedIndex];
                const allowed = new Set((selected?.dataset.positions || '').split(',').filter(Boolean));
                options.forEach(opt => {
                    const show = allowed.size > 0 && allowed.has(opt.dataset.position);
                    opt.hidden = !show;
                    opt.disabled = !show;
                    if (!show) opt.selected = false;
                });
            }
            officer.addEventListener('change', filterEmployees);
            filterEmployees();
        })();
    </script>
@endpush

@endsection)
