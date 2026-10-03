# Third-Party Component Register

Generated from the supplied application's Composer lock file. This document supports the Sri Lanka CERT requirement to identify third-party components and their versions. It is not a vulnerability scan.

| Component | Locked version | Purpose/class | Governance action |
|---|---:|---|---|
| `barryvdh/laravel-dompdf` | `v3.1.2` | Application dependency | Review advisories and update to tested stable releases |
| `guzzlehttp/guzzle` | `7.12.3` | Application dependency | Review advisories and update to tested stable releases |
| `laravel/framework` | `10.50.2` | Application dependency | Review advisories and update to tested stable releases |
| `laravel/sanctum` | `v3.3.3` | Application dependency | Review advisories and update to tested stable releases |
| `laravel/tinker` | `v2.11.1` | Application dependency | Review advisories and update to tested stable releases |
| `phpoffice/phpspreadsheet` | `5.9.0` | Application dependency | Review advisories and update to tested stable releases |

## Required process

1. Keep `composer.lock` under change control.
2. Do not introduce wildcard production dependencies.
3. Before adding a package, record the business need, maintainer/source, licence and security impact.
4. Run dependency/advisory checks from an approved connected maintenance environment and document findings.
5. Test package/framework updates in staging before production.
6. Remove components no longer required.
7. Keep offline JS/CSS libraries under the same inventory/version-review process.
