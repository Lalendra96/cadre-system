# Role Analytics Dashboard Update — 2026-09-23

## Scope

This update replaces fragmented role landing pages with a consistent role-aware analytics dashboard while preserving the existing governed operational workflows.

## Roles

- Super Admin: institution-wide system/workforce oversight.
- Planning Officer: aggregate establishment, vacancy, retirement and planning intelligence.
- Admin Group: administrative workload, approvals, workflow status and aggregate career events.
- Subject Officer: formally assigned HR scope, assigned intern batches, prepared car passes, letters and due actions.
- Unit Manager: aggregate authorised-unit workforce/events/data-quality indicators.
- Other/general accounts: governance notice only unless another role grants widgets.

## Personal dashboard layout

Users may:

- drag and drop widgets,
- remove widgets,
- add widgets from the role-specific widget catalog,
- save the layout,
- restore the role default.

Only widget keys and their order are stored. Analytics data, employee identifiers, query filters and permission data are not stored in the personal layout table.

## Governance safeguards

1. Dashboard widgets are read-only decision support.
2. Widget availability is calculated server-side from the effective role.
3. Saved layouts are revalidated against the current role catalog every time they are loaded.
4. A stale layout cannot restore a widget that the user no longer has permission to use.
5. Subject Officer metrics are limited to formally assigned HR positions and assigned operational responsibilities.
6. Unit Manager metrics are limited to configured decision-support units.
7. Planning dashboards are aggregate-first and do not expose employee identity in the dashboard.
8. Consequential actions remain in the existing workflow/controller authorization boundaries.
9. Layout updates are audit logged without recording dashboard analytics values.
10. Sidebar navigation search only filters links already rendered after `NavItem::isVisibleTo()` permission checks.

## Sidebar navigation search

A search box is added at the top of the main navigation drawer. It filters permitted navigation labels client-side and temporarily expands matching sections. It does not query routes or reveal hidden navigation entries.

## Migration

Run:

```bash
php artisan migrate
```

Migration:

`database/migrations/2026_09_23_090000_create_user_dashboard_layouts_table.php`

For manual PostgreSQL deployment:

`database/manual/2026-09-23-role-dashboard-layouts.sql`
