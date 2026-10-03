# Intern Subject Officer Assignment Dropdown Fix — 2026-09-22

## Cause
The assignment dropdown queried only users with both the `subject_officer` role and `can_manage_intern_assignments = true`. Existing Subject Officer accounts in the supplied Carder database have the role but the legacy feature flag is false, causing an empty dropdown.

## Corrected access model
- Every **active Subject Officer** may open Intern Medical Officer Allocation in read-only mode.
- Super Admin / Admin Group / Planning Officer retain oversight access.
- **Only the Subject Officer formally assigned to a batch** can change that batch's dates, capacity, interns, assignments, or RHO placements.
- Super Admin alone assigns/reassigns batch responsibility.
- Responsibility changes remain reason/effective-date based and audit logged.

The legacy `can_manage_intern_assignments` account flag is no longer used to decide module visibility or batch ownership. This prevents the flag from contradicting the governed batch-level ownership model.
