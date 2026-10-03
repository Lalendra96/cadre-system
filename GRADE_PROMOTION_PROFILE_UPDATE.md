# Grade Promotion & Employee Profile Update

## Changes
- Grade promotion/progression is now the primary career reminder in Employee 360 and Subject Officer Workspace.
- Increment information remains available as history/workflow but is no longer highlighted as a primary Employee 360 KPI.
- Grade promotion reminders use the configured Position Grade ladder and `min_years_in_grade` rule.
- Subject Officer notifications are sent only to officers explicitly allocated to the employee, plus Super Admin.
- Employee edit label changed to **Date Reported for Duty to this institute**.
- Added missing service chronology capture fields: Date Joined Public Service, Date Current Grade Started, and legacy Date Joined Institution.
- Employment Snapshot now includes Position, Grade, next grade/promotion eligibility, Unit/Ward, Subject Code, Service File, Registration, Appointment Date, Public Service Date, Reported-for-Duty date, Service length, Salary Scale, and Retirement.

## Deployment
No database migration is required for this update; the fields already exist.

```bash
php artisan optimize:clear
php artisan employees:notify-grade-promotion-eligibility
```

The scheduled command already runs daily at 07:35.
