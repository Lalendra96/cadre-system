# Intern Medical Officer Allocation — Analysis & Auto-close Update

## Current-allocation analysis

A new **Allocation Analysis** screen is available from the Intern Medical Officer Allocation page.

Current totals deliberately include **active batches only**. Closed batches remain historical records and are not counted.

The screen shows:

- active batch count;
- active intern count;
- 1st Appointment capacity, allocated interns, and interns still requiring allocation;
- 2nd Appointment capacity, allocated interns, and interns still requiring allocation;
- interns with both appointments recorded;
- Rotation Unit-wise capacity / allocated / remaining-slot breakdown for both appointments;
- active Batch-wise capacity / allocated / remaining-intern breakdown;
- data-quality warnings when assignment counts exceed configured capacity or configured total capacity is below the current active intern count.

### Important distinction

**Remaining Slots** = configured capacity minus recorded allocations for a Rotation Unit.

**Remaining Interns** = active interns in the relevant active batch scope who do not yet have the relevant appointment recorded.

These values are kept separate so available capacity is not confused with incomplete intern allocation.

## Governance scope

- Super Admin, Admin Group and Planning Officer receive current institution-level aggregate analysis.
- Subject Officers receive analysis only for active batches formally assigned to them.
- Analysis contains aggregate counts and does not expose intern names or identifiers.
- No allocation, placement, eligibility or personnel decision is generated automatically.
- Disabled intern records are excluded from current counts.
- Closed batches are excluded from all current analysis figures.

## Automatic batch closure

An active batch is automatically closed once its official `end_date` has passed.

Two safeguards are used:

1. Laravel Scheduler runs `intern-batches:auto-close-ended` daily at 00:10.
2. The lifecycle check also runs when Intern Allocation / Allocation Analysis is opened, making closure idempotent even if the scheduler was temporarily unavailable.

Auto-close:

- sets `is_active = false`;
- records `closed_at`;
- records an automatic close reason;
- marks `closed_automatically = true`;
- creates an audit-log event;
- never deletes the batch, capacities, interns, assignments or placement history.

The assigned Subject Officer may continue the governed **RHO Placement** follow-up after the batch has ended, even though operational allocation editing is closed.

## Database

Migration:

`database/migrations/2026_09_22_140000_add_intern_batch_closure_metadata.php`

Manual PostgreSQL alternative:

`database/manual/2026-09-22-intern-batch-auto-close-analysis.sql`

## Scheduler requirement

Laravel Scheduler must be running on the server for unattended daily closure:

```text
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```
