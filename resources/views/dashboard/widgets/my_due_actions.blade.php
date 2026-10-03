<div class="dashboard-guidance" style="margin-bottom:12px">
    <strong>Your Action Centre</strong>
    <div class="md-body-sm" style="margin-top:4px">
        Upcoming events show what is happening to employees in your assigned HR scope. Workflow counts show whether an
        administrative case has already been started.
    </div>
</div>

<div class="status-pills" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px">
    <div class="status-pill">
        <strong>{{ number_format($myDueActions['increments_due_30d']) }}</strong>
        <span>Increment Due Within 30 Days</span>
        <small>Employee increment dates approaching within your assigned scope.</small>
    </div>

    <div class="status-pill">
        <strong>{{ number_format($myDueActions['increments_overdue']) }}</strong>
        <span>Increment Actions Overdue</span>
        <small>Past increment dates without a granted, deferred or withheld outcome.</small>
    </div>

    <div class="status-pill">
        <strong>{{ number_format($myDueActions['employees_retiring_12m']) }}</strong>
        <span>Employees Retiring Within 12 Months</span>
        <small>Calculated from employee date of birth and retirement age.</small>
    </div>

    <div class="status-pill">
        <strong>{{ number_format($myDueActions['retirement_cases_not_opened']) }}</strong>
        <span>Retirement Cases Not Yet Opened</span>
        <small>Retiring employees who do not yet have a retirement workflow case.</small>
    </div>

    <div class="status-pill">
        <strong>{{ number_format($myDueActions['retirement_cases_in_progress']) }}</strong>
        <span>Retirement Cases In Progress</span>
        <small>Retirement workflow cases already started and not completed.</small>
    </div>
</div>

@if (Route::has('retirement-projects.index'))
    <div style="margin-top:12px">
        <a href="{{ route('retirement-projects.index') }}" class="md-btn md-btn--text md-btn--sm">Review retirement
            cases →</a>
    </div>
@endif
