# Employee Profile Completion Tracker — Count Simplification

The tracker now measures only the operational workload requested by management:

- **Handling Count**: the formally configured number of employee profiles handled by a Subject Officer for the mapped Subject Code/Post.
- **Current Profile Count**: active Employee Profile records currently mapped to that officer's effective HR scope for the same Subject Code/Post.
- **Remaining**: `max(Handling Count - Current Profile Count, 0)`.
- **Completion %**: `min(Current Profile Count / Handling Count * 100, 100)`.

Field-level profile completeness is no longer part of this tracker. The former Core Complete, Needs Completion, and quality percentage calculations have been removed from the service, dashboard widget, and tracking screen.

The existing `profile_completion_targets.target_count` database column is retained for backward compatibility and audit history; the UI now presents it as **Handling Count**.

Access remains unchanged:

- Super Admin: institution-wide oversight and handling-count maintenance.
- Planning Officer: institution-wide oversight and handling-count maintenance.
- Admin Group: institution-wide aggregate read-only view.
- Subject Officer: only their effective assigned Subject Code/Post scope.

The tracker does not score officer performance or infer missing employee identities.
