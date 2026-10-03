# Career Events & Workforce Timeline — 2026-09-22

## Added

- New governed `Career Events & Workforce Timeline` under the Workforce navigation.
- 13-month timeline with automatic Today marker.
- Recorded event sources:
  - Grade promotions
  - Retirement projects
  - Employee increments
  - Training expiry
  - Professional registration expiry
  - Transfers
  - Interdiction/review events
- Event filters, month selector, search for authorised identity-level users, print layout and summary cards.

## Role access

- Super Admin: institution-wide identity-level view.
- Planning Officer: institution-wide identity-level view for formal workforce planning.
- Admin Group: navigation access with an identity-masked aggregate planning view. This intentionally does not override the existing rule that Admin Group does not receive Employee Profile access.
- Subject Officer: scoped to employees allocated to their effective HR responsibility.

## Governance safeguards

- Uses recorded workflow data only; no inferred promotion or retirement entitlement is created by the timeline.
- Time-sensitive priority is based only on proximity of the next recorded date and is not an automated employment decision.
- Existing workforce scope rules are reused instead of creating a parallel permission model.
- Admin Group personal identifiers are masked for data minimisation.
- UI explicitly requires users to verify applicable service minutes, approvals and official records before consequential action.
- No new employee data is duplicated into a timeline table; the page reads from authoritative source records.

## Deployment

A migration is included to register the navigation entry automatically:

`database/migrations/2026_09_22_113500_add_career_events_timeline_navigation.php`

The existing `NavItemSeeder` was also updated so fresh installations receive the same navigation entry. No new employee/business-data table is introduced.
