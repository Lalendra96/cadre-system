# v7 validation record

## Completed in the delivery environment

- **281 non-Blade PHP files passed native PHP 8.3.6 syntax validation**, including the application, migrations, routes, seeders and regression scripts. No syntax errors were reported.
- **10 dependency-free reminder-band checks passed**: configuration cleanup, duplicate markers, invalid markers, date boundaries, missed-run catch-up, current bands, due-today behaviour and out-of-window dates. Re-run with `php tests/hr-responsibility/reminder-bands.php`.
- **21 changed/new PHP files were consistently formatted** with four-space indentation, multiline blocks and PSR-style braces.
- Reviewed the v6.1 routes, models, assignment pivot, role helpers, reminder commands and nav structure before integrating the new workflow.
- Inspected the supplied PostgreSQL SQL file: it contains 12 configuration tables, not a complete application backup. No hospital data was imported or changed.
- The release archive retains every file from the v6.1 baseline and adds the implementation, migration, upgrade documentation and regression harness. Temporary runtime files, dependencies, SQL data and credentials are excluded.

## Included but not executed

The 14-case Testbench regression suite covers responsibility scope, temporary expiry, ended history, inactive owners, cross-position access, role checks, handover acceptance, effective dates, overlap prevention, cancellation and durable/transactional reminder delivery.

A fresh Laravel 10/Testbench 8 dependency install was blocked by Composer security advisories. The isolated harness was then configured for Laravel 12/Testbench 10, but dependency download timeouts prevented installation and execution. **No full integration-suite pass is claimed.** The harness's Composer files apply only to its nested test directory; they do not upgrade the hospital application.

Blade rendering, migration execution on the actual schema, browser interactions, PostgreSQL locking/concurrent-job behaviour and the deployed Laravel 10 application were not executed here. Test these on a staging copy before live deployment. The shipped code does not introduce APIs that intentionally require Laravel 12.
