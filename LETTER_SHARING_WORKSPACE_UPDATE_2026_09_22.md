# Letter Sharing / Live Letter Workspace Update — 2026-09-22

Laravel 10 update scoped to the existing Letter Sharing workflow. The main Carder Management application shell/styles are unchanged; new styling is isolated in `public/css/letter-sharing-workspace.css`.

## UI changes
- Reworked Letter Sharing table to match the supplied collaboration mockup direction.
- Added workflow tabs, search, mode filter, status filter, recipient/read counts, due dates, last-modified state and archive actions.
- Reworked Live Letter Workspace into a document-centric editor with toolbar, official letter canvas, active-viewer presence, comments, suggestions, document information, version history, workflow and safeguards panels.
- Existing main navigation/header remains unchanged.

## Collaboration patterns added
Interaction patterns commonly found in modern document collaboration products are implemented without introducing anonymous cloud-style sharing:
- Autosave and keyboard save (`Ctrl/Cmd+S`).
- Presence / active viewers.
- Selected-text comments.
- Non-destructive suggestions (suggestions never change official content automatically).
- Comment/suggestion resolution while preserving the record.
- Version history and restore-as-new-version.
- Word count, find, copy, list/indent helpers and insert-date helper.
- Read receipts and reviewer status visibility.
- Review due date.
- Governed archive / restore from archive.

## Governance / legal safeguards
- Identity-based recipients only; no anonymous/public link feature.
- Viewer, Commenter, Editor and Reviewer access levels.
- Formal review locks document content.
- All designated Reviewers must approve before final approval.
- Rejection/return requires a reason.
- Approved and issued content remains immutable at the model layer.
- SHA-256 integrity hash includes core content plus governance metadata.
- Classification, personal-data indicator, retention category and handling note remain visible.
- Every restored revision creates a new version rather than overwriting history.
- Feature disablement remains controlled by Super Admin through `feature_live_letter_editing`; existing records stay readable when disabled.

## Database
Run Laravel migrations, or apply:
`database/manual/2026-09-22-live-letter-collaboration-enhancements.sql`

New fields:
- `letters.review_due_date`
- `letters.archived_at`
- `letter_comments.comment_type`
- `letter_comments.quoted_text`
- revision copies of retention/access/due-date metadata

## Important implementation note
This update retains the existing collision-safe Laravel autosave model. It shows active collaborators and prevents silent overwrites via `editor_lock_version`; it does not claim CRDT/OT character-level simultaneous merging. That avoids silently merging conflicting official correspondence changes without a dedicated collaboration service.
