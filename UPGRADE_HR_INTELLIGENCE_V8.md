# v8 — HR Responsibility Intelligence

This package continues the v7 module and implements the first priority in the new roadmap. It remains a Laravel 10/PostgreSQL application overlay; it does not include a Laravel skeleton, `.env`, production dependencies or hospital data.

## New workflow

1. **Observe ownership:** `hr:reconcile-ownership` compares each employee's current position, record activity and eligible HR officers with the last recorded observation.
2. **Record history:** a new baseline/change event is appended whenever the observed state changes. Position assignment and handover history from v7 remains available.
3. **Open a review:** an active employee whose position or owners change enters the reassignment queue. Initial baselines open cases for unassigned/shared responsibility; healthy single-owner baselines do not flood the queue with unnecessary acceptance work.
4. **Select the work owner:** a single eligible officer is proposed automatically. With shared responsibility, an HR manager selects a current eligible officer and records instructions. No eligible owner means the position assignment must be corrected first.
5. **Accept pending work:** only the named, currently eligible Subject Officer can accept. Open data-quality issues, uncompleted increments and unfinished retirement projects transfer in one database transaction. Completed records remain untouched. The case retains the IDs of transferred tasks and their former/new owners.

Selecting a work owner does not grant access, end another officer's position assignment, or remove a shared-ownership warning. Shared access may be intentional temporary cover. A repeated acceptance is idempotent; a stale case cannot transfer work after employee ownership changes. Older open cases become superseded when a new observation is recorded.

## Screens and permissions

| Screen/action | Access |
| --- | --- |
| Own workload and current position-scoped employee queue/history | Active Subject Officers |
| Hospital-wide workload, coverage alerts and queue/history | Super Admin, Planning Officer and existing HR-manager Admin Group categories |
| Refresh intelligence / propose work owner | Those same HR managers |
| Accept reassigned work | Named active Subject Officer with current position scope |
| Grant/end position responsibility | Existing v7 Super Admin workflow |

The menu is **Workforce → HR Intelligence & Reassignments**. Visibility uses `canAccessHrIntelligence`; controller/service checks independently enforce access. An exact employee record ID filter is provided. No wildcard employee search is added.

The workload table shows current position/employee coverage, open data-quality issues, increments overdue or due within 30 days, unfinished retirement projects, retirements expected within 12 months, individually assigned work within those counts, and pending acceptances. Shared employee coverage appears under each eligible officer, so officer rows are not a hospital headcount total. No arbitrary workload score or automatic staff selection is introduced.

## Coverage alerts

The scheduled reconciliation records:

- Active position without any eligible HR officer.
- Shared position ownership requiring review.
- A currently effective explicit assignment held by an inactive user or a user who no longer has the Subject Officer role.
- A disabled position that still has a currently effective explicit assignment.

HR managers receive one in-app notification per open position/finding episode. Resolved alerts close automatically; a later recurrence reopens the alert and increments its occurrence number. The first run may produce several alerts for existing gaps. Historical closed alerts remain in the database; the screen displays open alerts.

An employee without a position creates an unassigned reassignment case. Queue acceptance never restores access from an expired assignment or bypasses the v7 explicit-scope rule.

## History boundaries

The employee timeline records **observations from v8 initialization onward**, not reconstructed historical effective dates. The first observation is explicitly labelled “Baseline.” Updates made and reversed between scheduled observations cannot be reconstructed. Existing v7 assignment/handover rows retain their original effective dates and provide the assignment-level history.

Names in employee timelines are current display names for the recorded immutable IDs; names are not signed historical snapshots. Case transfer details preserve former/new task-owner IDs, and existing audit logs record proposals and acceptance. This release does not implement signed or cryptographically immutable workforce reports.

## Deploy from v7

1. Back up the complete application, PostgreSQL database and document storage. The previously supplied SQL contains only 12 configuration tables, not a complete recovery backup.
2. Test on a staging copy. Put the application in maintenance mode and pause scheduler/queue execution for the deployment.
3. Copy/merge the module directories into the existing application. Preserve `.env`, storage and local integrations. Review `CHANGED_FILES_V8.txt`; merge local route and Console Kernel customisations rather than discarding them.
4. Run from the Laravel application root:

   ```bash
   composer dump-autoload
   php artisan migrate --force
   php artisan optimize:clear
   php artisan view:cache
   php artisan route:list --path=hr-intelligence
   php artisan hr:reconcile-ownership
   php artisan up
   ```

5. Resume the scheduler and queues. This package adds the following to `app/Console/Kernel.php`:

   ```php
   $schedule->command('hr:reconcile-ownership')
       ->everyFiveMinutes()
       ->withoutOverlapping(15);
   ```

   Retain the existing per-minute `schedule:run` cron. Do not register the job twice. Use the hospital's configured timezone and a cache driver supporting atomic locks. Multiple application nodes must share the cache used for synchronization locks.

6. Review coverage alerts and the queue as an HR manager, then test acceptance with a different Subject Officer. The first reconciliation can be run from the CLI to avoid an HTTP request timeout on a large employee register.

If upgrading from before v7, also follow `UPGRADE_HR_RESPONSIBILITY_V7.md`. The new migration adds the ownership/queue/alert tables, a last-completed-refresh status row, and nullable `responsible_user_id` fields on `employee_increments` and `retirement_projects`. Existing rows remain intact. New menu installation is included in the migration; reseeding the whole menu is unnecessary.

The history-preserving migration refuses rollback. Reverting requires a matching verified database/application backup. Never use `migrate:fresh` on a live database.

## Operational behaviour

- Scope checks and notification routing still use live v7 HR assignments. The observation/queue job does not control access and can lag changes by up to the scheduling interval.
- A manager can refresh explicitly after changing assignments. Acceptance always rechecks live eligibility and the case fingerprint.
- A successfully completed refresh updates the status displayed on the dashboard. Unchanged employee snapshots are not rewritten every five minutes.
- Acceptance transfers open records present at that moment. It does not automatically assign future newly created tasks; those continue using the normal position-scoped workflow until assigned or reviewed.
- Disabled/soft-deleted employees do not receive new reassignment cases. Old open cases are superseded after observation. Subject Officers cannot use this screen to view soft-deleted employee cases.
- Coverage alerts do not implement escalation tiers or email delivery. Document governance, scenario planning, signed reports and the integration API remain separate work.

See `VALIDATION_V8.md` for the checks actually performed and deployment checks still required.
