@extends('layouts.app')
@section('title', $isScoped ? 'My Retirements' : 'Retirement Project')
@section('content')
    <div class="page-header">
        <div>
            <div class="page-eyebrow">Workforce Management</div>
            <h1 class="page-title">Retirement Projects</h1>
            <p class="page-subtitle">Prepare, verify and complete employee retirement cases.</p>
        </div>
    </div>
    <div class="workforce-kpi-grid">
        <div class="workforce-kpi">
            <div class="workforce-kpi__label">Active Cases</div>
            <div class="workforce-kpi__value">{{ $projects->where('status', '!=', 'completed')->count() }}</div>
        </div>
        <div class="workforce-kpi">
            <div class="workforce-kpi__label">Completed on Page</div>
            <div class="workforce-kpi__value">{{ $projects->where('status', 'completed')->count() }}</div>
        </div>
        <div class="workforce-kpi">
            <div class="workforce-kpi__label">Eligible to Start</div>
            <div class="workforce-kpi__value">{{ $upcoming->count() }}</div>
        </div>
    </div>
    @php
        $pageCases = collect($projects->items());
    @endphp
    <div class="workflow-strip" aria-label="Retirement workflow overview">
        @foreach (['identified' => 'Identified', 'verified' => 'Verified', 'employee_notified' => 'Notified', 'completed' => 'Completed'] as $stage => $label)
            @php
                $stageCount = $pageCases->where('status', $stage)->count();
            @endphp
            <div class="workflow-stage">
                <div class="workflow-stage__label">{{ $label }}</div>
                <div class="workflow-stage__value">{{ $stageCount }}</div>
                <div class="workflow-stage__bar">
                    <span
                        style="--stage-color:{{ $stage === 'completed' ? 'var(--md-success)' : ($stage === 'employee_notified' ? 'var(--md-primary)' : 'var(--md-warning)') }};width:{{ $pageCases->count() ? min(100, round(($stageCount / $pageCases->count()) * 100)) : 0 }}%">
                    </span>
                </div>
            </div>
        @endforeach
    </div>
    @if ($upcoming->isNotEmpty())
        <section class="workforce-panel" style="margin-bottom:16px">
            <h2 class="md-title-lg">Start Retirement Case</h2>
            <div style="display:grid;gap:10px;margin-top:12px">
                @foreach ($upcoming->take(10) as $e)
                    <form method="POST" action="{{ route('retirement-projects.store') }}"
                        style="display:flex;gap:12px;align-items:center;justify-content:space-between;border-bottom:1px solid var(--md-outline-variant);padding-bottom:10px">
                        @csrf
                        <input type="hidden" name="employee_id" value="{{ $e->id }}">
                        <input type="hidden" name="retirement_date" value="{{ $e->retire_date->format('Y-m-d') }}">
                        <div>
                            <a href="{{ route('employees.show', $e) }}">
                                <strong>{{ $e->display_name }}</strong>
                            </a>
                            <br>
                            <small>{{ $e->position?->title ?? '—' }} · {{ $e->retire_date->format('d M Y') }}</small>
                        </div>
                        <button class="md-btn--primary">Start Case</button>
                    </form>
                @endforeach
            </div>
        </section>
    @endif
    <section class="workforce-panel">
        <h2 class="md-title-lg">Retirement Cases</h2>
        <div style="overflow:auto;margin-top:12px">
            <table class="md-table" style="width:100%">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Retirement</th>
                        <th>Verification</th>
                        <th>Status</th>
                        <th>Update</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($projects as $project)
                        <tr>
                            <td>
                                <a href="{{ route('employees.show', $project->employee) }}">
                                    <strong>{{ $project->employee?->display_name }}</strong>
                                </a>
                                <br>
                                <small>{{ $project->employee?->position?->title ?? '—' }} ·
                                    {{ $project->employee?->unit?->name ?? '—' }}</small>
                            </td>
                            <td>{{ $project->retirement_date->format('d M Y') }}<br>
                                <small>{{ $project->replacement_required ? 'Replacement required' : 'No replacement flag' }}</small>
                            </td>
                            <td>
                                <small>DOB {{ $project->dob_verified ? '✓' : '○' }} · Service
                                    {{ $project->service_verified ? '✓' : '○' }} · Contact
                                    {{ $project->contact_verified ? '✓' : '○' }} · Docs
                                    {{ $project->documents_verified ? '✓' : '○' }}</small>
                            </td>
                            <td>
                                <span
                                    class="status-chip {{ $project->status === 'completed' ? 'status-chip--good' : 'status-chip--warn' }}">{{ ucwords(str_replace('_', ' ', $project->status)) }}</span>
                            </td>
                            <td>
                                <details>
                                    <summary style="cursor:pointer;color:var(--md-primary)">Update</summary>
                                    <form method="POST" action="{{ route('retirement-projects.update', $project) }}"
                                        style="min-width:360px;padding:10px 0;display:grid;gap:8px">
                                        @csrf @method('PUT')
                                        <select class="md-select" name="status">
                                            @foreach (['identified', 'verified', 'notification_prepared', 'employee_notified', 'documentation', 'clearance', 'completed'] as $s)
                                                <option value="{{ $s }}" @selected($project->status === $s)>
                                                    {{ ucwords(str_replace('_', ' ', $s)) }}</option>
                                            @endforeach
                                        </select>
                                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px">
                                            @foreach (['dob_verified' => 'DOB verified', 'service_verified' => 'Service verified', 'contact_verified' => 'Contact verified', 'documents_verified' => 'Documents verified', 'replacement_required' => 'Replacement required', 'vacancy_created' => 'Vacancy created'] as $f => $label)
                                                <label>
                                                    <input type="checkbox" name="{{ $f }}" value="1"
                                                        @checked($project->$f)> {{ $label }}</label>
                                            @endforeach
                                        </div>
                                        <div class="md-form-row">
                                            <div>
                                                <label class="md-label">Pension</label>
                                                <select class="md-select" name="pension_status">
                                                    @foreach (['not_started', 'in_progress', 'completed'] as $s)
                                                        <option value="{{ $s }}" @selected($project->pension_status === $s)>
                                                            {{ ucwords(str_replace('_', ' ', $s)) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="md-label">Clearance</label>
                                                <select class="md-select" name="clearance_status">
                                                    @foreach (['not_started', 'in_progress', 'completed'] as $s)
                                                        <option value="{{ $s }}" @selected($project->clearance_status === $s)>
                                                            {{ ucwords(str_replace('_', ' ', $s)) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="md-label">Handover</label>
                                                <select class="md-select" name="handover_status">
                                                    @foreach (['not_started', 'in_progress', 'completed'] as $s)
                                                        <option value="{{ $s }}" @selected($project->handover_status === $s)>
                                                            {{ ucwords(str_replace('_', ' ', $s)) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <input type="date" class="md-input" name="notification_issued_date"
                                            value="{{ optional($project->notification_issued_date)->format('Y-m-d') }}">
                                        <input type="date" class="md-input" name="employee_acknowledged_date"
                                            value="{{ optional($project->employee_acknowledged_date)->format('Y-m-d') }}">
                                        <input type="date" class="md-input" name="final_working_date"
                                            value="{{ optional($project->final_working_date)->format('Y-m-d') }}">
                                        <input class="md-input" name="reference_no" value="{{ $project->reference_no }}"
                                            placeholder="Reference">
                                        <textarea class="md-input" name="remarks" rows="2" placeholder="Remarks">{{ $project->remarks }}</textarea>
                                        <button class="md-btn--primary">Save Case</button>
                                    </form>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align:center;padding:30px">No retirement cases started yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:14px">{{ $projects->links() }}</div>
    </section>
@endsection
