# Non-Critical Reference Caching Update — 2026-10-01

## Objective
Reduce repeated database traffic without caching data whose staleness could affect safety, legal decisions, authentication, authorization, payroll, approvals, live attendance, PII, or audit evidence.

## Cached data

| Dataset | TTL | Invalidation |
|---|---:|---|
| Active navigation definitions | 5 minutes | Immediate version bump when `nav_items` changes |
| Active units | 10 minutes | Immediate version bump on Unit save/delete/restore |
| Active positions | 10 minutes | Immediate version bump on Position save/delete/restore |
| Public-holiday lookup by date | 15 minutes | Immediate version bump on holiday save/delete |

The cache keys are **versioned**. A change to the source model increments its namespace version, so older cached values become unreachable immediately. TTL remains as a cleanup/safety boundary.

## Deliberately not cached

The following are intentionally kept live:

- authenticated user/session state
- role and permission decisions
- approval queues and approval decisions
- employee PII and decrypted employee data
- payroll figures and statutory calculation output
- live attendance punches / biometric event ingestion
- leave/roster approval state
- audit logs and configuration-change evidence
- security-console state
- password/MFA/session controls

Navigation *definitions* are cached, but `NavItem::isVisibleTo($user)` still runs live for every authenticated user. Therefore cached navigation cannot grant a role or bypass a feature/permission check.

## Operational notes

The implementation works with Laravel's configured cache store and does not require Redis. Redis/Memcached may be used later for higher-scale deployments, but file/database cache remains supported.

If reference data is changed outside Laravel/Eloquent (for example direct SQL), the automatic model invalidation hook will not run. In that exceptional case clear the application cache after the administrative change:

```bash
php artisan cache:clear
```

Do not routinely clear the cache after normal application-based Unit, Position, navigation, or holiday changes; model invalidation handles those automatically.
