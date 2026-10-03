# Letter Sharing — Governed Live Editing Update

This update keeps Live Letter Editing inside the existing **Letter Sharing** workflow in Laravel 10.

## User workflow
1. Subject Officer opens **Letter Sharing → Share a Letter**.
2. Choose either **Share existing files** or **Compose & collaborate live**.
3. Select individual recipients and, for a live letter, assign one of: Viewer, Commenter, Editor, Reviewer.
4. Owner/editors work in the Live Workspace. Autosave creates immutable revision snapshots and optimistic locking prevents silent overwrites.
5. Owner submits for formal review. Content becomes locked.
6. Reviewer records Reviewed / Approve / Return for correction.
7. If all designated reviewers approve, the letter is sealed with a SHA-256 content hash and becomes immutable.
8. Owner/Super Admin may mark the approved record as Issued.

## Governance / legal safeguards
- Least-privilege per-letter access levels.
- Individually selected recipients; no implicit whole-group distribution.
- Draft/review/approval/issue state machine.
- No silent content changes after submission for review.
- Approved and issued records are immutable at model level.
- Revision snapshots retain author, timestamp, hash and reason.
- Comments are separate from official content.
- Personal-data indicator, classification, retention category and handling note are visible in the workspace.
- Audit events are written for autosave, comments, review submission, return, approval, restore and issue.
- Presence is informational only and does not grant permission.
- Super Admin feature disablement blocks new/edit/reopen/restore operations without deleting historical records.

## Super Admin feature control
**Admin → Feature Management → Live Letter Editing (Letter Sharing)**

When disabled:
- attachment-based Letter Sharing remains operational;
- existing live letters remain readable for audit/records purposes;
- live autosave/reopen/restore/new-live-letter operations are denied;
- no historical letter content is deleted.

## Database
Preferred: run Laravel migrations.

If the deployment does not permit CLI migrations, use:
`database/manual/2026-09-21-letter-sharing-live-editing.sql`

## Collaboration implementation note
This Laravel-only build uses short-interval autosave, presence heartbeats and optimistic locking. It safely supports multiple people working on the same live letter but deliberately prevents silent last-write-wins overwrites. Character-level CRDT merging (Google Docs style where two users type in the same paragraph at the exact same moment) would require an additional self-hosted WebSocket/CRDT service such as Yjs/Hocuspocus; it is not required for this governed workflow and has not been introduced as an external dependency here.
