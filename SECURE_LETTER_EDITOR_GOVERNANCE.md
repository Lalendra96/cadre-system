# Secure Live Letter Editor — Governance & Legal-Safeguard Design

## Purpose
This update turns Service Letters into a controlled official-document workspace while preserving the existing Laravel/PostgreSQL architecture and approval workflow. The design supports institutional governance and privacy obligations; it does **not** claim that software alone guarantees legal compliance.

## Safeguards implemented

### 1. Need-to-know access
- Workspace viewing reuses the existing workforce-scope / role checks.
- Only the originating drafting officer may alter a draft (Super Admin retains controlled recovery access).
- Reviewers can comment without silently changing the official text.

### 2. Approved records are immutable
- Once a Service Letter reaches `approved`, the model rejects changes to official content/identity fields.
- Approval stores a SHA-256 `approved_content_hash`.
- The document-record screen verifies the current content against the stored approval hash and displays an integrity warning if they differ.

### 3. Version history and conflict protection
- Every material autosave creates a `service_letter_revisions` snapshot.
- Each snapshot records author, timestamp, reason and SHA-256 hash.
- Restoring a revision creates a **new** revision; history is never overwritten.
- `editor_lock_version` provides optimistic concurrency control. A stale browser receives HTTP `409` instead of silently overwriting a newer version.

### 4. Audit minimisation
- Editor/comment/workflow events are written to the central audit log.
- Full letter text is intentionally not duplicated into audit logs for editor events, reducing unnecessary copies of potentially sensitive personal information.

### 5. Explicit document handling metadata
The editor makes the following visible to staff:
- document classification: Internal / Confidential / Restricted / Public;
- whether personal data is present;
- access/handling note;
- official-record retention category;
- mandatory official reference number before review.

### 6. Safe-by-default editor
- The official letter body is stored as plain text rather than arbitrary user HTML.
- This deliberately avoids hidden markup/script content and reduces unsafe formatting ambiguity.
- Common official-document helpers are provided: bullet, indent, date, page-break marker and signature block.

### 7. Review comments and presence
- Authorised users can add and resolve review comments.
- Presence heartbeats show who currently has the document workspace open.
- Presence is informational; it does not grant edit rights.

## Workflow
1. Draft is created from the existing employee/template workflow.
2. User enters the Secure Letter Editor.
3. Changed content autosaves after a short idle period.
4. Each material save is versioned and hashed.
5. Official reference number + classification are required before submission.
6. Submission locks drafting and moves the letter to review.
7. Administrative Officer / authorised approver reviews and e-signs using the existing approval process.
8. Approved content becomes immutable and is protected by an approval hash.

## Database migration
Run the included migration:

`2026_09_21_181500_add_secure_live_editor_to_service_letters.php`

It adds governance fields to `service_letters` and creates:
- `service_letter_revisions`
- `service_letter_comments`
- `service_letter_presence`

## Important deployment note
Back up the production database before applying migrations. Existing Service Letters receive safe defaults for the new governance fields; their version history begins when the new editor starts saving them.

## Future phase (not included in this update)
True character-level simultaneous editing (Google Docs-style CRDT/OT) should be introduced only after the governance workflow is validated. A future phase can add Yjs/WebSockets while retaining the current role checks, immutable approval state, revision snapshots, conflict/audit controls and official-record rules.
