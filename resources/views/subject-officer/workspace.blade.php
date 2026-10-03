@extends('layouts.app')
@section('title', 'My Workspace')
@section('content')
    <div class="page-header">
        <div>
            <div class="page-eyebrow">Subject Officer Workspace</div>
            <h1 class="page-title">Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }},
                {{ explode(' ', auth()->user()->name)[0] }}</h1>
            <p class="page-subtitle">A focused view of your allocated employees, grade progression, monthly Carder,
                retirements and data-quality actions.
            <div style="margin-top:10px;display:flex;gap:6px;flex-wrap:wrap;">
                <span class="md-chip md-chip--selected">HR Responsibility</span>
                @foreach ($hrPositions as $p)
                    <span class="md-chip">{{ $p->title }}</span>
                @endforeach
            </div>
            </p>
        </div>
        <div class="page-actions">
            <a class="md-btn md-btn--outlined" href="{{ route('workforce.action-center') }}">Action Centre</a>
            <a class="md-btn md-btn--filled" href="{{ route('carder-entries.index') }}">Open Monthly Carder</a>
        </div>
    </div>
    <div class="workforce-kpi-grid">
        <div class="workforce-kpi">
            <div class="workforce-kpi__label">My Employees</div>
            <div class="workforce-kpi__value">{{ $employees->count() }}</div>
            <a href="{{ route('employees.index') }}">Open directory →</a>
        </div>
        <div class="workforce-kpi">
            <div class="workforce-kpi__label">Data Quality Issues</div>
            <div class="workforce-kpi__value">{{ $qualityIssues }}</div>
            <a href="{{ route('data-quality.index') }}">Review issues →</a>
        </div>
        <div class="workforce-kpi">
            <div class="workforce-kpi__label">Grade Promotions</div>
            <div class="workforce-kpi__value">{{ $gradePromotionEligibleNow }}</div>
            <small>{{ $gradePromotionReminders->count() }} eligible / due within 6 months</small>
        </div>
        <div class="workforce-kpi">
            <div class="workforce-kpi__label">Employees Retiring Within 12 Months</div>
            <div class="workforce-kpi__value">{{ $retirements->count() }}</div>
            <small>{{ $retirementCasesNotOpened }} case{{ $retirementCasesNotOpened == 1 ? '' : 's' }} not opened ·
                {{ $retirementCasesInProgress }} in progress</small>
            <a href="{{ route('retirement-projects.index') }}">Review retirement work →</a>
        </div>
        <div class="workforce-kpi">
            <div class="workforce-kpi__label">Acting Ends ≤ 30 Days</div>
            <div class="workforce-kpi__value">{{ $actingEnding }}</div>
            <small>Appointments requiring attention</small>
        </div>
        <div class="workforce-kpi">
            <div class="workforce-kpi__label">Registration Expiry ≤ 90 Days</div>
            <div class="workforce-kpi__value">{{ $registrationsExpiring->count() }}</div>
            <small>Professional registrations</small>
        </div>
        <div class="workforce-kpi">
            <div class="workforce-kpi__label">Pending Profile Corrections</div>
            <div class="workforce-kpi__value">{{ $pendingCorrections }}</div>
            <small>Employee correction requests</small>
        </div>
        <div class="workforce-kpi">
            <div class="workforce-kpi__label">Service Letters Waiting</div>
            <div class="workforce-kpi__value">{{ $letterStats['pending'] }}</div>
            <small>{{ $letterStats['draft'] }} draft · {{ $letterStats['rejected'] }} returned</small>
        </div>
        <div class="workforce-kpi">
            <div class="workforce-kpi__label">Service History Incomplete</div>
            <div class="workforce-kpi__value">{{ $incompleteServiceHistory }}</div>
            <a href="{{ route('employee-service-periods.overview') }}">Complete records →</a>
        </div>
    </div>
    <div class="section-grid">
        <section class="workforce-panel span-8">
            <div class="panel-title-row">
                <div>
                    <div class="panel-title">Today's priorities</div>
                    <div class="panel-subtitle">Work that may need attention first.</div>
                </div>
                <a href="{{ route('workforce.action-center') }}" class="md-btn md-btn--text md-btn--sm">View all</a>
            </div>
            <div style="display:grid;gap:9px">
                @if ($gradePromotionReminders->count())
                    <a class="priority-item" href="{{ route('employees.index') }}">
                        <span
                            class="priority-dot {{ $gradePromotionEligibleNow ? 'priority-dot--critical' : 'priority-dot--warning' }}">
                        </span>
                        <div>
                            <strong>{{ $gradePromotionEligibleNow }} grade
                                promotion{{ $gradePromotionEligibleNow == 1 ? '' : 's' }} eligible now</strong>
                            <div class="md-body-sm" style="color:var(--md-on-surface-variant)">
                                {{ $gradePromotionReminders->count() }}
                                employee{{ $gradePromotionReminders->count() == 1 ? '' : 's' }} are eligible or approach
                                configured grade eligibility within 6 months. Review Grade History before action.</div>
                        </div>
                    </a>
                @endif
                @if ($qualityIssues)
                    <a class="priority-item" href="{{ route('data-quality.index') }}">
                        <span class="priority-dot priority-dot--warning">
                        </span>
                        <div>
                            <strong>{{ $qualityIssues }} employee data issue{{ $qualityIssues == 1 ? '' : 's' }}</strong>
                            <div class="md-body-sm" style="color:var(--md-on-surface-variant)">Correct incomplete or
                                inconsistent workforce information.</div>
                        </div>
                    </a>
                @endif
                @if ($retirements->count())
                    <a class="priority-item" href="{{ route('retirement-projects.index') }}">
                        <span class="priority-dot priority-dot--warning">
                        </span>
                        <div>
                            <strong>{{ $retirements->count() }} employee{{ $retirements->count() == 1 ? '' : 's' }}
                                retiring within 12 months</strong>
                            <div class="md-body-sm" style="color:var(--md-on-surface-variant)">
                                {{ $retirementCasesNotOpened }} retirement
                                case{{ $retirementCasesNotOpened == 1 ? '' : 's' }} not yet opened;
                                {{ $retirementCasesInProgress }} currently in progress. Verify DOB, service and contact
                                information early.</div>
                        </div>
                    </a>
                @endif
                @if ($registrationsExpiring->count())
                    <a class="priority-item" href="{{ route('employees.index') }}">
                        <span class="priority-dot priority-dot--warning">
                        </span>
                        <div>
                            <strong>{{ $registrationsExpiring->count() }} professional
                                registration{{ $registrationsExpiring->count() == 1 ? '' : 's' }} expire within 90
                                days</strong>
                            <div class="md-body-sm" style="color:var(--md-on-surface-variant)">Confirm renewal details
                                before the expiry date.</div>
                        </div>
                    </a>
                @endif
                @if ($letterStats['rejected'])
                    <a class="priority-item" href="{{ route('service-letters.index') }}">
                        <span class="priority-dot priority-dot--critical">
                        </span>
                        <div>
                            <strong>{{ $letterStats['rejected'] }} service
                                letter{{ $letterStats['rejected'] == 1 ? '' : 's' }} returned</strong>
                            <div class="md-body-sm" style="color:var(--md-on-surface-variant)">Review the rejection reason,
                                correct and resubmit.</div>
                        </div>
                    </a>
                @endif
                @if ($incompleteServiceHistory)
                    <a class="priority-item" href="{{ route('employee-service-periods.overview') }}">
                        <span class="priority-dot priority-dot--warning">
                        </span>
                        <div>
                            <strong>{{ $incompleteServiceHistory }} service history
                                record{{ $incompleteServiceHistory == 1 ? '' : 's' }} incomplete</strong>
                            <div class="md-body-sm" style="color:var(--md-on-surface-variant)">Record previous institutions
                                and preserve combined/public-service continuity before preparing promotion or service
                                letters.</div>
                        </div>
                    </a>
                @endif
                @if ($pendingCorrections)
                    <a class="priority-item" href="{{ route('employees.index') }}">
                        <span class="priority-dot priority-dot--info">
                        </span>
                        <div>
                            <strong>{{ $pendingCorrections }} pending employee correction
                                request{{ $pendingCorrections == 1 ? '' : 's' }}</strong>
                            <div class="md-body-sm" style="color:var(--md-on-surface-variant)">Review profile corrections
                                within your allocated employee scope.</div>
                        </div>
                    </a>
                @endif
                <a class="priority-item" href="{{ route('carder-entries.index') }}">
                    <span class="priority-dot priority-dot--info">
                    </span>
                    <div>
                        <strong>Monthly Carder · {{ now()->format('F Y') }}</strong>
                        <div class="md-body-sm" style="color:var(--md-on-surface-variant)">
                            {{ $currentEntry ? 'Current status: ' . ucwords(str_replace('_', ' ', $currentEntry->status)) : 'No entry recorded for this month yet.' }}
                        </div>
                    </div>
                </a>
                @if (!$gradePromotionReminders->count() && !$qualityIssues && !$retirements->count())
                    <div class="priority-item">
                        <span class="priority-dot priority-dot--success">
                        </span>
                        <div>
                            <strong>No urgent workforce exceptions</strong>
                            <div class="md-body-sm" style="color:var(--md-on-surface-variant)">Your high-priority employee
                                actions are clear.</div>
                        </div>
                    </div>
                @endif
            </div>
        </section>
        <section class="workforce-panel span-4">
            <div class="panel-title-row">
                <div>
                    <div class="panel-title">Upcoming retirements</div>
                    <div class="panel-subtitle">Employees within your scope.</div>
                </div>
            </div>
            <div style="display:grid;gap:4px">
                @forelse($retirements->take(6) as $e)
                    <a href="{{ route('employees.show', $e) }}" class="action-card"
                        style="border:0;border-bottom:1px solid var(--md-outline-variant);border-radius:0;background:transparent;padding-left:2px;padding-right:2px">
                        <div
                            style="width:36px;height:36px;border-radius:10px;background:var(--md-primary-container);color:var(--md-on-primary-container);display:grid;place-items:center;font-weight:800">
                            {{ strtoupper(substr($e->display_name, 0, 1)) }}</div>
                        <div style="min-width:0">
                            <strong>{{ $e->display_name }}</strong>
                            <div class="md-body-sm" style="color:var(--md-on-surface-variant)">
                                {{ $e->position?->title ?? 'No position' }}</div>
                            <div class="md-body-sm" style="color:var(--md-primary);margin-top:2px">
                                {{ $e->retire_date->format('d M Y') }}</div>
                        </div>
                    </a>
                @empty
                    <div class="empty-state">No retirement due within 12 months.</div>
                @endforelse
            </div>
        </section>
        <section class="workforce-panel span-6">
            <div class="panel-title-row">
                <div>
                    <div class="panel-title">My workload by position</div>
                    <div class="panel-subtitle">Only employees explicitly allocated to you.</div>
                </div>
            </div>
            <div style="display:grid;gap:9px">
                @forelse($positionWorkload->take(8) as $row)
                    <div
                        style="display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;align-items:center;padding:10px 0;border-bottom:1px solid var(--md-outline-variant)">
                        <div>
                            <strong>{{ $row->position }}</strong>
                            <div class="md-body-sm" style="color:var(--md-on-surface-variant)">Allocated employee profiles
                            </div>
                        </div>
                        <span class="md-badge">{{ $row->count }}</span>
                    </div>
                @empty
                    <div class="empty-state">No employee allocations are currently assigned to you.</div>
                @endforelse
            </div>
        </section>
        <section class="workforce-panel span-6">
            <div class="panel-title-row">
                <div>
                    <div class="panel-title">Service & compliance watch</div>
                    <div class="panel-subtitle">Upcoming employee-profile items that benefit from early action.</div>
                </div>
            </div>
            <div style="display:grid;gap:8px">
                <a class="action-card" href="{{ route('employees.index') }}">
                    <span style="font-size:20px">🎖️</span>
                    <div>
                        <strong>Grade promotion watch</strong>
                        <div class="md-body-sm">{{ $gradePromotionEligibleNow }} eligible now ·
                            {{ $gradePromotionReminders->count() }} eligible/due within 6 months</div>
                    </div>
                </a>
                <a class="action-card" href="{{ route('service-letters.index') }}">
                    <span style="font-size:20px">✉️</span>
                    <div>
                        <strong>Service letters</strong>
                        <div class="md-body-sm">{{ $letterStats['pending'] }} waiting approval ·
                            {{ $letterStats['draft'] }} draft · {{ $letterStats['rejected'] }} returned</div>
                    </div>
                </a>
                <a class="action-card" href="{{ route('employees.index') }}">
                    <span style="font-size:20px">🪪</span>
                    <div>
                        <strong>Registration renewal watch</strong>
                        <div class="md-body-sm">{{ $registrationsExpiring->count() }} expire within 90 days</div>
                    </div>
                </a>
                <a class="action-card" href="{{ route('employees.index') }}">
                    <span style="font-size:20px">✅</span>
                    <div>
                        <strong>Confirmation status</strong>
                        <div class="md-body-sm">{{ $unconfirmed }} allocated employees currently marked unconfirmed</div>
                    </div>
                </a>
                <a class="action-card" href="{{ route('transfer-records.index') }}">
                    <span style="font-size:20px">🔄</span>
                    <div>
                        <strong>Recent movement activity</strong>
                        <div class="md-body-sm">{{ $recentTransfers }} transfer records added in the last 30 days</div>
                    </div>
                </a>
            </div>
        </section>
        <section class="workforce-panel span-12">
            <div class="panel-title-row">
                <div>
                    <div class="panel-title">Quick actions</div>
                    <div class="panel-subtitle">Frequently used Subject Officer tasks.</div>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px">
                <a class="action-card" href="{{ route('employees.index') }}">
                    <span style="font-size:20px">👥</span>
                    <div>
                        <strong>My Employees</strong>
                        <div class="md-body-sm">Search your allocated Employee 360 profiles</div>
                    </div>
                </a>
                @if (\App\Services\FeatureToggleService::enabled('service_history'))
                    <a class="action-card" href="{{ route('employee-service-periods.overview') }}">
                        <span style="font-size:20px">📂</span>
                        <div>
                            <strong>Service History</strong>
                            <div class="md-body-sm">Previous institutions, postings and career continuity</div>
                        </div>
                    </a>
                @endif
                <a class="action-card" href="{{ route('incoming-officers.create') }}">
                    <span style="font-size:20px">🏛</span>
                    <div>
                        <strong>Incoming Officer</strong>
                        <div class="md-body-sm">Guided registration from another agency/institution</div>
                    </div>
                </a>
                <a class="action-card" href="{{ route('data-quality.index') }}">
                    <span style="font-size:20px">🛡️</span>
                    <div>
                        <strong>Data Quality</strong>
                        <div class="md-body-sm">Correct employee issues</div>
                    </div>
                </a>
                <a class="action-card" href="{{ route('employees.index') }}">
                    <span style="font-size:20px">🎖️</span>
                    <div>
                        <strong>Grade & Promotion</strong>
                        <div class="md-body-sm">Review grade progression for allocated employees</div>
                    </div>
                </a>
                <a class="action-card" href="{{ route('retirement-projects.index') }}">
                    <span style="font-size:20px">🌿</span>
                    <div>
                        <strong>Retirements</strong>
                        <div class="md-body-sm">Verify upcoming cases</div>
                    </div>
                </a>
                @if (\App\Services\FeatureToggleService::enabled('service_letters'))
                    <a class="action-card" href="{{ route('service-letters.create') }}">
                        <span style="font-size:20px">✉️</span>
                        <div>
                            <strong>Draft Service Letter</strong>
                            <div class="md-body-sm">Create from the multilingual template library</div>
                        </div>
                    </a>
                @endif
                <a class="action-card" href="{{ route('transfer-records.create') }}">
                    <span style="font-size:20px">🔄</span>
                    <div>
                        <strong>Record Transfer</strong>
                        <div class="md-body-sm">Record movement for an allocated employee</div>
                    </div>
                </a>
                <a class="action-card" href="{{ route('acting-appointments.create') }}">
                    <span style="font-size:20px">👤</span>
                    <div>
                        <strong>Acting Appointment</strong>
                        <div class="md-body-sm">Record acting responsibility</div>
                    </div>
                </a>
            </div>
        </section>
    </div>
@endsection
