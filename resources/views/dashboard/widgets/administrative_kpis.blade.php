<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-label">Pending Decisions</div>
        <div class="kpi-value">{{ number_format($administrative['pending_decisions']) }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Service Letters Pending</div>
        <div class="kpi-value">{{ number_format($administrative['pending_service_letters']) }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Car Pass Approvals</div>
        <div class="kpi-value">{{ number_format($administrative['pending_car_passes']) }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Open Incidents</div>
        <div class="kpi-value">{{ number_format($administrative['open_incidents']) }}</div>
    </div>
</div>
