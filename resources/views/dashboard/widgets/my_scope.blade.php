<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-label">Assigned Positions</div>
        <div class="kpi-value">{{ number_format($myScope['positions']) }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Employees in Scope</div>
        <div class="kpi-value">{{ number_format($myScope['employees']) }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Open Data Issues</div>
        <div class="kpi-value">{{ number_format($myScope['open_quality']) }}</div>
    </div>
</div>
