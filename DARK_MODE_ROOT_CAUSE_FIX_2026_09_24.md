# Dark Mode Root Cause Fix — 2026-09-24

## Root cause
`public/css/carder-professional.css` was loaded after `md3-dark.css` and declared its light colour palette on both `:root` and `[data-theme="light"]`.

Because `:root` remains matched when the page is in dark mode, those later declarations replaced the active dark Material Design tokens with white/light values. The theme state itself was working; the cascade was not.

## Fix
- Removed `:root` from the professional light-palette selector.
- Light-specific tokens now apply only under `[data-theme="light"]`.
- Added dark equivalents for legacy `--cadre-*` tokens.
- Added a final dark-theme hardening layer for dashboard widgets and quick-link components.
- Bumped CSS cache versions in `resources/views/layouts/app.blade.php`.

No database migration is required.
