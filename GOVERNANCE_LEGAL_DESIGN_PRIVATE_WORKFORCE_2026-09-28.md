# Governance & Legal Design Notes — Private Workforce / GP Extension

This package is designed to support compliance-oriented implementation; it is **not a legal opinion or certification of compliance**.

## Privacy / Sri Lanka PDPA-oriented controls
- Data minimisation: payroll banking fields are kept in a separate payroll profile rather than the core employee table.
- Laravel encrypted casts are used for bank name, branch and account number fields.
- Need-to-know access should be enforced with the host project's roles/policies; payroll and bank details must not be exposed to ordinary roster/attendance users.
- Existing `audit_logs` are reused for material workforce actions.
- Disable rather than delete for master/HR records; finalized payroll is locked.
- Employee self-service exposes only the authenticated user's employee-linked records.
- Exports should use the existing `export_audit_logs` mechanism and role controls.
- Retention periods must be configured by the deploying organisation based on its legal/HR obligations. Do not hard-code arbitrary erasure periods.

## Payroll/statutory configuration
- EPF/ETF percentages are stored in effective-dated `statutory_rate_profiles`, not hard-coded into historical payroll lines.
- The initial profile uses EPF 8% employee / 12% employer and ETF 3% employer as setup defaults; the payroll administrator must verify applicable legislation and employee coverage before production use.
- APIT calculation is deliberately disabled by default. Configure the current IRD schedule and validate it before enabling automatic tax calculations.
- Every payroll line stores a calculation snapshot so a later rate change does not rewrite historical reasoning.

## Labour / rostering / overtime
- Overtime rules vary by employment category and applicable legislation. The system stores rate multipliers and policy rules as configuration.
- Do not treat a generic 1.5 multiplier or 240-hour divisor as universally legally correct. They are operational defaults only and should be replaced with the organisation's approved rules.
- Add rest-period, maximum-hours and holiday rules through `workforce_policy_rules` after legal/HR validation.

## Governance workflow recommendations
1. Attendance corrections: Employee/Manager request -> authorised reviewer.
2. Leave: Employee -> Manager/Unit In-Charge -> optional HR.
3. OT: Manager/Unit In-Charge -> authorised approver -> payroll inclusion.
4. Contract: HR prepare -> authorised management approval.
5. Payroll: Prepare -> Check -> Approve -> Finalize/Lock -> Bank/statutory output.
6. Locum/session payment: Session record -> clinical/admin approval -> payroll inclusion.
7. Configuration changes: Super Admin only, audit every change.

## High-risk controls still required in the host project
- Route/policy permissions for payroll, contracts, bank data and statutory setup.
- MFA for privileged HR/payroll administrators.
- Secure backup/restore, key management and encryption-key rotation procedures.
- Data breach/incident workflow and access review process.
- Formal retention schedule and privacy notices.
- Testing with current employment/tax rules before production payroll.
