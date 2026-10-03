# Employee Profile Completion Tracking — 23 Sep 2026

## Purpose
Track how many Employee Profiles have been recorded and how many contain the core data required for reliable HR administration, against a governed target for each Subject Officer + Subject Code + Position assignment.

## Source-of-truth mapping
The tracker deliberately reuses the existing HR ownership model:

- `subject_code_user` / acting Subject Officer assignments define Subject Code responsibility.
- `hr_responsibilities` and the existing fallback logic define effective Position responsibility.
- `employee_hr_allocations` is authoritative when an individual employee has an explicit owner.
- Employees without an explicit allocation use the existing Subject Code + Position fallback.
- `employees.subject_code_id` and `employees.position_id` remain the employee master-data mapping.

The tracker does not use `created_by` as ownership, and it does not substitute Approved Cadre amounts for profile-entry targets.

## Access
- Super Admin: institution-wide aggregate view and target management.
- Planning Officer: institution-wide aggregate view and target management.
- Admin Group: institution-wide aggregate view only; no employee-level drill-down is exposed by this tracker.
- Subject Officer: only current effective HR responsibility / allocated Employee Profiles.

## Progress measures
- **Target Profiles** — governed planning/administrative target, configured with an effective date and source/reference.
- **Recorded Profiles** — active Employee Profile records currently mapped to the officer's governed scope.
- **Core Complete** — recorded profiles containing the documented core identity, organisational and basic service fields.
- **Needs Completion** — recorded minus core-complete.
- **Not Recorded** — target minus recorded, never below zero.

Core completeness is a data-quality indicator only. It is not an assessment of an employee's performance, eligibility, fitness, promotion entitlement or legal status.

## Target governance
Targets are versioned by superseding the previous active target. Historical rows are retained. Every new target requires:

- Subject Officer
- Subject Code
- Position
- Target count
- Effective date
- Source / memo reference

The target update is audit logged.

## Dashboard integration
`Profile Completion` is available as an authorised dashboard widget for Super Admin, Planning Officer, Admin Group and Subject Officer roles. It is removable/re-addable through the existing dashboard widget manager.
