# Carder Management — Feature Completion Audit

**Build audited:** Client Version & Feature Governance build supplied on 2026-09-30
**Audit scope:** source code, routes, migrations, controllers, models and visible Blade workflows.
**Status terms:** **Completed** = usable end-to-end foundation exists; **Partial** = data model or limited workflow exists but important production behavior remains; **Not implemented** = no meaningful implementation found in this build.

## Completed / substantially implemented

### Core HR, cadre and governance
- Employee master/profile management with unit/position linkage.
- Cadre, unit, position, categories/subcategories and allocation management.
- HR responsibility ownership/history, handover, reassignment and escalation foundations.
- Employee lifecycle records including increments, promotions, transfers, retirement projects and related intelligence.
- Employee qualifications, documents, competencies and training records.
- Data-quality issue management and duplicate-resolution workflow foundations.
- Official reporting, signed workforce snapshots and governance/audit infrastructure.
- PII encryption/decryption through the Employee model and protected personnel-data handling.
- Security sessions/events, MFA foundation, audit logs and export audit logging.
- Feature management, governed safe disablement and client deployment/version governance.
- Dedicated `client_feature_manager` role for client edition/version and permitted feature controls.
- Payroll disabled by default and marked as externally authoritative for the current government deployment policy.

### Roster
- Reusable roster templates and multiple time slots.
- Modern custom calendar roster builder with time-slot legend and employee initials.
- Employee initials generated from decrypted employee name (for example Keerthi Tennakoon → KT).
- Cross-unit roster coverage without changing the employee's permanent/home unit.
- Draft → approval → approved → active → completed lifecycle.
- Configurable approval workflow with Consultant / Director / Deputy Director-style approver resolution.
- Roster revision/snapshot table and audit logging foundation.
- Employee/leave overlap conflict checking foundation.
- Roster plan start/completion workflow.
- MD3/custom Material styling for roster pages.

### Workforce operations already present before this completion phase
- Attendance records with manual/QR/biometric/import method definitions.
- Attendance records can link to a roster assignment.
- Leave types, leave requests and leave-balance tables.
- Overtime request/approval records with roster/attendance references.
- Employee contracts supporting permanent, probation, fixed-term, part-time, hourly, locum, visiting and sessional types.
- Contract rate fields for salary/hour/session/patient/revenue share.
- Locum/session recording and session/hour/per-patient/revenue-share/hybrid calculation.
- Cost-centre entities and basic payroll-derived cost-centre analytics.
- Employee Self-Service dashboard foundation.
- Payroll calculation/finalization foundation remains present but **disabled by default** by governance policy.

## Completed in Workforce Completion Phase 1

### Roster availability and open shifts
- Employee availability/preference records by date and optional time window.
- Preferred Morning / Evening / Night / On-call shift indicator.
- Employee self-service availability entry.
- Open/unfilled shift entity with required and filled staffing counts.
- Manager assignment of open shifts after compliance validation.

### Shift-change / swap foundation
- Shift replacement/swap request record.
- Employee self-service request for a replacement/change to an assigned duty.
- Manager approval/rejection path.
- Approved replacement updates the roster assignment while preserving auditability through the request record.

### Stronger roster compliance validation
- Overlapping duty hard-stop.
- Approved leave hard-stop.
- Employee-marked unavailable hard-stop.
- Availability-window warning.
- Expired contract warning/hard-stop logic.
- Expired professional registration hard-stop.
- Configurable minimum-rest warning.
- Configurable maximum-weekly-hours warning.
- Public-holiday warning.
- No client-side PII decryption introduced.

### Safe staffing and fairness
- Configurable unit/day/time/position safe-staffing rules.
- Optional hard-stop flag stored per staffing rule.
- 30-day roster fairness visibility for total, night, weekend and cross-unit duties.
- Governance note retained: fairness metrics support review; they do not make employment decisions automatically.

### Attendance completion
- Planned-vs-actual attendance comparison when attendance is linked to a roster assignment.
- Worked-time variance display.
- Late and early-departure calculation foundation.
- Employee attendance-correction request from Self-Service.
- Manager correction approval/rejection.
- Approved correction updates the attendance record and verifier metadata.

### Leave completion
- Balance-aware leave request validation.
- Half-day policy enforcement from leave type.
- Leave balance created from configured entitlement when first required.
- Paid leave blocked when insufficient balance exists.
- Approved leave increments balance usage.
- Current leave balances displayed to managers.

### Holiday governance
- Effective configurable Public / Mercantile / Organisation holiday calendar.
- Holiday dates are not hard-coded into business logic.
- Roster conflict/compliance service surfaces holiday-duty warning for policy review.

## Partial / still unfinished

### Roster
- Peer-to-peer acceptance stage for a true two-sided shift swap is not yet complete; current Phase 1 supports employee request + manager replacement approval.
- Open-shift self-claim/bidding by employees is not yet complete; managers currently assign open shifts.
- Staffing rules store competency requirements, but competency matching is not yet enforced by the compliance service.
- Safe-staffing shortfalls are not yet visualized directly on every calendar cell in the plan builder.
- Automatic roster generation/suggested best employee is not yet implemented.
- Post-approval emergency amendment/version workflow needs a dedicated UI and approval policy.
- Roster acknowledgement is stored but employee acknowledgement workflow is not complete.
- Fairness analytics are basic counts; normalized workload/FTE and historical trend analysis remain outstanding.

### Attendance
- Native biometric vendor/device connectors are not implemented; the schema supports biometric/import sources but not specific device protocols.
- Bulk attendance import UI, validation preview and import history are not complete.
- Breaks, split shifts and multiple punches per day are not yet modeled.

### Leave
- Automatic monthly accrual engine is not yet implemented.
- Year-end carry-forward processing/expiry is not yet automated.
- Leave attachment upload/medical certificate workflow is not complete.
- Unit-wide leave calendar and staffing-impact preview are not complete.
- Encashment and compensatory leave generation are not complete.

### Overtime
- OT is request/approval based, but automatic recommendation from planned-vs-actual attendance is not complete.
- Effective-dated OT rules by employee regime/category are not yet complete.
- Public-holiday/weekend policy calculation is not automatically applied; this is intentionally left configurable pending applicable legal/employment-policy validation.

### Contracts
- Contract renewal workflow, renewal recommendation, version history and employee acknowledgement are not complete.
- Probation review/extension/confirmation workflow is not integrated with contracts yet.
- Contract document generation/signing is not complete.

### Payroll
- Internal payroll remains intentionally disabled by default for this deployment because a separate approved external payroll system is authoritative.
- Arrears/retroactive payroll, bank export, payroll reconciliation/anomaly checks, payslip dispute workflow and accounting export remain unfinished.
- These should only be completed/enabled for a client whose governance model explicitly permits internal payroll.

### Locum / session management
- Availability pool and locum booking/acceptance workflow are not complete.
- Session reconciliation against patient volume/revenue source systems is not integrated.
- Invoicing/payment settlement workflow is not complete.

### Cost-centre analytics
- Current analytics are basic payroll aggregation.
- FTE, budget-vs-actual, forecast, revenue-vs-workforce-cost and non-payroll labor cost allocation remain unfinished.

### Employee Self-Service
- Availability, roster-change requests and attendance-correction requests are now present.
- Leave submission directly from ESS, policy/document acknowledgement, notification preferences and payroll query workflow remain unfinished.

### Commercial / multi-client productization
- Client Version & Feature Manager is implemented.
- Full tenant data isolation, per-client branding, subscription billing and plan enforcement are not yet implemented.

### Integration and automation
- Internal workforce summary API exists, but a versioned external integration API and webhook/event platform are not complete.
- Scheduled recurring workforce reports and delivery are partial rather than comprehensive.
- PWA/offline transaction queue for ESS is not implemented.

## Recommended next completion order

1. **Roster Phase 2:** peer swap acceptance, employee open-shift claim, competency/skill-mix validation, calendar staffing shortfall overlays, post-approval amendments.
2. **Leave Phase 2:** accrual scheduler, year-end carry-forward, leave calendar, attachments and compensatory leave.
3. **Attendance Phase 2:** multi-punch/breaks, governed import centre, device connector abstraction and OT suggestions.
4. **Contracts Phase 2:** renewal/probation workflow, versioning, generated contract documents and acknowledgement.
5. **ESS Phase 2:** leave request, roster acknowledgement, notifications/preferences, document/policy acknowledgement.
6. **Locum Phase 2:** locum pool, booking/acceptance and clinical/revenue reconciliation.
7. **Analytics Phase 2:** FTE, absenteeism, turnover, staffing demand, budget-vs-actual and workforce cost forecasting.
8. **Payroll:** keep disabled unless a future client governance decision explicitly replaces or integrates the authoritative external payroll system.
