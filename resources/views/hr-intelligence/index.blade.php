@extends('layouts.app')

@section('title', 'HR Intelligence & Reassignments')

@section('content')
    <div class="page-header">
        <div>
            <div class="page-eyebrow">HR responsibility intelligence</div>
            <h1 class="page-title">Coverage, workload & pending handovers</h1>
            <p class="page-subtitle">Identify gaps and confirm who takes over outstanding employee work.</p>
        </div>
        <a class="md-btn md-btn--outlined" href="{{ route('hr-responsibilities.index') }}">Position assignments & history</a>
    </div>

    @if ($errors->any())
        <div class="md-card" role="alert" style="padding:16px;margin-bottom:16px;">
            <strong>Unable to complete the action</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="md-card" style="padding:20px;margin-bottom:20px;">
        <h2 class="md-title-md">How to use this page</h2>
        <p><strong>1. Review coverage.</strong> Correct missing or unintended position assignments through HR
            Responsibilities.</p>
        <p><strong>2. Review pending work.</strong> Where several officers have access, a manager selects the officer who
            will take responsibility for the employee's open work.</p>
        <p><strong>3. Accept the work.</strong> The selected officer acknowledges the transfer. Open data-quality issues,
            increments and retirement projects are reassigned together. This does not grant additional employee access.</p>
        <p>Ownership observations refresh every five minutes when the scheduler is running. Recorded ownership history
            begins with the first refresh; earlier employee ownership is not reconstructed.</p>
        <p><strong>Last completed refresh:</strong>
            {{ $lastChecked ?? 'Not initialized — a manager must refresh, or run the scheduled reconciliation command.' }}
        </p>
        @if ($manager)
            <form method="POST" action="{{ route('hr-intelligence.refresh') }}">
                @csrf
                <button class="md-btn md-btn--filled">Refresh coverage & reassignment queue</button>
            </form>
        @endif
    </div>

    <section class="md-card" style="padding:20px;margin-bottom:20px;">
        <h2 class="md-title-md">{{ $manager ? 'Subject Officer workload' : 'Your HR workload' }}</h2>
        <p>Coverage counts reflect current position access. Shared positions appear under each responsible officer, so rows
            must not be added to obtain a hospital total. Assigned work is the subset with a recorded task owner, including
            prior data-quality assignments and accepted reassignments.</p>
        <div style="overflow-x:auto;">
            <table class="md-table" style="width:100%;">
                <thead>
                    <tr>
                        <th>Officer</th>
                        <th>Positions</th>
                        <th>Employees</th>
                        <th>Open data quality</th>
                        <th>Increments overdue / due in 30 days</th>
                        <th>Open retirement projects</th>
                        <th>Retiring in 12 months</th>
                        <th>Assigned work within these counts</th>
                        <th>Awaiting acceptance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($workloads as $row)
                        <tr>
                            <td>{{ $row['officer']->name }}</td>
                            <td>{{ $row['positions'] }}</td>
                            <td>{{ $row['employees'] }}</td>
                            <td>{{ $row['quality'] }}</td>
                            <td>{{ $row['increments'] }}</td>
                            <td>{{ $row['retirement_projects'] }}</td>
                            <td>{{ $row['retiring'] }}</td>
                            <td>{{ $row['assigned_work'] }}</td>
                            <td>{{ $row['acceptances'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">No active Subject Officers are available in this view.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($manager)
            <p>Use these counts to discuss redistribution, then create a dated handover or temporary cover through HR
                Responsibilities. No automatic workload score or staffing recommendation is applied.</p>
        @endif
    </section>

    @if ($manager)
        <section class="md-card" style="padding:20px;margin-bottom:20px;">
            <h2 class="md-title-md">Open coverage alerts</h2>
            <p>Shared ownership can be intentional cover. Review the actual assignments before ending responsibility. In-app
                alerts are sent to HR managers once per incident; a resolved issue can alert again if it reopens.</p>
            <div style="overflow-x:auto;">
                <table class="md-table" style="width:100%;">
                    <thead>
                        <tr>
                            <th>Position</th>
                            <th>Finding</th>
                            <th>Opened</th>
                            <th>Occurrence</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($alerts as $alert)
                            <tr>
                                <td>{{ $alert->title }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', $alert->kind)) }}</td>
                                <td>{{ $alert->opened_at }}</td>
                                <td>{{ $alert->occurrence }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">No open recorded alerts. Check the observation date above.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $alerts->withQueryString()->links() }}
        </section>
    @endif

    <section class="md-card" style="padding:20px;">
        <h2 class="md-title-md">Employee work reassignment queue</h2>
        <p>A healthy employee's first baseline does not create unnecessary acceptance work. Later position/ownership changes
            enter this queue. Older open cases become superseded when a newer observation replaces them.</p>
        <form method="GET" action="{{ route('hr-intelligence.index') }}"
            style="display:flex;gap:12px;flex-wrap:wrap;align-items:end;margin-bottom:16px;">
            <label>Status
                <select class="md-field__input" name="status">
                    @foreach (['open' => 'Open reviews', 'accepted' => 'Accepted', 'superseded' => 'Superseded', 'all' => 'All history'] as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>Exact employee record ID <input class="md-field__input" name="employee_id" type="number" min="1"
                    value="{{ request('employee_id') }}"></label>
            <button class="md-btn md-btn--outlined">Apply filter</button>
        </form>
        @forelse ($cases as $case)
            <article style="padding:18px 0;border-top:1px solid var(--md-outline-variant);">
                <h3 class="md-title-md">Case #{{ $case->id }} · {{ $case->employee->display_name }}</h3>
                <p>Employee record #{{ $case->employee_id }} · {{ $case->employee->position?->title ?? 'No position' }} ·
                    <strong>{{ ucwords(str_replace('_', ' ', $case->status)) }}</strong>
                </p>
                <p>Opened {{ $case->created_at->format('d M Y H:i') }} · Proposed officer:
                    {{ $case->proposedOfficer?->name ?? 'Manager review needed' }}</p>
                @if (!$case->employee->trashed())
                    <a class="md-btn md-btn--text" href="{{ route('employees.show', $case->employee) }}">Review employee
                        profile & open work</a>
                    <a class="md-btn md-btn--text" href="{{ route('hr-intelligence.history', $case->employee) }}">Employee
                        ownership timeline</a>
                @endif
                @if ($case->review_note)
                    <p style="white-space:pre-wrap;">{{ $case->review_note }}</p>
                @endif
                @if ($case->status === 'accepted')
                    <p>Accepted by {{ $case->acceptedOfficer?->name }} on {{ $case->accepted_at?->format('d M Y H:i') }}.
                    </p>
                    <p>Work transferred: {{ count($case->work_transfer['data_quality_issues'] ?? []) }} data-quality
                        issues,
                        {{ count($case->work_transfer['employee_increments'] ?? []) }} increments,
                        {{ count($case->work_transfer['retirement_projects'] ?? []) }} retirement projects.</p>
                @elseif (in_array($case->status, \App\Models\HrReassignmentCase::OPEN_STATUSES, true))
                    @if ($manager)
                        @if ($eligible->get($case->employee->position_id, collect())->isEmpty())
                            <p>No current HR owner is eligible. Assign the position, then refresh the queue.</p>
                        @else
                            <form method="POST" action="{{ route('hr-intelligence.propose', $case) }}"
                                style="display:grid;gap:10px;margin-top:12px;">
                                @csrf
                                <label>Officer to take over this employee's open work
                                    <select class="md-field__input" name="officer_id" required>
                                        <option value="">Choose a current owner</option>
                                        @foreach ($eligible->get($case->employee->position_id, collect()) as $officer)
                                            <option value="{{ $officer->id }}" @selected($case->proposed_user_id == $officer->id)>
                                                {{ $officer->name }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label>Reason / instructions
                                    <textarea class="md-field__input" name="note" rows="2" maxlength="2000" required></textarea>
                                </label>
                                <button class="md-btn md-btn--outlined">Send to officer for acceptance</button>
                            </form>
                        @endif
                    @endif
                    @if ($case->status === 'awaiting_acceptance' && (int) $case->proposed_user_id === (int) auth()->id())
                        <form method="POST" action="{{ route('hr-intelligence.accept', $case) }}"
                            style="display:grid;gap:10px;margin-top:12px;">
                            @csrf
                            <label>Acceptance note
                                <textarea class="md-field__input" name="note" rows="2" maxlength="2000" required></textarea>
                            </label>
                            <button class="md-btn md-btn--filled">Accept & take over open work</button>
                        </form>
                    @endif
                @endif
            </article>
        @empty
            <p>No reassignment cases match this view.</p>
        @endforelse
        {{ $cases->links() }}
    </section>
@endsection
