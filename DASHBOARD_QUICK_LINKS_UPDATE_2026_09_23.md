# Dashboard Quick Links Update — 2026-09-23

## Purpose
Adds a configurable Quick Links widget to the role-aware analytics dashboard.

## Behaviour
- The widget is available to every dashboard-enabled role.
- A user can choose up to 12 shortcuts.
- Candidates are derived exclusively from `nav_items` that already pass `NavItem::isVisibleTo()` for the authenticated user.
- Hidden, disabled, feature-disabled, or role-restricted navigation items are never offered.
- Saved shortcuts are revalidated on every dashboard load, so a later role/permission change automatically removes inaccessible links.
- Quick Links do not grant permissions; destination controllers/middleware remain authoritative.

## Database
Adds nullable `quick_links` JSONB to `user_dashboard_layouts`.

## Files
- `app/Models/UserDashboardLayout.php`
- `app/Services/RoleDashboardService.php`
- `app/Http/Controllers/RoleDashboardController.php`
- `routes/web.php`
- `resources/views/dashboard/widgets/quick_links.blade.php`
- `database/migrations/2026_09_23_100000_add_quick_links_to_user_dashboard_layouts.php`
- `database/manual/2026-09-23-dashboard-quick-links.sql`
