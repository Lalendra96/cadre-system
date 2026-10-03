@extends('layouts.app')
@section('title', 'Workforce Module Configuration')

@push('head')
    <style>
        .wf-config-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 12px;
        }

        .wf-config-card {
            border: 1px solid var(--md-outline-variant);
            border-radius: var(--md-shape-md);
            background: var(--md-surface-container-low);
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            min-height: 155px;
        }

        .wf-config-card__head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .wf-config-card__dependency {
            color: var(--md-on-surface-variant);
            font-size: 12px;
            line-height: 1.45;
        }

        .wf-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: var(--md-shape-full);
            padding: 5px 9px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .wf-status--enabled {
            background: var(--md-success-container);
            color: var(--md-on-success-container);
        }

        .wf-status--disabled {
            background: var(--md-error-container);
            color: var(--md-on-error-container);
        }

        .wf-config-card__actions {
            margin-top: auto;
            display: flex;
            justify-content: flex-end;
        }

        .wf-rate-table {
            width: 100%;
            border-collapse: collapse;
        }

        .wf-rate-table th,
        .wf-rate-table td {
            padding: 11px 12px;
            border-bottom: 1px solid var(--md-outline-variant);
            text-align: left;
            font-size: 13px;
        }

        .wf-rate-table th {
            color: var(--md-on-surface-variant);
            font-weight: 600;
            background: var(--md-surface-container);
        }

        .wf-rate-table tr:last-child td {
            border-bottom: 0;
        }

        @media (max-width: 720px) {
            .wf-config-card__head {
                flex-direction: column;
            }
        }
    </style>
@endpush

@section('content')
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:18px;">
        <div>
            <h2 class="md-headline-sm" style="margin:0 0 4px;">Workforce Module Configuration</h2>
            <p class="md-body-sm" style="margin:0;color:var(--md-on-surface-variant);">
                Super Admin controls for Workforce modules, dependencies and governance-sensitive payroll configuration.
            </p>
        </div>
        <a href="{{ route('admin.features') }}" class="md-btn md-btn--outlined">Feature Management</a>
    </div>

    @if (session('success'))
        <div style="background:var(--md-success-container);color:var(--md-on-success-container);padding:12px 16px;border-radius:var(--md-shape-sm);margin-bottom:14px;">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div style="background:var(--md-error-container);color:var(--md-on-error-container);padding:12px 16px;border-radius:var(--md-shape-sm);margin-bottom:14px;">
            <strong>Configuration not changed.</strong>
            <ul style="margin:6px 0 0 18px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="md-card md-card--elevated" style="margin-bottom:16px;">
        <div class="md-card__body" style="display:flex;gap:12px;align-items:flex-start;">
            <span aria-hidden="true" style="font-size:22px;line-height:1;">🛡️</span>
            <div>
                <div class="md-label-md">Safe module disablement</div>
                <div class="md-body-sm" style="margin-top:4px;color:var(--md-on-surface-variant);">
                    Disabling a Workforce module removes operational access and navigation but does not delete historical HR,
                    payroll, roster or audit records. Dependency rules prevent disabling a module that an enabled module still requires.
                </div>
            </div>
        </div>
    </div>

    <div class="md-card md-card--elevated">
        <div class="md-card__header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
            <div>
                <div class="md-title-md">Workforce Modules</div>
                <div class="md-body-sm" style="margin-top:3px;color:var(--md-on-surface-variant);">
                    Enable or disable each module independently where dependencies permit.
                </div>
            </div>
            <span class="md-body-sm" style="color:var(--md-on-surface-variant);">{{ count($features) }} modules configured</span>
        </div>
        <div class="md-card__body">
            <div class="wf-config-grid">
                @foreach ($features as $key => $feature)
                    <div class="wf-config-card">
                        <div class="wf-config-card__head">
                            <div>
                                <div class="md-title-sm">{{ $feature['label'] }}</div>
                                <div class="wf-config-card__dependency" style="margin-top:5px;">
                                    <strong>Dependency:</strong>
                                    {{ count($feature['dependencies']) ? implode(', ', $feature['dependencies']) : 'None' }}
                                </div>
                            </div>
                            <span class="wf-status {{ $feature['enabled'] ? 'wf-status--enabled' : 'wf-status--disabled' }}">
                                {{ $feature['enabled'] ? 'Enabled' : 'Disabled' }}
                            </span>
                        </div>

                        <div class="md-body-sm" style="color:var(--md-on-surface-variant);">
                            @if ($feature['enabled'])
                                Operational routes and navigation are currently available to authorised roles.
                            @else
                                Operational access is blocked; retained records remain available for governance and audit purposes.
                            @endif
                        </div>

                        <div class="wf-config-card__actions">
                            <form method="POST" action="{{ route('workforce.settings.toggle') }}">
                                @csrf
                                <input type="hidden" name="feature" value="{{ $key }}">
                                <input type="hidden" name="enabled" value="{{ $feature['enabled'] ? 0 : 1 }}">
                                @if ($key === 'payroll' && ! $feature['enabled'])
                                    <input class="md-input" style="margin-bottom:8px;min-width:280px" name="payroll_override_reason" placeholder="Governance reason to enable internal payroll" required>
                                @endif
                                @if (! $feature['enabled'] && \App\Services\SectorProfileService::isGovernment() && in_array($key, ['biometric_attendance','leave_automation','contract_lifecycle','locum_pool','demand_forecasting','roster_optimizer'], true))
                                    <input class="md-input" style="margin-bottom:8px;min-width:280px" name="governance_override_reason" placeholder="Governance reason for Government-sector opt-in" required>
                                @endif
                                <button type="submit" class="md-btn {{ $feature['enabled'] ? 'md-btn--outlined' : 'md-btn--filled' }}">
                                    {{ $feature['enabled'] ? 'Disable Module' : 'Enable Module' }}
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="md-card md-card--elevated" style="margin-top:16px;">
        <div class="md-card__header">
            <div class="md-title-md">Statutory Rate Profiles</div>
            <div class="md-body-sm" style="margin-top:3px;color:var(--md-on-surface-variant);">
                Effective-dated payroll parameters. Production payroll must use a validated profile for the relevant period.
            </div>
        </div>
        <div class="md-card__body" style="padding:0;overflow:auto;">
            <table class="wf-rate-table">
                <thead>
                    <tr>
                        <th>Profile</th>
                        <th>Effective From</th>
                        <th>EPF Employee</th>
                        <th>EPF Employer</th>
                        <th>ETF Employer</th>
                        <th>APIT</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rates as $rate)
                        <tr>
                            <td><strong>{{ $rate->name }}</strong></td>
                            <td>{{ $rate->effective_from }}</td>
                            <td>{{ $rate->epf_employee_percent }}%</td>
                            <td>{{ $rate->epf_employer_percent }}%</td>
                            <td>{{ $rate->etf_employer_percent }}%</td>
                            <td><span class="wf-status wf-status--disabled">Validation required</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center;color:var(--md-on-surface-variant);padding:22px;">
                                No statutory profiles have been configured.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="md-card md-card--elevated" style="margin-top:16px;">
        <div class="md-card__header"><div class="md-title-md">Public / Organisation Holiday Calendar</div><div class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:3px">Used by roster warnings and future OT policy evaluation. Dates remain configurable rather than hard-coded.</div></div>
        <div class="md-card__body">
            <form method="POST" action="{{ route('workforce.settings.holidays.store') }}" style="display:grid;grid-template-columns:180px 1fr 180px auto;gap:10px;align-items:end">@csrf
                <label class="md-body-sm">Date<input class="md-input" type="date" name="holiday_date" required></label>
                <label class="md-body-sm">Name<input class="md-input" name="name" maxlength="150" required></label>
                <label class="md-body-sm">Type<select class="md-input" name="holiday_type"><option value="public">Public</option><option value="mercantile">Mercantile</option><option value="organisation">Organisation</option></select></label>
                <button class="md-btn md-btn--filled">Save</button>
            </form>
            <div style="overflow:auto;margin-top:14px"><table class="wf-rate-table"><thead><tr><th>Date</th><th>Name</th><th>Type</th></tr></thead><tbody>@forelse($holidays as $holiday)<tr><td>{{ $holiday->holiday_date?->format('Y-m-d') }}</td><td>{{ $holiday->name }}</td><td>{{ ucfirst($holiday->holiday_type) }}</td></tr>@empty<tr><td colspan="3">No holidays configured for the current/future period.</td></tr>@endforelse</tbody></table></div>
        </div>
    </div>

@endsection
