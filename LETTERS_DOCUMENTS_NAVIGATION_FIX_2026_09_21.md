# Letters & Documents Navigation Fix — 2026-09-21

## Issue
The Secure Live Letter Builder routes and screens existed, but the application renders navigation from the `nav_items` database table. Updating only `NavItemSeeder` does not change an already-installed database, so some deployments continued to show the old scattered "Service Letters" item or no item at all.

## Fix
A dedicated **Letters & Documents** navigation section is now installed for existing and fresh deployments.

- **Live Letter Builder** — Subject Officer, Planning Officer, Admin Group and Super Admin
- **Letter Templates** — Super Admin
- **Letterheads** — Super Admin
- **My E-Signature** — Admin Group

Old Service Letter navigation entries in Carder, My Work, Admin Group and Administration are retired to avoid duplicate links.

## Deployment
Normal Laravel deployment:

```bash
php artisan migrate
php artisan optimize:clear
```

For installations where database changes are applied manually, run:

`database/manual/2026_09_21_letters_documents_navigation.sql`

Then refresh/re-login so the sidebar is rebuilt from current navigation data.
