@extends('layouts.app')
@section('title', 'Employee 360')
@section('content')
    <div class="employee-hero">
        <div
            style="display:flex;justify-content:space-between;gap:18px;align-items:center;flex-wrap:wrap;position:relative;z-index:1">
            <div style="display:flex;align-items:center;gap:14px;min-width:0">
                <div class="employee-avatar-lg">{{ strtoupper(substr($employee->display_name, 0, 1)) }}</div>
                <div style="min-width:0">
                    <a href="{{ route('employees.index') }}" class="md-body-sm">← Employee Directory</a>
                    <h1 style="font-size:28px;line-height:1.2;font-weight:800;margin-top:4px;color:#fff">
                        {{ $employee->display_name }}</h1>
                    <p class="md-body-md">{{ $employee->pay_no ?: 'No Pay No.' }} ·
                        {{ $employee->position?->title ?? 'No position' }} · {{ $employee->unit?->name ?? 'No unit' }}</p>
                </div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                <span
                    class="status-chip {{ $employee->is_active ? 'status-chip--good' : 'status-chip--bad' }}">{{ $employee->is_active ? 'Active' : 'Inactive' }}</span>
                @if (Route::has('employees.edit'))
                    <a class="md-btn md-btn--outlined"
                        style="background:rgba(255,255,255,.12)!important;color:#fff!important;border-color:rgba(255,255,255,.26)!important"
                        href="{{ route('employees.edit', $employee) }}">Edit Master Record</a>
                @endif
            </div>
        </div>
    </div>
    <div class="workforce-kpi-grid">
        <div class="workforce-kpi">
            <div class="workforce-kpi__label">Subject Code</div>
            <div class="md-title-lg">{{ $employee->subjectCode?->code ?? '—' }}</div>
        </div>
        <div class="workforce-kpi">
            <div class="workforce-kpi__label">Current Grade</div>
            <div class="md-title-lg">{{ $employee->current_grade?->positionGrade?->name ?? '—' }}</div>
        </div>
        @php
            $profileCurrentGrade = $employee->current_grade;
            $profileNextGrade = $profileCurrentGrade?->positionGrade?->nextGrade();
            $profileGradeThreshold = $profileNextGrade?->minYearsInGrade();
            $profileGradeEligibleOn =
                $profileCurrentGrade && $profileGradeThreshold !== null
                    ? $profileCurrentGrade->effective_date
                        ?->copy()
                        ->addDays((int) round($profileGradeThreshold * 365.25))
                    : null;
            $publicServiceMonths = $employee->date_joined_public_service
                ? (int) $employee->date_joined_public_service->diffInMonths(now())
                : null;
            $institutionMonths = $employee->date_reported_for_duty
                ? (int) $employee->date_reported_for_duty->diffInMonths(now())
                : null;
        @endphp
        <div class="workforce-kpi">
            <div class="workforce-kpi__label">Grade Progression</div>
            <div class="md-title-lg">{{ $profileNextGrade?->name ?? 'Top / Not configured' }}</div>
            <small>
                @if ($profileGradeEligibleOn)
                    {{ $profileGradeEligibleOn->isPast() ? 'Eligible now' : 'Eligibility ' . $profileGradeEligibleOn->format('d M Y') }}
                @else
                    Configure grade criteria
                @endif
            </small>
        </div>
        <div class="workforce-kpi">
            <div class="workforce-kpi__label">Retirement</div>
            <div class="md-title-lg">{{ $employee->retire_date?->format('d M Y') ?? '—' }}</div>
        </div>
        <div class="workforce-kpi">
            <div class="workforce-kpi__label">Open Data Issues</div>
            <div class="workforce-kpi__value">
                {{ $employee->dataQualityIssues->whereNotIn('status', ['verified'])->count() }}</div>
        </div>
    </div>
    @if (auth()->user()->isSubjectOfficer())
        <details class="md-card" style="padding:12px 16px;margin-bottom:16px">
            <summary style="cursor:pointer;font-weight:600">Request a correction to a protected field</summary>
            <form method="POST" action="{{ route('employee-change-requests.store', $employee) }}"
                style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:12px">
                @csrf
                <select class="md-select" name="field_name" required>
                    <option value="">Select field</option>
                    <option value="nic_number">NIC</option>
                    <option value="date_of_birth">Date of birth</option>
                    <option value="date_of_appointment">Appointment date</option>
                    <option value="date_joined_public_service">Public-service joining date</option>
                    <option value="retirement_age">Retirement age</option>
                </select>
                <input class="md-input" name="requested_value" placeholder="Requested value">
                <textarea class="md-input" name="reason" required maxlength="2000"
                    placeholder="Explain why this correction is required" style="grid-column:1/-1">
                        </textarea>
                <button class="md-btn--primary" style="grid-column:1/-1">Submit correction request</button>
            </form>
        </details>
    @endif
    @php
        $openIssues = $employee->dataQualityIssues->whereNotIn('status', ['verified']);
    @endphp
    @if ($openIssues->isNotEmpty())
        <div class="workforce-panel" style="margin-bottom:16px;border-left:4px solid var(--md-error)">
            <h2 class="md-title-lg">Attention Required</h2>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px">
                @foreach ($openIssues->take(8) as $issue)
                    <a href="{{ route('data-quality.index') }}"
                        class="status-chip {{ $issue->severity === 'critical' ? 'status-chip--bad' : '' }}"
                        style="text-decoration:none">{{ $issue->label }} · {{ strtoupper($issue->severity) }}</a>
                @endforeach
            </div>
        </div>
    @endif
    <div class="workforce-panel" style="margin-bottom:16px">
        <div class="panel-title-row">
            <div>
                <div class="panel-title">Employment snapshot</div>
                <div class="panel-subtitle">Current service and appointment information.</div>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-top:12px">
            <div>
                <div class="md-body-sm">Employment Status</div>
                <strong>{{ ucwords(str_replace('_', ' ', $employee->employment_status ?? ($employee->is_active ? 'active' : 'inactive'))) }}</strong>
            </div>
            <div>
                <div class="md-body-sm">Current Service</div>
                <strong>{{ $employee->current_service_name ?? ($employee->combined_service_name ?? ($employee->currentServicePeriod?->service_name ?? '—')) }}</strong>
            </div>
            <div>
                <div class="md-body-sm">Combined Service</div>
                <strong>{{ $employee->combined_service_name ?? '—' }}</strong>
                <br>
                <span
                    class="md-body-sm">{{ $employee->date_joined_combined_service?->format('d M Y') ?? 'Not applicable / not recorded' }}</span>
            </div>
            <div>
                <div class="md-body-sm">Current Position</div>
                <strong>{{ $employee->position?->title ?? '—' }}</strong>
            </div>
            <div>
                <div class="md-body-sm">Current Grade</div>
                <strong>{{ $profileCurrentGrade?->positionGrade?->name ?? '—' }}</strong>
                <br>
                <span
                    class="md-body-sm">{{ $profileCurrentGrade?->effective_date?->format('d M Y') ?? ($employee->date_current_grade?->format('d M Y') ?? 'Grade start date not recorded') }}</span>
            </div>
            <div>
                <div class="md-body-sm">Next Grade / Promotion</div>
                <strong>{{ $profileNextGrade?->name ?? '—' }}</strong>
                <br>
                <span class="md-body-sm">
                    @if ($profileGradeEligibleOn)
                        {{ $profileGradeEligibleOn->isPast() ? 'Eligible for review now' : 'Estimated eligibility ' . $profileGradeEligibleOn->format('d M Y') }}
                    @else
                        Promotion rule not configured
                    @endif
                </span>
            </div>
            <div>
                <div class="md-body-sm">Current Unit / Ward</div>
                <strong>{{ $employee->unit?->name ?? '—' }}</strong>
            </div>
            <div>
                <div class="md-body-sm">Current Institution</div>
                <strong>{{ $employee->currentServicePeriod?->institution_name ?? 'Teaching Hospital Peradeniya' }}</strong>
            </div>
            <div>
                <div class="md-body-sm">Subject Code</div>
                <strong>{{ $employee->subjectCode?->code ?? '—' }}</strong>
            </div>
            <div>
                <div class="md-body-sm">Service File No.</div>
                <strong>{{ $employee->service_file_no ?? '—' }}</strong>
            </div>
            <div>
                <div class="md-body-sm">Professional Registration</div>
                <strong>{{ $employee->professional_registration_no ?? '—' }}</strong>
                <br>
                <span
                    class="md-body-sm">{{ $employee->professional_registration_expiry?->format('d M Y') ?? 'No expiry recorded' }}</span>
            </div>
            <div>
                <div class="md-body-sm">Date of Appointment</div>
                <strong>{{ $employee->date_of_appointment?->format('d M Y') ?? '—' }}</strong>
            </div>
            <div>
                <div class="md-body-sm">Joined Public Service</div>
                <strong>{{ $employee->date_joined_public_service?->format('d M Y') ?? '—' }}</strong>
            </div>
            <div>
                <div class="md-body-sm">Reported for Duty to this institute</div>
                <strong>{{ $employee->date_reported_for_duty?->format('d M Y') ?? ($employee->date_joined_institution?->format('d M Y') ?? '—') }}</strong>
            </div>
            <div>
                <div class="md-body-sm">Total Public Service</div>
                <strong>
                    @if ($publicServiceMonths !== null)
                        {{ intdiv($publicServiceMonths, 12) }}y {{ $publicServiceMonths % 12 }}m
                    @else
                        —
                    @endif
                </strong>
            </div>
            <div>
                <div class="md-body-sm">Service at this Institution</div>
                <strong>
                    @if ($institutionMonths !== null)
                        {{ intdiv($institutionMonths, 12) }}y {{ $institutionMonths % 12 }}m
                    @else
                        —
                    @endif
                </strong>
            </div>
            <div>
                <div class="md-body-sm">Salary Scale</div>
                <strong>{{ $employee->salaryScale?->code ?? '—' }}</strong>
                <br>
                <span class="md-body-sm">{{ $employee->salaryScale?->name ?? '' }}</span>
            </div>
            <div>
                <div class="md-body-sm">Retirement</div>
                <strong>{{ $employee->retire_date?->format('d M Y') ?? '—' }}</strong>
            </div>
        </div>
    </div>
    @if (\App\Services\FeatureToggleService::enabled('ai_record_assistant'))
        <div class="workforce-panel" style="margin-bottom:16px">
            <div class="panel-title-row">
                <div>
                    <div class="panel-title">✨ Record Assistant</div>
                    <div class="panel-subtitle">Privacy-first summary of recorded facts. It never replaces the official
                        service record.</div>
                </div>
                <button type="button" id="loadAiSummary"
                    data-ai-summary-url="{{ route('ai-record-assistant.employee', $employee) }}"
                    class="md-btn md-btn--tonal">Summarise record</button>
            </div>
            @include('partials.section-help', ['topic' => 'ai'])
            <div id="aiSummaryBox" class="md-body-md ai-result" role="status" aria-live="polite"
                style="white-space:pre-wrap;margin-top:10px;color:var(--md-on-surface-variant)">Select “Summarise record”
                to review chronology gaps and key service facts.</div>
        </div>
    @endif
    <div class="workforce-panel" style="margin-bottom:16px">
        <div class="panel-title-row">
            <div>
                <div class="panel-title">What do you want to record?</div>
                <div class="panel-subtitle">Choose the administrative action; the system will take you to the correct
                    record type.</div>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:9px;margin-top:10px">
            <a class="action-card" href="{{ route('transfer-records.create', ['employee_id' => $employee->id]) }}">
                <span>🔄</span>
                <div>
                    <strong>Employee transferred</strong>
                    <div class="md-body-sm">Internal or external movement</div>
                </div>
            </a>
            @if (\App\Services\FeatureToggleService::enabled('grade_progression'))
                <a class="action-card" href="{{ route('employee-grades.create', $employee) }}">
                    <span>🎖</span>
                    <div>
                        <strong>Promotion / grade change</strong>
                        <div class="md-body-sm">Preserve grade service date</div>
                    </div>
                </a>
            @endif
            @if (\App\Services\FeatureToggleService::enabled('service_history'))
                <a class="action-card" href="{{ route('employee-service-periods.create', $employee) }}">
                    <span>📂</span>
                    <div>
                        <strong>Add previous service</strong>
                        <div class="md-body-sm">Institution, posting or attachment</div>
                    </div>
                </a>
            @endif
            <a class="action-card" href="{{ route('acting-appointments.create', ['employee_id' => $employee->id]) }}">
                <span>👤</span>
                <div>
                    <strong>Acting appointment</strong>
                    <div class="md-body-sm">Temporary responsibility</div>
                </div>
            </a>
            @if (\App\Services\FeatureToggleService::enabled('service_letters'))
                <a class="action-card" href="{{ route('service-letters.create', ['employee_id' => $employee->id]) }}">
                    <span>✉️</span>
                    <div>
                        <strong>Create service letter</strong>
                        <div class="md-body-sm">Multilingual template workflow</div>
                    </div>
                </a>
            @endif
            <a class="action-card" href="{{ route('retirement-projects.index') }}">
                <span>🌿</span>
                <div>
                    <strong>Retirement preparation</strong>
                    <div class="md-body-sm">Review retirement readiness</div>
                </div>
            </a>
        </div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin:0 0 16px">
        <a class="md-btn md-btn--ghost" href="{{ route('employee-service-periods.index', $employee) }}">📂 Service
            History</a>
        <a class="md-btn md-btn--ghost" href="{{ route('employee-grades.index', $employee) }}">🎖️ Grade & Promotion</a>
        <a class="md-btn md-btn--ghost" href="{{ route('employee-documents.index', $employee) }}">Documents
            ({{ $employee->documents->count() }})</a>
        <a class="md-btn md-btn--ghost" href="{{ route('employee-training.index', $employee) }}">Training</a>
        <a class="md-btn md-btn--ghost" href="{{ route('employee-competencies.index', $employee) }}">Competencies</a>
        <a class="md-btn md-btn--ghost" href="{{ route('employee-qualifications.index', $employee) }}">Qualifications</a>
        <a class="md-btn md-btn--ghost" href="{{ route('employee-exam-records.index', $employee) }}">Exams</a>
        <a class="md-btn md-btn--ghost" href="{{ route('employee-leave-records.index', $employee) }}">Leave</a>
        <a class="md-btn md-btn--ghost" href="{{ route('employee-interdictions.index', $employee) }}">Interdictions</a>
        <a class="md-btn md-btn--text" href="{{ route('employee-increments.index', $employee) }}">Increment history</a>
    </div>
    <div style="display:grid;grid-template-columns:minmax(0,1.35fr) minmax(320px,.65fr);gap:16px;align-items:start">
        <section class="workforce-panel">
            <div class="panel-title-row">
                <div>
                    <div class="panel-title">Service & lifecycle timeline</div>
                    <div class="panel-subtitle">Chronological employee history.</div>
                </div>
            </div>
            <div style="display:grid;gap:0;margin-top:16px">
                @forelse($timeline as $item)
                    <div style="display:grid;grid-template-columns:110px 18px 1fr;gap:10px;min-height:64px">
                        <div class="md-body-sm">{{ $item['date']->format('d M Y') }}</div>
                        <div style="position:relative">
                            <span
                                style="display:block;width:10px;height:10px;border-radius:50%;background:var(--md-primary);margin-top:4px">
                            </span>
                            <span
                                style="position:absolute;left:4px;top:16px;bottom:-6px;border-left:2px solid var(--md-outline-variant)">
                            </span>
                        </div>
                        <div>
                            <strong>{{ $item['title'] }}</strong>
                            @if ($item['details'])
                                <div class="md-body-sm" style="color:var(--md-on-surface-variant)">{{ $item['details'] }}
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <p>No service events recorded yet.</p>
                @endforelse
            </div>
        </section>
        <section class="workforce-panel">
            <div class="panel-title-row">
                <div>
                    <div class="panel-title">Add service event</div>
                    <div class="panel-subtitle">Record a dated lifecycle change with reference.</div>
                </div>
            </div>
            <form method="POST" action="{{ route('employee-lifecycle.store', $employee) }}"
                style="display:grid;gap:10px;margin-top:12px">
                @csrf
                <div>
                    <label class="md-label">Event Type</label>
                    <select class="md-select" name="event_type" required>
                        @foreach (['joined_service', 'active', 'on_leave', 'no_pay_leave', 'temporary_transfer', 'permanent_transfer', 'secondment', 'deputation', 'acting', 'interdicted', 'suspended', 'resigned', 'retired', 'deceased', 'promotion', 'other'] as $s)
                            <option value="{{ $s }}">{{ ucwords(str_replace('_', ' ', $s)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="md-label">Title</label>
                    <input class="md-input" name="title" required maxlength="180">
                </div>
                <div>
                    <label class="md-label">Effective Date</label>
                    <input class="md-input" type="date" name="effective_date" required>
                </div>
                <div>
                    <label class="md-label">End Date</label>
                    <input class="md-input" type="date" name="end_date">
                </div>
                <div>
                    <label class="md-label">Reference</label>
                    <input class="md-input" name="reference_no" maxlength="100">
                </div>
                <div>
                    <label class="md-label">Details</label>
                    <textarea class="md-input" name="details" rows="3">
                                    </textarea>
                </div>
                <button class="md-btn--primary">Add to Timeline</button>
            </form>
        </section>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px;align-items:start">
        <section class="workforce-panel">
            <h2 class="md-title-lg">Recent Documents</h2>
            <div style="display:grid;gap:8px;margin-top:10px">
                @forelse($employee->documents->sortByDesc('document_date')->take(6) as $d)
                    <div class="md-card" style="padding:10px">
                        <strong>{{ $d->title }}</strong>
                        <div class="md-body-sm">{{ ucwords($d->category) }} ·
                            {{ $d->document_date?->format('d M Y') ?? 'Undated' }} @if ($d->expiry_date)
                                · expires {{ $d->expiry_date->format('d M Y') }}
                            @endif
                        </div>
                    </div>
                @empty
                    <p>No employee documents uploaded.</p>
                @endforelse
            </div>
        </section>
        <section class="workforce-panel">
            <h2 class="md-title-lg">Record Change History</h2>
            <div style="display:grid;gap:8px;margin-top:10px">
                @forelse($auditLogs->take(12) as $log)
                    <div class="md-card" style="padding:10px">
                        <strong>{{ ucwords($log->action) }}</strong>
                        <div class="md-body-sm">{{ $log->description }}</div>
                        <div class="md-body-sm">{{ $log->user?->name ?? 'System' }} ·
                            {{ $log->created_at?->format('d M Y H:i') }}</div>
                        @if ($log->old_values || $log->new_values)
                            <details style="margin-top:6px">
                                <summary>Field changes</summary>
                                <div class="md-body-sm" style="margin-top:5px">
                                    @foreach ($log->new_values ?? [] as $key => $value)
                                        @php
                                            $old = ($log->old_values ?? [])[$key] ?? null;
                                        @endphp@if ($old != $value)
                                            <div>
                                                <strong>{{ str_replace('_', ' ', $key) }}:</strong>
                                                {{ is_scalar($old) ? $old : '—' }} →
                                                {{ is_scalar($value) ? $value : '—' }}
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </details>
                        @endif
                    </div>
                @empty
                    <p>No audit entries recorded for this employee.</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection
