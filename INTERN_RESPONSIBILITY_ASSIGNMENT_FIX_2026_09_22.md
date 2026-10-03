# Intern Batch Responsibility Assignment Fix — 2026-09-22

## Issue
The inline responsibility assignment/reassignment form on the Intern Medical Officer Allocation index submitted `assigned_subject_officer_id` and `reason`, while the controller also required `effective_date`. Laravel validation therefore redirected back without updating the batch.

## Fix
- Added mandatory Effective Date to the inline form.
- Added optional Reference No. to preserve administrative authority/reference.
- Changed Reason to a clearer Reason / Authority textarea.
- Added visible validation errors on the index screen.
- Disabled the submit action if no authorised active Subject Officers are available.
- After a successful assignment, redirect back to Intern Medical Officer Allocation so the Responsible Subject Officer column immediately shows the saved officer.
- Preserved responsibility-history and audit-log creation.

## Governance
Only Super Admin may assign/reassign responsibility. The selected account must be active and carry the Subject Officer role. Other Subject Officers remain read-only for the batch.
