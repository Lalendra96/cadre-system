# Offline assets, form validation and interface update

## Apply to the existing Laravel 10 installation

1. Back up the deployed application and database.
2. Copy this package over the existing project. Keep the deployed `.env`, `vendor/`, `bootstrap/`, and `storage/` directories. This remains a copy-and-replace project; those runtime directories are not supplied.
3. Run `php artisan optimize:clear` and `php artisan view:cache`.
4. Hard-refresh each browser once (Ctrl+F5) to load the updated styles and scripts.

No new database migration or data import is required for this update. Do not restore the supplied historical SQL backup over your live database for this change.

## Offline scope

All referenced browser scripts, styles, charts, dropdown libraries and branding assets are included under `public/`. The application no longer downloads Chart.js or web fonts from a CDN. No npm build is needed to serve these views. Fonts use local system fallbacks, including the fonts available on the user's computer for Sinhala/Tamil.

Bundled dependencies:

| Dependency | Version | Local directory |
|---|---|---|
| Chart.js | 4.4.1 | `public/vendor/chartjs/` |
| jQuery | 3.7.1 | `public/vendor/selectize/` |
| Selectize (includes Sifter and MicroPlugin) | 0.15.2 | `public/vendor/selectize/` |

Third-party licenses are included beside each dependency. The Chart.js version matches the previously referenced CDN version.

Offline here means no Internet is required for the browser interface. Users still need access to the hospital Laravel server and database. Optional email, LAN AI and external integrations still require their configured services.

The complete Composer `vendor/` directory is **not included**. Existing installations can keep their installed dependencies. For a new offline server, prepare a compatible Laravel 10 installation on an Internet-connected computer, run `composer install --no-dev --prefer-dist --optimize-autoloader`, and transfer the application with `vendor/` to the offline server. Match PHP and required extensions to `composer.lock`; run `composer check-platform-reqs --no-dev` on the destination. Do not bypass platform requirements in production. Preserve the destination `.env` and generate an application key only for a new installation.

## Validation

- Browser validation blocks leading spaces, tabs, line breaks and Unicode whitespace in editable text inputs and textareas; normal spaces within names and later lines of text remain allowed.
- Server validation captures original form, JSON and query values before Laravel trimming. It rejects leading whitespace in nested values with field-specific errors, including passwords without changing their value. CSRF and method transport fields are excluded.
- HTML requests use Laravel's normal validation error handling; JSON clients receive validation errors. Existing required/type/range/file/authorization rules remain in place.
- Invalid submissions are rejected rather than silently accepting a trimmed version.
- Cadre proposal updates now validate the title, items, position IDs, counts and justifications before changing records. Feature configuration now validates toggles and local endpoint/model values.
- This does not revalidate historical database records or change imported file contents.

## Interface and formatting

- Shared controls, cards, tables, focus states and errors use the same steel-blue interface tokens.
- Light/dark input and panel colors are paired, and chart labels/tooltips update when the theme changes.
- The public circular and intern selection pages share the application's palette and validation script.
- Print layouts remain controlled by their existing print styles.
- PHP formatted with Laravel Pint; Blade with blade-formatter. Inline `@php(...)` expressions converted to standard `@php` / `@endphp` blocks for safe formatting.
- Local CSS and JavaScript formatted with Prettier. Third-party distributions remain intact.

## Verification

Run `php tests/offline-interface-regression.php` after installing dependencies. It tests normal, Unicode, nested, password, query and JSON whitespace handling, then compiles and syntax-checks the application's PHP and Blade sources without connecting to a database.

Full hospital role/workflow and production database testing must be done on staging; these checks do not certify every business workflow.
