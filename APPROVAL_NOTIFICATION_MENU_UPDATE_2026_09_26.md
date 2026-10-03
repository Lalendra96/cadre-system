# Approval + Navigation Notification Update — 2026-09-26

## Added
- Live notification-count badges on suitable sidebar menu items.
- Counts refresh together with the existing notification polling endpoint (30-second polling; no websocket/Node requirement).
- New **Requests for Approval** action-centre page with an aggregate pending count.
- New approval notifications use the title **New Request for Approval**.

## Approval workflows connected
- Service Letter approval requests.
- Cadre Review Director-stage approval requests.
- Car Pass independent approval requests.

## Menu badge mappings
- Service / Official Letters
- Cadre Reviews
- Car Passes
- Letter Sharing
- HR Intelligence
- Requests for Approval
- Notifications (where a navigation entry exists)

## Security / access behavior
- Notification queries remain scoped to the authenticated user.
- Approval inbox counts are filtered by the user's actual role/approval authority.
- The approval inbox links to the existing workflow pages; existing controller authorization remains authoritative.
- No approval is performed from the badge or inbox itself.

## Database
Run the normal Laravel migration for:
`2026_09_26_120000_add_approval_requests_navigation.php`

For manually maintained databases, the equivalent optional SQL is included at:
`database/manual/2026-09-26-approval-notification-menu.sql`
