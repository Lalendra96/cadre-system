# Completion Tracker — Subject Officer Export Filter

Date: 2026-09-24

## Update
- Added an **Export only files handled by** Subject Officer filter to Employee Profile Handling Count Tracking.
- Filter applies to both CSV and PDF exports.
- **All authorised Subject Officers** remains available for users whose role permits institution-level visibility.
- Pure Subject Officer users remain restricted to their own authorised handled employee profiles.
- Export requests validate the selected officer against the already-authorised tracker rows; manually changing `officer_id` cannot widen export scope.
- CSV and PDF exports now show the selected export scope.
- Filtered export summary values (Handling Count, Current Profile Count, Remaining, Completion %) are recalculated from the selected Subject Officer only.

## Changed files
- `app/Http/Controllers/EmployeeProfileCompletionController.php`
- `resources/views/employee-profile-completion/index.blade.php`
- `resources/views/pdf/employee-profile-handling-progress.blade.php`
