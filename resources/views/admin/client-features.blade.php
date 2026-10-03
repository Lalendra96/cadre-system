@extends('layouts.app')
@section('title', 'Client Version & Features')
@section('content')
<div style="max-width:1200px;margin:0 auto">
    <div style="margin-bottom:18px">
        <h2 class="md-headline-sm" style="margin:0 0 4px">Client Version & Feature Management</h2>
        <p class="md-body-sm" style="margin:0;color:var(--md-on-surface-variant)">Deployment-level controls only. This role does not grant access to HR records, payroll data, PII, security administration or operational approvals.</p>
    </div>

    @if(session('success'))<div class="md-card" style="padding:12px 16px;margin-bottom:14px;background:var(--md-success-container);color:var(--md-on-success-container)">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="md-card" style="padding:12px 16px;margin-bottom:14px;background:var(--md-error-container);color:var(--md-on-error-container)"><strong>Configuration not changed.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <form method="POST" action="{{ route('admin.client-features.update') }}">
        @csrf
        <div class="md-card md-card--elevated" style="margin-bottom:16px">
            <div class="md-card__header"><div class="md-title-md">Client deployment profile</div></div>
            <div class="md-card__body">
                <div class="md-form-row">
                    <div class="md-form-group"><label class="md-label">Client / Institution</label><input class="md-input" name="client_name" required value="{{ old('client_name',$profile['client_name']) }}"></div>
                    <div class="md-form-group"><label class="md-label">Edition / Plan</label><input class="md-input" name="edition" required value="{{ old('edition',$profile['edition']) }}" placeholder="Government / Private / GP / Custom"></div>
                </div>
                <div class="md-form-row">
                    <div class="md-form-group"><label class="md-label">Application version</label><input class="md-input" name="application_version" required value="{{ old('application_version',$profile['application_version']) }}" placeholder="v1.0.0"></div>
                    <div class="md-form-group"><label class="md-label">Release channel</label><select class="md-input" name="release_channel">@foreach(['stable'=>'Stable','pilot'=>'Pilot','testing'=>'Testing'] as $v=>$l)<option value="{{ $v }}" @selected(old('release_channel',$profile['release_channel'])===$v)>{{ $l }}</option>@endforeach</select></div>
                </div>
            </div>
        </div>

        <div class="md-card md-card--elevated" style="margin-bottom:16px">
            <div class="md-card__header"><div><div class="md-title-md">Client-manageable features</div><div class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:4px">Disabled modules keep historical records intact. Payroll is intentionally not available here.</div></div></div>
            <div class="md-card__body" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(290px,1fr));gap:10px">
                @foreach($features as $feature)
                    <label class="md-card" style="padding:14px;display:flex;gap:12px;align-items:flex-start">
                        <input type="checkbox" name="features[{{ $feature['id'] }}]" value="1" @checked(old('features.'.$feature['id'],$feature['enabled']))>
                        <div><strong>{{ $feature['label'] }}</strong><div class="md-body-sm" style="color:var(--md-on-surface-variant)">{{ $feature['enabled'] ? 'Currently enabled' : 'Currently disabled' }}</div>@if(!empty($feature['governance_note']))<div class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:5px">{{ $feature['governance_note'] }}</div>@endif</div>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="md-card md-card--elevated" style="margin-bottom:16px;border-left:4px solid var(--md-primary)">
            <div class="md-card__body">
                <div class="md-title-sm">Payroll governance boundary</div>
                <p class="md-body-sm" style="color:var(--md-on-surface-variant)">Internal Payroll is disabled by default because the authoritative payroll process is handled by a separate Sri Lankan Government-approved system. The Client Version & Feature Manager cannot enable it. Any future exception must be made by Super Admin with a recorded governance reason.</p>
            </div>
        </div>

        <div class="md-card md-card--elevated" style="margin-bottom:16px;border-left:4px solid var(--md-tertiary)">
            <div class="md-card__body">
                <div class="md-title-sm">Government leave-process boundary</div>
                <p class="md-body-sm" style="color:var(--md-on-surface-variant)">Digital Leave Management starts disabled. In government deployments the authorised paper-based leave process remains official unless the institution formally adopts the digital workflow. Any client feature change is versioned and requires the mandatory change reason below.</p>
            </div>
        </div>

        <div class="md-card md-card--elevated">
            <div class="md-card__body">
                <div class="md-form-group"><label class="md-label">Change reason *</label><textarea class="md-input" name="change_reason" rows="3" required placeholder="State the approved deployment/version or client feature change and reference where applicable.">{{ old('change_reason') }}</textarea><div class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:5px">Required for auditability and change governance.</div></div>
                <div style="display:flex;justify-content:flex-end;margin-top:12px"><button class="md-btn md-btn--filled">Save Client Configuration</button></div>
            </div>
        </div>
    </form>

    <div class="md-card md-card--elevated" style="margin-top:16px">
        <div class="md-card__header"><div><div class="md-title-md">Configuration history</div><div class="md-body-sm" style="color:var(--md-on-surface-variant)">Immutable deployment/version snapshots retained for traceability.</div></div></div>
        <div class="md-card__body" style="overflow:auto">
            <table class="md-table" style="width:100%">
                <thead><tr><th>Version</th><th>Edition</th><th>Channel</th><th>Changed by</th><th>Reason</th><th>Date</th></tr></thead>
                <tbody>
                    @forelse($history as $row)
                        <tr>
                            <td>{{ $row->application_version }}</td><td>{{ $row->edition }}</td><td>{{ ucfirst($row->release_channel) }}</td>
                            <td>{{ $row->changed_by_name ?: 'User #'.$row->changed_by }}</td><td>{{ $row->change_reason }}</td><td>{{ $row->created_at }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="md-body-sm" style="color:var(--md-on-surface-variant)">No deployment-version changes recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
