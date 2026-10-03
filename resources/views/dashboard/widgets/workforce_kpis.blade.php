<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-label">Active Employees</div>
        <div class="kpi-value">{{ number_format($workforce['active_employees']) }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Approved Establishment</div>
        <div class="kpi-value">{{ number_format($workforce['approved']) }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Recorded Workforce</div>
        <div class="kpi-value">{{ number_format($workforce['available']) }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Vacancy Gap</div>
        <div class="kpi-value">{{ number_format($workforce['vacancies']) }}</div>
    </div>
</div>
