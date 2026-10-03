# Additional governance update

This update adds four enterprise-governance capabilities identified in the requirements review:

- **Scenario comparison:** Base case, planned recruitment and high-retirement scenarios are compared side by side using projected headcount, gaps and fill rate.
- **Application health:** Super Admin can check database connectivity, the last HR ownership reconciliation and failed-job count when Laravel's failed-jobs table is enabled.
- **Sensitive-field maker-checker:** Subject Officers can request corrections to NIC, DOB, appointment date, public-service joining date and retirement age. Management reviews and approves/rejects the request; the request history is retained.
- **Expanded data-quality coverage:** The preceding update added duplicate Pay No., future-date, inactive-unit, registration-expiry and open-ended acting-assignment checks.

The new migration is `2026_09_18_000098_create_employee_change_requests.php`.
