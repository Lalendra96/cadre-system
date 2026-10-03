# Value-added Subject Officer and Admin Group features

## Subject Officer — My Workspace

Added operational signals, all restricted to the officer's explicit employee allocations:

- Professional registration expiry watch (next 90 days)
- Pending employee profile correction count
- Service-letter workflow summary: draft / pending approval / returned
- Returned service letters surfaced as a priority
- Recent transfer activity (last 30 days)
- Confirmation-status count for allocated employees
- Workload distribution by position
- Quick actions for multilingual service letters, transfers and acting appointments
- Existing increment, retirement, acting-appointment and data-quality signals retained

The workspace continues to use `WorkforceScopeService::employeeQuery($user)`, so a shared position does not expose another Subject Officer's allocated employees.

## Admin Group / Super Admin — Carder Summary

Added an aggregate-only Administrative Operations Pulse:

- Active employee count
- Employee-to-Subject-Officer allocation coverage
- Unallocated employee count
- Active Subject Officer account count
- Open data-quality issue count
- Increments due within 30 days
- Retirements within 12 months
- Professional registrations expiring within 90 days
- Service letters awaiting approval
- Pending employee profile correction requests
- Employment-status distribution

No employee names, NICs, profile links, or other identifying profile details are exposed by this panel. This preserves the requirement that Admin Group users use aggregate/count views rather than browsing Employee Profiles.

## Deployment

No new database migration is required for this update.

After replacing files:

```bash
php artisan optimize:clear
```

If the project has not already been migrated to the latest package, run the existing migrations as usual before deployment.
