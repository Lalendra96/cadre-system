# Carder Management — Workforce Completion Phase 2

**Build date:** 2026-09-30
**Scope:** Government-safe leave default + governed Roster Phase 2 completion

## Governance defaults

### Digital Leave Management
- `feature_leave_management` is **disabled by default**.
- `leave_external_process_authoritative = true` is created as a governance setting.
- The application explicitly treats the existing authorised paper-based leave process as authoritative unless digital leave is formally enabled.
- Digital leave records are not used as a roster hard-stop while the Leave feature is disabled.
- Enabling digital Leave through Feature Management requires a governance reason when the external/paper process is marked authoritative.
- Disabling the feature never deletes historical digital leave records.

### Payroll
- Internal Payroll remains **disabled by default**.
- The separate Sri Lankan Government-approved payroll system remains the authoritative payroll source under the existing governance configuration.

## Phase 2 Roster features completed

### 1. Employee open-shift claims
- Employees can view suitable open shifts from **My Roster Actions**.
- Position eligibility is checked before a claim is accepted.
- Roster compliance is re-evaluated before submission.
- Claims are stored separately from assignments and require manager review.
- Approved/active rosters are not silently edited: accepted claims become governed roster amendments and are only applied after amendment approval.
- Open-shift capacity is updated only after the amendment is applied.

### 2. True two-way peer shift swaps
- Employee A can select another duty in the same published roster and request a true two-way swap with Employee B.
- Employee B must explicitly accept or decline.
- Peer acceptance is recorded with timestamp and optional response note.
- After peer acceptance, the request moves to manager review.
- Both employees are rechecked for conflicts, availability, contract validity, professional registration, competencies and other configured compliance rules.
- For approved/active rosters, manager acceptance creates a governed amendment instead of editing the published roster directly.
- The two assignments are swapped only when the amendment is approved.

### 3. Replacement / give-away requests
- Employees can request a replacement when a direct two-way swap is not appropriate.
- Manager review remains mandatory.
- Published roster integrity is preserved through the amendment workflow.

### 4. Roster acknowledgement
- Employees can acknowledge their own published/active roster duties.
- Acknowledgement records:
  - assignment
  - employee
  - authenticated user
  - acknowledgement timestamp
  - source IP
  - user agent
- Acknowledgement confirms receipt/visibility of the roster and does **not** replace attendance verification.
- When an assignment changes through an approved amendment, its acknowledgement is reset so the changed duty can be acknowledged again.

### 5. Governed post-approval amendments
- Approved and active rosters are revision controlled.
- Supported amendment actions:
  - add duty
  - update duty
  - replace employee
  - cancel duty
  - two-way swap
- Amendment reason is mandatory.
- Emergency amendments are explicitly flagged.
- Requesters cannot approve their own amendment unless acting as Super Admin under the existing exceptional governance authority.
- The previous roster revision is snapshotted before an approved amendment is applied.
- Each approved amendment increments the roster revision number.
- Rejected amendments leave the published roster unchanged.
- Amendment approval/rejection generates audit records and requester notifications.

### 6. Skill-mix / competency enforcement
- Safe-staffing rules can require a specific employee competency.
- Employee competency expiry is considered.
- Compliance checking evaluates required competencies before an employee is assigned, swapped, used as a replacement, or approved for an open shift.
- Rules may be warnings or hard stops.
- Staffing counts only include employees who satisfy the configured competency when a competency rule is present.

### 7. Safe-staffing enforcement on publication and amendment
- Roster submission checks configured hard minimum staffing rules.
- Cross-unit duty units are checked in addition to the roster's primary unit.
- Amendments are rejected if the resulting roster would leave a configured hard staffing or skill-mix shortfall in an affected unit/time period.
- The modern roster calendar continues to display staffing shortfall indicators.

### 8. Improved conflict checking
- Overnight duties are checked across adjacent dates to prevent overlap bypasses.
- New roster creation now also validates overlap **within the same unsaved batch**, preventing two overlapping duties for the same employee from being created in one submission.
- Minimum rest and weekly-hours warnings remain configurable.

### 9. Employee roster action centre
**My Roster Actions** now provides:
- upcoming published duties
- acknowledgement
- two-way swap request
- replacement request
- open-shift claims
- incoming peer swap requests
- outgoing request/status history

The Employee Self-Service page links to this area only when the Roster feature itself is enabled.

## Security / privacy design
- Employee names continue to be read through the `Employee` Eloquent model so the existing protected-data decrypt accessor is used.
- No JavaScript/client-side PII decryption was introduced.
- Roster actions are scoped to the authenticated employee for employee-facing operations.
- Published-roster modifications use revision-controlled server-side transactions.
- Existing audit logging is used for claims, peer decisions, amendments, approvals and roster changes.

## New database structures
- `roster_open_shift_claims`
- `roster_acknowledgements`
- `roster_amendment_requests`
- peer-response metadata added to `roster_shift_swap_requests`

## New / expanded routes
- Employee open-shift claim
- Employee two-way swap request
- Employee peer response
- Employee roster acknowledgement
- Manager open-shift claim review
- Governed roster amendment create/review

## Still recommended for later phases
These are intentionally not represented as complete in Phase 2:
- employee-initiated open-shift bidding where multiple candidates are ranked by policy
- automatic roster optimization / AI-assisted scheduling
- formal staffing establishment-to-roster demand modelling
- leave accrual/carry-forward automation for private-sector deployments
- multi-punch biometric attendance normalization and device adapters
- contract-renewal/probation workflow completion
- locum pool booking and session reconciliation expansion
- deeper workforce forecasting and budget scenario modelling
- configurable notification preference centre

## Validation performed
- PHP/Blade syntax lint across application, routes, migrations and views
- static route/view reference review for new Phase 2 Roster actions
- check for accidental raw employee-name query usage in Roster/Workforce code
- ZIP integrity validation before delivery
