@extends('layouts.app')
@section('title', $isScoped ? 'My Increments' : 'Increment Management')
@section('content')
    <div class="page-header">
        <div>
            <div class="page-eyebrow">Workforce Management</div>
            <h1 class="page-title">Increment Management</h1>
            <p class="page-subtitle">Track due, overdue and upcoming employee increment actions.</p>
        </div>
    </div>
    <div class="workforce-kpi-grid">
        <div class="workforce-kpi">
            <div class="workforce-kpi__label">Due in 30 Days</div>
            <div class="workforce-kpi__value">{{ $due30 }}</div>
        </div>
        <div class="workforce-kpi">
            <div class="workforce-kpi__label">Due in 90 Days</div>
            <div class="workforce-kpi__value">{{ $due90 }}</div>
        </div>
        <div class="workforce-kpi">
            <div class="workforce-kpi__label">Past / Overdue</div>
            <div class="workforce-kpi__value">{{ $overdue }}</div>
        </div>
        <div class="workforce-kpi">
            <div class="workforce-kpi__label">Reminders Sent</div>
            <div class="workforce-kpi__value">{{ $notified }}</div>
        </div>
    </div>
    <section class="workforce-panel">
        <h2 class="md-title-lg">Increment Worklist</h2>
        <div style="overflow:auto;margin-top:12px">
            <table class="md-table" style="width:100%">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Position / Unit</th>
                        <th>Date</th>
                        <th>Workflow</th>
                        <th>Reminder</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pending as $increment)
                        <tr>
                            <td>
                                <a href="{{ route('employees.show', $increment->employee) }}">
                                    <strong>{{ $increment->employee?->display_name }}</strong>
                                </a>
                                <br>
                                <small>{{ $increment->employee?->pay_no ?: '—' }}</small>
                            </td>
                            <td>{{ $increment->employee?->position?->title ?? '—' }}<br>
                                <small>{{ $increment->employee?->unit?->name ?? '—' }}</small>
                            </td>
                            <td>{{ $increment->increment_date->format('d M Y') }}</td>
                            <td>
                                <span
                                    class="status-chip {{ $increment->workflow_status === 'granted' ? 'status-chip--good' : (in_array($increment->workflow_status, ['deferred', 'withheld']) ? 'status-chip--bad' : 'status-chip--warn') }}">{{ ucwords(str_replace('_', ' ', $increment->workflow_status ?? 'upcoming')) }}</span>
                            </td>
                            <td>{{ $increment->notified_at ? 'Sent' : 'Pending' }}</td>
                            <td>
                                <details>
                                    <summary style="cursor:pointer;color:var(--md-primary)">Process</summary>
                                    <form method="POST"
                                        action="{{ route('employee-increments.workflow', [$increment->employee, $increment]) }}"
                                        style="min-width:300px;display:grid;gap:8px;padding-top:8px">
                                        @csrf @method('PUT')
                                        <select name="workflow_status" class="md-select">
                                            @foreach (['upcoming', 'due', 'checked', 'recommended', 'approved', 'granted', 'deferred', 'withheld', 'reinstated'] as $s)
                                                <option value="{{ $s }}" @selected(($increment->workflow_status ?? 'upcoming') === $s)>
                                                    {{ ucwords($s) }}</option>
                                            @endforeach
                                        </select>
                                        <input type="date" class="md-input" name="granted_date"
                                            value="{{ optional($increment->granted_date)->format('Y-m-d') }}">
                                        <input class="md-input" name="decision_reason"
                                            value="{{ $increment->decision_reason }}" placeholder="Decision reason / note">
                                        <button class="md-btn--primary">Update Workflow</button>
                                    </form>
                                    <a class="md-btn md-btn--ghost" style="margin-top:6px"
                                        href="{{ route('employee-increments.index', $increment->employee) }}">Full
                                        History</a>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center;padding:30px">No upcoming increment records.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:14px">{{ $pending->links() }}</div>
    </section>
@endsection
