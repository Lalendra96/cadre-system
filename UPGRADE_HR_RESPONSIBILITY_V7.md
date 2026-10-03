# Carder HR responsibility upgrade — v7

This release continues **cadre-system-position-scoped-hr-v6.1-fix.zip**. It includes the existing v6.1 module and the next HR responsibility implementation batch. It is a copy-and-replace Laravel module, not a standalone Laravel installation.

## Implemented

- One position-responsibility resolver for HR access and increment/retirement recipients.
- Permanent and temporary/acting assignments with inclusive effective dates.
- A permanent handover per position: administrator records outgoing assignment, receiving officer, effective date, notes and outstanding actions; the receiving officer accepts.
- Future accepted handovers retain the outgoing officer until the effective date. Same-day acceptance ends the outgoing assignment immediately. Overdue unaccepted handovers need cancellation and reissue; they never transfer access silently.
- Assignment and handover history, cancellation/end reasons and audit events. No assignment or handover deletion endpoint.
- A **Workforce → HR Responsibilities** menu item, coverage counts and warnings for unassigned positions, shared ownership, disabled positions and inactive/former Subject Officers.
- Subject Officers see their own assignment history and handovers involving them. Super Admin manages assignments; Planning Officers and authorised HR managers can review hospital-wide coverage/history.
- Employee HR helpers, transfer/acting record lists, service-letter access, employee feature gates and the workspace position display use effective HR position scope. Unlinked transfers now require a hospital-wide HR manager. A former service-letter drafter does not retain access solely because they drafted the letter.
- Durable reminder delivery keys per event, employee/increment, due date, reminder band and recipient. The notification and its delivery key commit together. A rerun does not duplicate the same reminder; a newly responsible officer can receive a reminder in the current band. Inactive employees/officers and completed increment decisions are excluded.
- Changed PHP uses consistent four-space indentation, multiline blocks and PSR-style braces.

## Upgrade the existing Laravel application

1. Back up the **complete live database**, application files and uploaded documents. The attached SQL was inspected and contains only 12 configuration tables; it does **not** contain `users`, `employees`, `migrations` or the complete HR history. It is not a full application recovery backup.
2. Test this package on a copy of your Laravel 10/PostgreSQL installation before replacing live files.
3. During the deployment window, pause scheduler/queue execution and put the application into maintenance mode:

   ```bash
   php artisan down
   ```

4. Copy the package's `app/`, `database/`, `resources/`, `public/` and `routes/` contents into your existing application, preserving `.env`, `vendor/`, `storage/` and any local integration customisations. Merge `routes/web.php` if it contains additional hospital routes.
5. From the Laravel application directory, run:

   ```bash
   composer dump-autoload
   php artisan migrate --force
   php artisan optimize:clear
   php artisan view:cache
   php artisan route:list --path=hr-responsibilities
   php artisan route:list --path=hr-handovers
   php artisan up
   ```

6. Resume the scheduler/queues. Retain the existing reminder schedules:

   ```php
   $schedule->command('increments:notify-upcoming')->dailyAt('07:00')->withoutOverlapping();
   $schedule->command('retirements:notify-upcoming')->dailyAt('07:15')->withoutOverlapping();
   ```

   Do not add a second copy of a schedule that already exists. Use the configured hospital application timezone (for example `Asia/Colombo`). No scheduled job is needed to activate or expire HR assignments: every scope check evaluates their dates.

7. Open **Workforce → HR Responsibilities** as Super Admin and review coverage. New menu installation is part of the migration; reseeding all menu items is unnecessary.

## Migration and access behaviour

`2026_09_17_000093_create_hr_responsibility_history.php` creates `hr_responsibilities`, `hr_handovers`, `hr_reminder_deliveries` and `users.hr_scope_configured`.

Existing `hr_position_user` rows are copied to permanent responsibility history, using the recorded assignment date where available. Existing source rows are retained as a legacy snapshot; new assignments are maintained in the responsibility ledger. Earlier historical ownership is not invented. When no assignment timestamp exists, migration day is used.

A user with no explicit HR assignment continues using positions linked to their effective Subject Codes. After the first explicit assignment, HR scope is permanently explicit: no current assignments means no position-scoped access, even if old Subject Codes remain. Scheduled future assignments switch the account into explicit mode immediately; assign any current responsibilities that the officer must retain before scheduling future ones. Elevated roles retain their existing hospital-wide permissions.

New account creation supports initial permanent HR positions. Existing accounts use the dedicated HR Responsibilities screen rather than silently replacing their assignment list in the user edit form. Only active Subject Officers can receive new assignments. Temporary responsibility supplements explicitly assigned positions; it does not automatically suspend another officer.

For a legacy officer's first handover, first record their complete current permanent position responsibilities. Then hand over each relevant position. Multiple positions use separate acceptance records.

An accepted handover changes which officer can see position-scoped employee work; it does not rewrite historic employee actions, completed tasks, author fields, import-batch permissions or monthly Carder ownership. Outstanding actions are recorded as handover notes, not as a new task engine. The remaining escalation, professional registration, promotion, recruitment and duplicate-resolution features from the gap list are outside this batch.

The new migration intentionally refuses `migrate:rollback`, preserving responsibility history. Reverting this deployment requires the matching complete application/database backup. Do not use `migrate:fresh` against a live database.

## Reminder upgrade notes

The old short-lived cache flags cannot prove who received a reminder. Therefore the first v7 run can send one catch-up reminder per recipient/current band, including for events notified by v6.1. Subsequent runs use durable delivery records.

Configured `increment_reminder_days`, `retirement_reminder_days` and `workforce_notifications_enabled` are preserved. Within a band, a missed scheduler run catches up once; older bands are not sent together. The retirement notification opens the position-scoped employee profile rather than an inaccessible management report.

## Verification

See `VALIDATION_V7.md` for checks actually completed and remaining deployment checks. The optional regression harness is under `tests/hr-responsibility/`; it has its own Composer configuration and uses a synthetic in-memory SQLite database. Never copy its Composer configuration over the application's root Composer files.

Before live rollout, verify two Subject Officers with different positions, temporary start/end boundaries, acceptance by the named recipient only, future handover dates, ended-assignment denial, disabled officers, navigation and repeated reminder execution on a staging copy of the real Laravel 10/PostgreSQL app.
