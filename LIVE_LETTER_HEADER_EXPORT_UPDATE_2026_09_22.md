# Live Letter Sharing — Official Header + Final PDF/DOCX Export Update

## What changed

- Live Letter Sharing now uses the same Super Admin-managed official header profiles as Service Letters.
- Super Admin navigation is labelled **Official Letter Headers**.
- Official Letter Header configuration remains available even if the optional Service Letters module is disabled, because Live Letter Sharing also depends on it.
- New Live Letter and Live Workspace/Edit now expose the same governed metadata set, including:
  - Official Letter Header
  - Reference Number
  - Classification
  - Review Due Date
  - Personal Data indicator
  - Retention Category
  - Access / Handling Note
- Header selection is versioned with live-letter revisions.
- On final approval, the chosen header is snapshotted into the approved record. Later Super Admin header edits cannot silently alter an already-approved letter.
- The final approver's currently registered e-signature is bound to the letter at approval time when available.
- Approved / Issued / Archived live letters can be exported as:
  - Final PDF (A4 via DomPDF)
  - Final DOCX (native OOXML package)
- Final exports include approval identity, approval timestamp, classification, and SHA-256 integrity evidence.
- Final exports are audit logged.
- Export is blocked if the approved content hash no longer matches the current official content.

## Database update

Run either the Laravel migration:

`database/migrations/2026_09_22_230000_add_letterhead_signature_to_live_letters.php`

or, for a manual PostgreSQL deployment:

`database/manual/2026-09-22-live-letter-header-signed-export.sql`

## Governance safeguards

1. Approved content remains immutable.
2. Header configuration changes do not retroactively modify approved letters because the approval-time header is snapshotted.
3. E-signature binding uses the final approving user's active registered e-signature at the time approval completes.
4. Historical letters are never regenerated using a newly edited header profile.
5. PDF/DOCX export is limited to approved, issued or archived records and authorised participants/Super Admin.
6. All final exports are recorded in `export_audit_logs` when export auditing is enabled.
7. No anonymous/public final export URL is introduced.

## DOCX dependency note

The exporter uses PHP `ZipArchive` when available. If the ZIP extension is unavailable, it falls back to `maennchen/zipstream-php`, which is already present in the project's Composer lock through PhpSpreadsheet.
