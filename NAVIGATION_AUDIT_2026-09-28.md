# Navigation Audit — Private Workforce / Roster

## Issues found

1. Workforce Dashboard existed as a route/view but had no navigation item.
2. Roster exposed only the overview link; Templates, Plans and My Roster Approvals were missing from navigation.
3. Roster approval routes inherited roster-creator role middleware, preventing workflow-category approvers such as Consultants from reliably accessing the approval queue.
4. The approval queue returned every pending approval to any admitted user before the per-action check, which could expose unrelated roster metadata.
5. Approval-list links opened `roster.plans.show`, but that route was restricted to roster creators, so a Consultant approved through category-based workflow could receive a 403 when reviewing the roster.
6. Workforce navigation was seeder-only; an existing deployment running migrations without rerunning seeders could miss all newly-added menu items.
7. New Workforce/Roster route labels were not present in the trilingual navigation dictionaries.

## Corrections applied

- Added Workforce Dashboard.
- Added Roster Overview, Roster Templates, Roster Plans and My Roster Approvals.
- Kept roster creation/maintenance with Super Admin, Admin Group, Planning Officer and Unit Manager / Unit In-Charge.
- Made Roster Approvals workflow-driven using configured user IDs, roles or user categories.
- Added `canAccessRosterApprovals` to the whitelisted navigation custom checks.
- Filtered the approval queue to approvals the signed-in user can actually action.
- Allowed configured approvers read-only access to a roster plan detail while retaining creation/edit/start/complete controls for operational managers.
- Added migration-based `nav_items` sync so existing installations receive the new menu entries during upgrade.
- Added English, Sinhala and Tamil navigation labels.
- Preserved Super Admin feature disablement through the existing `FeatureToggleService`.

## Expected Workforce menu

- Workforce Dashboard
- Roster Overview
- Roster Templates
- Roster Plans
- My Roster Approvals (only when the account has an approval responsibility)
- Attendance
- Leave
- Overtime
- Contracts
- Payroll
- Locum / Sessions
- Cost Centre Analytics
- Employee Self-Service

## Expected System menu for Super Admin

- Workforce Configuration
- Roster Workflow Configuration

Feature-disabled modules remain hidden through `FeatureToggleService::routeEnabled()`.
