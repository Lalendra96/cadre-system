# Carder Management — Workforce Completion Phase 3

## Scope

This phase adds private-sector workforce extensions while preserving the traditional Sri Lankan Government workflows already represented in Carder.

## Sector-aware defaults

The following are **enabled by default only when the configured client edition is Private / GP / Clinic / Commercial**:

- Biometric / multi-punch attendance adapters
- Leave accrual / carry-forward support
- Contract renewal / probation workflow
- Locum pool / booking / reconciliation
- Workforce demand forecasting
- Advanced roster optimization

For Government / Custom editions these start **disabled**. A Super Admin may opt in only through the governed feature configuration. A governance reason is required when a Government deployment enables one of these private-sector support features.

Existing Government defaults remain unchanged:

- Payroll: disabled; external Government-approved payroll system remains authoritative.
- Digital Leave Management: disabled; paper/external leave process remains authoritative unless formally adopted.
- Approved cadre, appointments, transfers, subject-code responsibility, retirement, increments and official reporting workflows are not modified by Phase 3.

## 1. Biometric and multi-punch attendance

### Implemented

- Device/integration registry.
- Suprema BioStar 2 TA REST adapter pattern.
- Generic REST adapter for other vendor middleware.
- Encrypted adapter credentials/configuration.
- External device user ID to Carder Employee mapping.
- Raw punch-event register.
- Event deduplication using SHA-256 event hashes.
- Multi-punch aggregation to daily attendance.
- Odd-punch exception detection.
- Manual day rebuild from stored punch events.
- Device sync status and error tracking.

### Privacy boundary

Carder **does not store fingerprint templates, face templates, iris templates, or vendor biometric feature vectors**. These remain in the vendor platform/device. Carder stores only the minimum attendance event metadata needed for workforce operations: external user reference, time, punch type, device/source reference and processing status.

For a production biometric deployment, perform the organisation's required privacy/legal assessment, document purpose and retention, control device/vendor access, and restrict attendance export/access by role.

## 2. Private-sector leave accrual/carry-forward support

### Implemented

- Effective-dated accrual policies.
- Contract-type-specific policies.
- Monthly or annual accrual.
- Annual cap.
- Carry-forward enable/disable and cap.
- Preview before applying.
- Audited accrual run.
- Audited year-to-year carry-forward.
- Governance note required for application.

### Authority boundary

This remains a **support system**. It does not automatically approve leave or replace an official paper/external leave record merely because accrual calculations are enabled. Government deployments keep this feature disabled by default.

## 3. Contract renewal and probation

### Implemented

- Probation review scheduling.
- Confirm / extend / terminate decision.
- Review note and reviewer/time audit fields.
- Contract renewal request.
- Proposed effective dates and revised terms.
- Approve/reject renewal workflow.
- Approved renewal creates a new effective-dated contract and closes the prior contract as renewed rather than overwriting history.

## 4. Locum pool, booking and reconciliation

### Implemented

- Locum pool profile.
- Availability state.
- Preferred session/hourly rate capture.
- Booking request.
- Manager approval/rejection.
- Approved booking creates a draft locum session.
- Performed-session record remains separate from booking.
- Session reconciliation with Verified / Exception states.
- External payment reference support without enabling Carder's internal Payroll.

This allows Carder to reconcile locum work against an external payroll/payment system while internal Payroll remains disabled.

## 5. Workforce demand forecasting

### Implemented

- Unit-level demand inputs.
- Supported metrics include patient visits, admissions, bed-days, theatre cases, clinic visits, lab tests and custom values.
- Configurable units-per-FTE rules.
- Optional position-specific rules.
- Minimum FTE floor.
- Average demand across a selected period.
- Required FTE vs actual headcount gap display.

### Government boundary

Forecast output is planning evidence only. It **does not alter approved cadre, recruit staff, transfer employees, modify appointments, or change an official workforce report**.

## 6. Advanced roster optimization

### Implemented

Human-in-the-loop employee suggestions using:

- Existing hard compliance rules.
- Employee availability.
- Weekly duty load.
- Night-duty distribution.
- Skill/compliance warnings.
- Configurable decision-support weights.

The optimizer never publishes or amends a roster automatically. Normal Unit In-Charge creation, configured approvals, amendment versioning and audit trails continue to apply.

## Public SDK/API integration design

The attendance integration intentionally does not bundle a proprietary vendor binary into the Laravel project.

- **Suprema BioStar 2 TA API**: integrated through its documented REST API pattern. Punch endpoint/path and response field mapping are configuration because deployed BioStar versions can differ.
- **Suprema G-SDK**: can be used by a local gateway/bridge where direct device communication is required; Carder can then consume the bridge through the Generic REST adapter.
- **Generic REST**: supports other public/vendor SDK bridges without coupling core HR logic to a device manufacturer.

This design keeps vendor SDK upgrades outside the HR core and avoids importing biometric templates into Carder.

## Governance controls added

- Private-sector feature defaults are sector aware.
- Government opt-in requires a written governance reason.
- Feature disablement remains non-destructive.
- Device credentials are encrypted using Laravel encrypted casts.
- Raw punch events are immutable/deduplicated by event hash.
- Biometric templates remain external.
- Human decision remains mandatory for roster optimization.
- Demand forecasting is advisory only.
- Leave automation is marked support-only.
- Contract renewal creates a new effective-dated record rather than overwriting history.
- Locum booking, session and reconciliation are separate auditable stages.

## Government workflow interference review

Phase 3 was deliberately isolated behind new feature flags and new tables. It does not change the established Government workflows for:

- approved cadre,
- employee substantive unit/position,
- subject-code responsibility,
- transfers,
- increments,
- retirement,
- official reports,
- external Government payroll authority,
- paper/external Government leave authority,
- existing Roster approval workflow.

The new features are optional support layers. Disabling them does not remove existing data.

## Deployment checklist

1. Run database migrations.
2. Confirm **Client Edition / Plan** under Client Version & Feature Management.
3. Government installation: verify the six Phase-3 private-sector support features remain disabled unless specifically approved.
4. Private/GP installation: review the six default-enabled capabilities and disable any not contractually required.
5. For biometrics, configure only vendor attendance API/event credentials. Do not import biometric templates.
6. Validate employee-device mappings before processing attendance.
7. Configure private leave policy rules from the client's approved HR policy; do not rely on example values as legal entitlements.
8. Configure contract and locum workflows according to signed employment/engagement terms.
9. Treat demand forecasts and roster optimization as decision support, not automated authority.
10. Review role access, retention, export permissions and audit logs before production use.
