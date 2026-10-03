# Isolated HR regression harness

This harness tests the module's HR services and HTTP write guards using a synthetic SQLite in-memory schema. It does not restore, connect to or modify the hospital database.

```bash
cd tests/hr-responsibility
composer install
composer test
```

Requires PHP 8.2+ with PDO SQLite, DOM/XML, mbstring and normal Laravel extensions. The harness uses Orchestra Testbench 10 / Laravel 12 because a fresh Testbench 8 / Laravel 10 dependency install is blocked by Composer security advisories. Application code still uses Laravel 10-compatible APIs. Passing this harness does not establish production Laravel 10/PostgreSQL compatibility or PostgreSQL concurrency behaviour.

The suite now contains 19 cases, including HR intelligence baseline/job idempotency, open-work transfer, preservation of completed work, stale-case denial and manager-only proposals. Earlier cases cover explicit-vs-legacy scope, date boundaries, expired/revoked access, disabled officers/positions, cross-position isolation, role checks, named-recipient acceptance, future/same-day handovers, overlap rejection, cancellation, reminder deduplication, transactional rollback and reminder catch-up bands.

See the root `VALIDATION_V7.md` for whether this harness was actually executed in the delivery environment. Install dependencies only inside this folder; do not replace the application's root Composer files.
