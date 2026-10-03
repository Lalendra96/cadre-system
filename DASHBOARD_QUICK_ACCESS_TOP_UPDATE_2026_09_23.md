# Dashboard Quick Access Top Update — 2026-09-23

## Summary
Quick Access / Quick Links is now treated as the top utility widget across the role-aware analytics dashboard.

## Behaviour
- Quick Links is normalized to the first position whenever it is enabled.
- Existing saved layouts are automatically normalized at read time; users do not need to reset their dashboards.
- Saving a dashboard always persists Quick Links first when present.
- Quick Links remains removable and may be added back from Available Widgets.
- When re-added, it is inserted at the top immediately.
- The Quick Links widget itself is non-draggable so other widgets cannot be placed above it.
- All other widgets remain drag-and-drop customisable.
- Quick Links remains full-width and role-authorized; it does not grant new permissions.

## Governance
The shortcut catalog continues to be generated only from navigation items already visible to the current user. Pinning Quick Links changes presentation only and does not change authorization or route access.

## Changed Files
- app/Services/RoleDashboardService.php
- resources/views/dashboard/role-analytics.blade.php
