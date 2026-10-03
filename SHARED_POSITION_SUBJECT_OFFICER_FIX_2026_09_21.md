# Shared Position / Subject Officer Visibility Fix — 2026-09-21

## Problem confirmed
Positions such as Nursing are linked to multiple Subject Codes. The Employee Profiles screen was using only `employee_hr_allocations` for Subject Officer visibility. The migration intentionally did not auto-allocate employees for positions with multiple owners, so shared positions could appear empty to the logged-in Subject Officer even though `employees.subject_code_id` and `position_subject_code` correctly identified their scope.

The supplied 2026-09-21 database dump confirms this condition for position 7: it is linked to multiple Subject Codes, while there are no current `employee_hr_allocations` rows for position 7.

## Fix
`App\Services\WorkforceScopeService` now resolves profile visibility in this order:

1. Current explicit `employee_hr_allocations` are authoritative.
2. When an employee has no current explicit allocation, visibility falls back to BOTH:
   - the logged-in Subject Officer's effective Subject Code(s), and
   - the logged-in Subject Officer's effective HR position scope.
3. If an employee has an explicit allocation to another officer, the Subject Code fallback is suppressed so the profile does not leak to the previous/shared officer.

This supports shared positions without weakening explicit reassignment controls.

## Files changed
- `app/Services/WorkforceScopeService.php`
- `app/Http/Controllers/EmployeeController.php` (clarified empty-scope warning)
