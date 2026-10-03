# Private Workforce / GP Merge — 2026-09-28

Merged into the Carder Management Professional UI source and normalized to the existing MD3/custom Material design tokens.

## Added
- Roster templates, plans, cross-unit assignments and configurable approval workflow
- Attendance
- Leave management improvements
- Overtime
- Employee contracts
- Payroll engine and statutory profiles
- Employee Self-Service
- Locum/session payments
- Cost-centre analytics

## Super Admin feature controls
All optional modules are registered in the existing `FeatureToggleService`, the same control plane used by Car Passes and Utility Bills. Disabling a module preserves its data and route-protects end-user access.

## Governance safeguards
- Audit services for workforce and roster actions
- Payroll finalization/locking workflow
- Effective-dated statutory rate profiles
- Sensitive payroll profile fields encrypted by model casts/services where defined
- Cross-unit roster duty does not change the employee's permanent unit
- Non-destructive feature disablement
- Configurable approval chains

See `GOVERNANCE_LEGAL_DESIGN_PRIVATE_WORKFORCE_2026-09-28.md` for implementation notes.
