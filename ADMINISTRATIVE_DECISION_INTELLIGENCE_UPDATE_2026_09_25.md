# Administrative Decision Intelligence Update — 2026-09-25

## Purpose
Improve Admin Group decision support by surfacing existing workforce, governance and financial-control capabilities in the dashboard and sidebar, while preserving aggregate-first privacy controls and existing role boundaries.

## Dashboard additions
The Hospital Secretary / Administrative Officer dashboard now includes:

- Administrative Workforce Intelligence panel
  - Open reconciliation issues
  - Open HR escalations
  - Recruitment vacancies in progress
  - Incomplete retirement cases
- Governance Intelligence Pulse
  - Official reports awaiting completion
  - High/critical incidents
  - Business rules due review
  - Open data-quality findings
- Utility Payment Control
  - Overdue bills
  - Bills due within 7 days
  - Outstanding amount
  - Current-month payments
- Administrative Decision Workspaces quick-access panel
  - Administrative Intelligence
  - Workforce & Application Health
  - HR Responsibility Intelligence
  - Enterprise Workforce Intelligence
  - Governance Control
  - Official Reports
  - Utility Bill Monitoring
  - Scenario Comparison

## Administrative Intelligence enhancement
The Administrative Intelligence screen now provides:

- Year / projection-horizon / recruitment / salary-impact scenario inputs
- Approved establishment, active workforce, current gap and fill rate
- Retirement and recruitment scenario projections
- Workforce capacity chart
- Governance & operational risk chart
- Administrative attention queue ranked by open-item count
- Recruitment, retirement, increment, official-report and utility-control context
- Direct links to governed decision workspaces

## Sidebar / navigation corrections
Navigation now exposes capabilities that were available by route but not consistently visible:

- Administrative Intelligence for the full Admin Group (not only executive categories)
- Enterprise Workforce Intelligence
- Governance Control Centre
- Official Reports & Signed Snapshots
- Workforce & Application Health for all route-authorised roles
- Utility Bill Administration for Super Admin

## Utility Bill integration correction
The earlier Utility Bill module files existed, but the module had two integration gaps. This update adds:

- Utility Bill routes
- `utility_bills` registration in `FeatureToggleService`
- Route visibility tied to the feature toggle
- Super Admin administration route remains accessible when the operational module is disabled, matching the Car Pass governance pattern

## Security / governance behavior
- Executive dashboard cards remain aggregate-first and do not list employee identities.
- Sensitive employee records remain behind their existing role/category checks.
- Consequential decisions remain in governed human approval workflows.
- Utility bill write access remains limited to the assigned Subject Officer or Super Admin.
- Admin Group utility access remains monitoring / analytics oriented.

## Deployment
Run:

```bash
php artisan migrate
php artisan optimize:clear
```

If navigation is intentionally reseeded in your environment, the updated `NavItemSeeder` preserves the new visibility rules:

```bash
php artisan db:seed --class=NavItemSeeder
```

No new npm dependency is required. Charts use the existing offline Chart.js asset under `public/vendor/chartjs/`.
