# Planning and Unit Decision-Support Update — 2026-09-21

## Purpose

This update adapts the existing Admin Group aggregate intelligence pattern for two operational decision-making contexts without broadening access to personal HR records:

1. **Planning Officer** — institution-wide aggregate operational pulse added to the existing planning dashboard.
2. **Unit Manager / Unit In-charge** — new unit-scoped aggregate decision-support role and dashboard.

## New Unit Manager role

Role key: `unit_manager`

A Unit Manager must be assigned one or more units by the Super Admin. Unit assignments are stored in `unit_user_decision_scope` and affect decision-support visibility only.

The role does **not** automatically grant:

- Employee Profile access;
- NIC, service-file or personal contact visibility;
- Subject Officer powers;
- employee HR record modification;
- transfer/appointment/disciplinary approval powers.

## Unit dashboard indicators

The Unit Decision Support dashboard provides aggregate-only signals for assigned units:

- active workforce count;
- unit-position allocated posts, actual in-post and vacancy gap;
- projected retirements within 12/24 months;
- increments due within 30 days;
- open data-quality issue count;
- professional registrations expiring within 90 days;
- missing core-data count;
- transfer-in / transfer-out / net movement over 90 days;
- workforce mix by position;
- largest staffing gaps by unit and position.

No employee names or profile links are shown.

## Planning dashboard enhancement

Planning Officers now also receive an **Administrative Operations Pulse for Planning**, containing aggregate institution-level indicators for:

- professional-registration expiry exposure;
- increment workload in the next 30 days;
- incomplete core workforce records;
- net workforce movement over the previous 90 days.

These complement the existing cadre, vacancy, retirement, data-quality and governance indicators.

## Governance safeguards

- Aggregate-first / no PII on decision dashboards.
- Need-to-know unit scoping controlled by Super Admin.
- Dashboard outputs are indicators, not determinations.
- Employee-specific action remains with the responsible Subject Officer / HR workflow.
- Consequential actions require the existing governed approval workflow.
- Official establishment, service records and applicable authorities must be checked before action.
- Unit scope changes are controlled through user administration rather than self-service.

## Deployment

Normal Laravel deployment:

```bash
php artisan migrate
php artisan optimize:clear
```

For environments where migrations are applied manually through PostgreSQL/pgAdmin, use:

`database/manual/2026_09_21_unit_manager_decision_scope.sql`
