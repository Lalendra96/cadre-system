# Role Dashboard Professionalisation — 2026-09-20

This update adds dedicated, self-explanatory professional dashboards for:

- Director
- Deputy Director / Deputy Director General
- Hospital Secretary / Administrative Officer
- Medical Officer Planning
- Planning Officer

## Design direction

The dashboards follow the approved navy/blue institutional design direction and use the existing shared application shell. The visual hierarchy is based on professional workforce/cadre-management interfaces: compact KPI cards, priority/action panels, governed workflow links, aggregate tables, planning charts, quick actions and strong status signalling.

## Governance and legal safeguards

Each dashboard includes a visible internal administrative decision-support notice and reinforces that:

- system calculations and projections are indicators rather than determinations;
- final consequential HR decisions require authorised human review;
- relevant official records and applicable Government of Sri Lanka authorities must be verified before action;
- AI assistance remains advisory-only;
- business-rule source verification, audit activity and incident/correction workflows are visible;
- management dashboards remain aggregate-first and do not expose employee names, NICs or direct profile links merely for executive convenience.

This preserves the existing Admin Group employee-profile privacy boundary.

## Branding

The application now uses the supplied files from `public/images/branding/`:

- `sri-lanka-emblem.png`
- `hospital-logo.png`
- `sri-lanka-flag.png`

The earlier SVG placeholder files are retained as fallback assets only and are not used by the main application shell.

## Files added/updated

- `app/Http/Controllers/DashboardController.php`
- `resources/views/dashboard/director.blade.php`
- `resources/views/dashboard/deputy-director.blade.php`
- `resources/views/dashboard/administrative-officer.blade.php`
- `resources/views/dashboard/medical-officer-planning.blade.php`
- `resources/views/dashboard/planning-officer.blade.php`
- `resources/views/dashboard/_decision-support-notice.blade.php`
- `resources/views/dashboard/_role-header.blade.php`
- `resources/views/dashboard/_privacy-note.blade.php`
- `public/css/carder-professional.css`
- `resources/views/layouts/app.blade.php`

No employee-allocation, Subject Officer scope, Admin Group privacy, decision-approval, or incident-governance rules were intentionally broadened by this visual/dashboard update.
