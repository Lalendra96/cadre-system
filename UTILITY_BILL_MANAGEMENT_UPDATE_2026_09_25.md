# Utility Bill Management Update — 2026-09-25

## Added
- Feature-toggle controlled **Utility Bill Management** module (`feature_utility_bills`, default disabled).
- Super Admin governance page to assign the single responsible Subject Officer and configure due-warning days.
- Utility account register for electricity, water, telephone, internet, sewerage, gas and other utilities.
- Bill register with billing period, issue/due dates, balances, charges, adjustments, consumption and outstanding balance.
- Payment transaction history with partial/full payment handling, payment reference and voucher number.
- No delete workflow: payments remain historical/auditable and account/bill records are retained.
- Due-soon and overdue monitoring KPIs.
- Admin Group / Super Admin analytics:
  - 12-month billed vs paid trend chart.
  - Current-year cost by utility type chart.
  - Outstanding, overdue and monthly-payment KPIs.
  - CSV report export.
- Audit logging on account creation, bill creation, payments, status/balance changes and responsibility changes.
- Navigation entries for operational and Super Admin administration pages.
- Leading-space validation on free-text form inputs.

## Access Model
- **Assigned Subject Officer:** create utility accounts, record bills, record payments, monitor bills.
- **Admin Group:** read-only monitoring, analytics and CSV reports.
- **Super Admin:** full operational access, governance assignment/configuration, analytics, and module enable/disable under Feature Management.

## Installation
1. Copy/replace this project over the matching Laravel project source.
2. Run `php artisan migrate` once to create the new tables and settings.
3. Log in as Super Admin and open **Feature Management**.
4. Enable **Utility Bill Management**.
5. Open **Utility Bill Administration** and assign the responsible Subject Officer.

The module uses the already bundled offline Chart.js asset and does not add an npm dependency.
