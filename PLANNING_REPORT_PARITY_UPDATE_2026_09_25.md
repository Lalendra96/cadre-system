# Planning Report Parity Update — 2026-09-25

## Purpose
Provide the Planning Officer and Medical Officer Planning with management-level reports comparable to the Admin Group where the information is required for hospital planning, while preserving governance boundaries.

## Added / aligned access
- Summary & Charts
- Submission Monitoring
- Trend Analysis
- Year-on-Year Analysis
- Historical Snapshot
- Retirement Projections
- Unit Post Availability
- Unit Allocations
- Official Reports / signed snapshot status
- Workforce planning scenario tools already available through the planning workspace

## Medical Officer Planning
The `Medical Officer Planning` Admin Group category can now enter:
- Planning Intelligence
- Hospital Planning Decision Support / assessment register

This access is category-gated through `User::canAccessPlanningReports()`; it is not granted to every Admin Group category by those planning-specific menu items.

## Governance boundary
Planning reports are decision-support only. They do not approve recruitment, transfer, procurement, expenditure, service reconfiguration, establishment changes, or other consequential actions. The user interface instructs planning users to validate source records, assumptions, authority and required approvals.

## Explicit exclusions
Planning report access does not grant Planning Officers Super Admin configuration access or raw Audit Log administration. Existing approval and role-specific operational controls remain separate.
