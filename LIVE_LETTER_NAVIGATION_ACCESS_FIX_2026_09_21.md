# Live Letter Builder navigation access fix — 2026-09-21

## What was happening
The Subject Officer already had the `service-letters.index` route under **Letters & Documents**, but the navigation translation layer replaced the database label with the legacy text **Service Letters**. This made the new Secure Live Letter Editor look as though it was missing.

## Changes
- `service-letters.index` is now displayed as **Live Letter Builder** in English, Sinhala and Tamil navigation.
- Added an explicit **Create Live Letter** menu item for Subject Officer, Planning Officer and Super Admin.
- The Live Letter Builder landing page now uses the same terminology.
- Admin Group keeps review/approval access through Live Letter Builder, but is not shown a drafting shortcut.
- A new migration is included so already-migrated installations are corrected without editing an old migration.

## Deployment
Run `php artisan migrate` and `php artisan optimize:clear`, then sign out and sign in again.
