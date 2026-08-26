@extends('layouts.app')
@section('title', 'System Settings')

@push('head')
<style>
.setting-row {
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 20px; padding: 14px 0;
    border-bottom: 1px solid var(--md-outline-variant);
}
.setting-row:last-child { border-bottom: none; }
.setting-row__meta { flex: 1; }
.setting-row__control { min-width: 220px; display: flex; justify-content: flex-end; align-items: flex-start; }

/* Three-way radio group */
.verifier-radio-group { display: flex; flex-direction: column; gap: 8px; }
.verifier-radio-option {
    display: flex; align-items: center; gap: 10px; padding: 8px 12px;
    border: 1px solid var(--md-outline-variant); border-radius: var(--md-shape-sm);
    cursor: pointer; transition: border-color .15s, background .15s;
}
.verifier-radio-option:has(input:checked) {
    border-color: var(--md-primary);
    background: color-mix(in srgb, var(--md-primary) 8%, transparent);
}
.verifier-radio-option input[type="radio"] { flex-shrink: 0; }
.verifier-radio-option__label { font-size: 13px; font-weight: 500; }
.verifier-radio-option__desc  { font-size: 11px; color: var(--md-on-surface-variant); margin-top: 1px; }

/* Category picker — shown only when 'category' is selected */
#categoryPickerWrap {
    margin-top: 10px; padding: 12px;
    background: var(--md-surface-container);
    border-radius: var(--md-shape-sm);
    border: 1px solid var(--md-outline-variant);
    display: none;
}
#categoryPickerWrap.visible { display: block; }
</style>
@endpush

@section('content')
<h2 class="md-headline-sm" style="margin-bottom:4px;">System Settings</h2>
<p class="md-body-sm" style="color:var(--md-on-surface-variant);margin-bottom:20px;">
    Configure system-wide behaviour. Changes take effect immediately.
</p>

<form method="POST" action="{{ route('admin.settings.update') }}">
    @csrf

    @if(session('success'))
        <div style="background:var(--md-success-container);color:var(--md-on-success-container);
                    padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
            {{ session('success') }}
        </div>
    @endif

    <div style="display:flex;flex-direction:column;gap:20px;">

        {{-- ── Submission Deadlines ─────────────────────────────────────────── --}}
        <div class="md-card md-card--elevated">
            <div class="md-card__header"><span class="md-title-md">📅 Submission Deadlines</span></div>
            <div class="md-card__body">
                <div class="setting-row">
                    <div class="setting-row__meta">
                        <div class="md-label-md">Enforce deadlines</div>
                        <div class="md-body-sm" style="color:var(--md-on-surface-variant);">When enabled, monthly entries are locked once the deadline passes.</div>
                    </div>
                    <div class="setting-row__control">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="checkbox" name="deadline_enforcement_enabled" value="1"
                                   {{ \App\Models\SystemSetting::getBool('deadline_enforcement_enabled', true) ? 'checked' : '' }}>
                            <span class="md-label-md">Enabled</span>
                        </label>
                    </div>
                </div>
                <div class="setting-row">
                    <div class="setting-row__meta">
                        <div class="md-label-md">Due day</div>
                        <div class="md-body-sm" style="color:var(--md-on-surface-variant);">Day of the <em>following</em> month by which the previous month's entries must be submitted (1–28).</div>
                    </div>
                    <div class="setting-row__control">
                        <input type="number" name="deadline_day"
                               class="md-field__input" style="width:90px;text-align:right;"
                               value="{{ \App\Models\SystemSetting::getInt('deadline_day', 15) }}"
                               min="1" max="28">
                    </div>
                </div>
                <div class="setting-row">
                    <div class="setting-row__meta">
                        <div class="md-label-md">Grace period (days)</div>
                        <div class="md-body-sm" style="color:var(--md-on-surface-variant);">Extra days after the deadline before entries are hard-locked.</div>
                    </div>
                    <div class="setting-row__control">
                        <input type="number" name="deadline_grace_days"
                               class="md-field__input" style="width:90px;text-align:right;"
                               value="{{ \App\Models\SystemSetting::getInt('deadline_grace_days', 0) }}"
                               min="0" max="14">
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Verification Layer ───────────────────────────────────────────── --}}
        <div class="md-card md-card--elevated">
            <div class="md-card__header"><span class="md-title-md">✅ Entry Verification Layer</span></div>
            <div class="md-card__body">

                <div class="setting-row">
                    <div class="setting-row__meta">
                        <div class="md-label-md">Require verification before entries appear in reports</div>
                        <div class="md-body-sm" style="color:var(--md-on-surface-variant);">When enabled, submitted entries stay in a pending state until approved by the designated verifier.</div>
                    </div>
                    <div class="setting-row__control">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="checkbox" name="entry_verification_required" value="1"
                                   {{ \App\Models\SystemSetting::getBool('entry_verification_required') ? 'checked' : '' }}>
                            <span class="md-label-md">Enabled</span>
                        </label>
                    </div>
                </div>

                {{-- Verifier role — three-way picker ──────────────────────── --}}
                <div class="setting-row" style="flex-direction:column;align-items:stretch;">
                    <div style="margin-bottom:12px;">
                        <div class="md-label-md">Who can verify entries?</div>
                        <div class="md-body-sm" style="color:var(--md-on-surface-variant);">Super Admin can always verify regardless of this setting.</div>
                    </div>

                    @php($currentRole = \App\Models\SystemSetting::get('entry_verifier_role', 'planning_officer'))

                    <div class="verifier-radio-group">

                        <label class="verifier-radio-option">
                            <input type="radio" name="entry_verifier_role" value="planning_officer"
                                   {{ $currentRole === 'planning_officer' ? 'checked' : '' }}
                                   onchange="onVerifierRoleChange(this)">
                            <div>
                                <div class="verifier-radio-option__label">Planning Officer</div>
                                <div class="verifier-radio-option__desc">Any user with the Planning Officer role can verify entries.</div>
                            </div>
                        </label>

                        <label class="verifier-radio-option">
                            <input type="radio" name="entry_verifier_role" value="super_admin"
                                   {{ $currentRole === 'super_admin' ? 'checked' : '' }}
                                   onchange="onVerifierRoleChange(this)">
                            <div>
                                <div class="verifier-radio-option__label">Super Admin only</div>
                                <div class="verifier-radio-option__desc">Only the Super Admin account can verify. Highest security, least flexible.</div>
                            </div>
                        </label>

                        <label class="verifier-radio-option">
                            <input type="radio" name="entry_verifier_role" value="category"
                                   {{ $currentRole === 'category' ? 'checked' : '' }}
                                   onchange="onVerifierRoleChange(this)">
                            <div>
                                <div class="verifier-radio-option__label">Specific User Category / Categories</div>
                                <div class="verifier-radio-option__desc">Admin Group users assigned any of the ticked categories can verify entries (e.g. Chief Clerk, Medical Officer Planning).</div>
                            </div>
                        </label>

                    </div>

                    {{-- Multi-category picker — visible when 'category' selected ── --}}
                    @php($currentCatIds = \App\Models\SystemSetting::getJson('entry_verifier_category_ids', []))
                    <div id="categoryPickerWrap" class="{{ $currentRole === 'category' ? 'visible' : '' }}">
                        <div class="md-label-md" style="margin-bottom:8px;">
                            Select verifier categories <span style="color:var(--md-error);">*</span>
                        </div>
                        <div style="display:flex;flex-direction:column;gap:6px;">
                            @foreach($verifierCategories as $cat)
                                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:8px 12px;
                                              border:1px solid var(--md-outline-variant);border-radius:var(--md-shape-sm);
                                              background:{{ in_array($cat->id, (array)$currentCatIds) ? 'color-mix(in srgb, var(--md-primary) 8%, transparent)' : 'transparent' }};"
                                       id="catLabel{{ $cat->id }}">
                                    <input type="checkbox"
                                           name="entry_verifier_category_ids[]"
                                           value="{{ $cat->id }}"
                                           {{ in_array($cat->id, (array)$currentCatIds) ? 'checked' : '' }}
                                           onchange="highlightCatLabel(this)">
                                    <div>
                                        <div class="md-label-md">{{ $cat->name }}</div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        <div class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:8px;">
                            A user matching ANY ticked category can verify. Super Admin always has access.
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- ── Vacancy Alerts ───────────────────────────────────────────────── --}}
        <div class="md-card md-card--elevated">
            <div class="md-card__header"><span class="md-title-md">🔔 Vacancy Alerts</span></div>
            <div class="md-card__body">
                <div class="setting-row">
                    <div class="setting-row__meta">
                        <div class="md-label-md">Enable vacancy alerts</div>
                        <div class="md-body-sm" style="color:var(--md-on-surface-variant);">Show an alert banner on dashboards when vacancies exceed the threshold.</div>
                    </div>
                    <div class="setting-row__control">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="checkbox" name="vacancy_alert_enabled" value="1"
                                   {{ \App\Models\SystemSetting::getBool('vacancy_alert_enabled', true) ? 'checked' : '' }}>
                            <span class="md-label-md">Enabled</span>
                        </label>
                    </div>
                </div>
                <div class="setting-row">
                    <div class="setting-row__meta">
                        <div class="md-label-md">Alert threshold (%)</div>
                        <div class="md-body-sm" style="color:var(--md-on-surface-variant);">Alert fires when a position's vacancy exceeds this percentage of its approved cadre.</div>
                    </div>
                    <div class="setting-row__control">
                        <div style="display:flex;align-items:center;gap:6px;">
                            <input type="number" name="vacancy_alert_threshold_pct"
                                   class="md-field__input" style="width:80px;text-align:right;"
                                   value="{{ \App\Models\SystemSetting::getInt('vacancy_alert_threshold_pct', 20) }}"
                                   min="1" max="100">
                            <span class="md-body-sm" style="color:var(--md-on-surface-variant);">%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Security ─────────────────────────────────────────────────────── --}}
        <div class="md-card md-card--elevated">
            <div class="md-card__header"><span class="md-title-md">🔒 Security Controls</span></div>
            <div class="md-card__body">
                @foreach([
                    ['key'=>'concurrent_session_lock_enabled', 'label'=>'Prevent concurrent sessions',
                     'desc'=>'Logging in from a new browser invalidates all previous sessions for the same account.'],
                    ['key'=>'ip_allowlist_enabled', 'label'=>'Enforce IP allowlist',
                     'desc'=>'Only allow access from IP ranges listed in Admin → IP Allowlist. Ensure your IP is listed before enabling.'],
                    ['key'=>'export_audit_enabled', 'label'=>'Audit data exports & downloads',
                     'desc'=>'Log every file download and data export with user, timestamp, and IP address.'],
                    ['key'=>'login_show_username', 'label'=>'Show username/email field on login',
                     'desc'=>'When disabled, login shows a User Group/Category dropdown instead of a typed username, then a dropdown to pick the specific account. Defaults to enabled (normal email login).'],
                ] as $s)
                <div class="setting-row">
                    <div class="setting-row__meta">
                        <div class="md-label-md">{{ $s['label'] }}</div>
                        <div class="md-body-sm" style="color:var(--md-on-surface-variant);">{{ $s['desc'] }}</div>
                    </div>
                    <div class="setting-row__control">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="checkbox" name="{{ $s['key'] }}" value="1"
                                   {{ \App\Models\SystemSetting::getBool($s['key']) ? 'checked' : '' }}>
                            <span class="md-label-md">{{ \App\Models\SystemSetting::getBool($s['key']) ? 'Enabled' : 'Disabled' }}</span>
                        </label>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:10px;">
            <button type="submit" class="md-btn md-btn--filled">Save All Settings</button>
        </div>

    </div>
</form>
@endsection

@push('scripts')
<script>
function onVerifierRoleChange(radio) {
    var wrap = document.getElementById('categoryPickerWrap');
    wrap.classList.toggle('visible', radio.value === 'category');
}

function highlightCatLabel(cb) {
    var label = document.getElementById('catLabel' + cb.value);
    if (!label) return;
    label.style.background = cb.checked
        ? 'color-mix(in srgb, var(--md-primary) 8%, transparent)'
        : 'transparent';
}

(function () {
    var checked = document.querySelector('input[name="entry_verifier_role"]:checked');
    if (checked && checked.value === 'category') {
        document.getElementById('categoryPickerWrap').classList.add('visible');
    }
})();
</script>
@endpush
