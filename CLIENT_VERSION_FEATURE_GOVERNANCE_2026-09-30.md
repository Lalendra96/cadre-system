# Client Version & Feature Governance Update — 2026-09-30

## Implemented

- Added the dedicated `client_feature_manager` role, displayed as **Client Version & Feature Manager**.
- The role can access only **Client Version & Feature Management** and does not inherit Super Admin, HR, payroll, PII, security-console, audit-export, or operational approval authority.
- Added client deployment metadata: institution/client name, edition/plan, application version, and release channel (`stable`, `pilot`, `testing`).
- Each save requires a change reason and creates an immutable `client_deployment_versions` snapshot containing the client-manageable feature state and actor.
- Changes are also written to the existing audit log.
- Client-manageable features continue to use the existing route/menu feature guards and safe-disable pattern; disabling a feature does not delete historical records.
- Dependency validation prevents enabling Overtime without Attendance and Locum/Sessions without Contracts.

## Payroll governance boundary

- Internal Payroll is **disabled by default** in both `FeatureToggleService` and the private-workforce installation migration.
- A forward migration sets `feature_payroll = false` and records `payroll_external_system_authoritative = true` for existing installations.
- Payroll and payroll-dependent Cost Centre Analytics are excluded from the Client Version & Feature Manager's controls.
- The Super Admin can make an exceptional internal Payroll enablement only with an explicit governance reason; the reason is audit logged.
- Payroll historical data is not deleted when the module is disabled.
- The implementation treats the separate Sri Lankan Government-approved payroll system as the authoritative payroll source by configuration rather than duplicating or silently replacing that authority.

## Least privilege

The new role is deliberately excluded from `User::OPERATIONAL_ROLES` so recognizing the role does not accidentally grant HR Responsibility access or other operational capabilities that check for a recognized operational role.

## Deployment

Run normal migrations after replacing the project files. Assign `client_feature_manager` through the existing Super Admin user-role interface only to personnel responsible for client edition/version and feature packaging.
