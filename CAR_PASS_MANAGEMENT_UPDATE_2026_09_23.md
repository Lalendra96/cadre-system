# Car Pass Management Update — 2026-09-23

## Workflow

1. Super Admin enables **Car Pass Management** from Feature Management.
2. Super Admin opens **Car Pass Administration** and formally assigns exactly one active Subject Officer.
3. Super Admin selects the independent approval role: Admin Group, Planning Officer, or Super Admin.
4. Super Admin uploads image-based pass formats and maps each format to eligible Posts/Positions.
5. The assigned Subject Officer searches an active employee using an **exact NIC, Pay No, or Phone Number**.
6. The system loads authoritative Employee, Post and Unit details and lists only active pass formats mapped to that Post.
7. Vehicle Registration Number and Vehicle Type are mandatory.
8. The Subject Officer may save a Draft or submit for independent approval.
9. The configured approving role Approves or Returns/Rejects the request. The preparer cannot approve their own request.
10. Approval creates a SHA-256 integrity seal over the official pass snapshot.
11. The assigned Subject Officer issues the approved pass.
12. Issued passes cannot be edited or deleted. An authorised approver or Super Admin may revoke them with a mandatory reason.

## Governance safeguards

- Single formally assigned operational owner, with assignment history.
- Exact identifier lookup only; no wildcard employee search.
- Need-to-know role checks at every write action.
- Separation of duties between preparation and approval.
- Pass template eligibility is explicitly configured by Post; the system does not infer eligibility.
- Approval-time employee/post/unit/template information is snapshotted.
- Template images are retained on the private local disk and served only through authenticated routes.
- No hard delete for templates or issued passes.
- Mandatory reason for template disablement, rejected requests, and pass revocation.
- Audit logging of creation, updates, approval, issue, revocation and configuration changes.
- Print/export action is audit logged.
- The system does not make automated HR or eligibility decisions.

## Database

Run:

`database/migrations/2026_09_23_054500_create_car_pass_management_tables.php`

or, for PostgreSQL manual deployment:

`database/manual/2026-09-23-car-pass-management.sql`
