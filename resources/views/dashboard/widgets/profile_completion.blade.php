@php
    $profileSummary = $profileCompletion ?? [
        'handling_count' => 0,
        'profile_count' => 0,
        'remaining_count' => 0,
        'completion_pct' => null,
    ];
@endphp

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-label">Handling Count</div>
        <div class="kpi-value">{{ number_format($profileSummary['handling_count']) }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Current Profiles</div>
        <div class="kpi-value">{{ number_format($profileSummary['profile_count']) }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Remaining</div>
        <div class="kpi-value">{{ number_format($profileSummary['remaining_count']) }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Completion</div>
        <div class="kpi-value">
            {{ $profileSummary['completion_pct'] === null ? '—' : number_format($profileSummary['completion_pct'], 1) . '%' }}
        </div>
    </div>
</div>

<div style="margin-top:12px;display:grid;gap:8px;">
    <div class="metric-row">
        <span>Profile count progress</span>
        <div class="metric-track">
            <div class="metric-fill" style="width:{{ $profileSummary['completion_pct'] ?? 0 }}%"></div>
        </div>
        <span class="metric-value">
            {{ $profileSummary['completion_pct'] === null ? '—' : number_format($profileSummary['completion_pct'], 1) . '%' }}
        </span>
    </div>
</div>

<div style="margin-top:12px;display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap;">
    <small style="color:var(--md-on-surface-variant,#667a8e);">
        {{ number_format($profileSummary['remaining_count']) }} profile(s) remaining against the configured handling
        count.
    </small>
    <a href="{{ route('employee-profile-completion.index') }}" class="dashboard-btn">Open Tracker</a>
</div>
