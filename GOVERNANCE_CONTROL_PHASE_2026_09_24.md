# Governance Control Phase — 2026-09-24

This update deepens Carder Management governance without adding unrelated HR modules.

## Implemented

### 1. Administrative Decision / Order Register
- Permanent `ADM-YYYY-NNNN` reference.
- Decision type, employee/source linkage, effective date and decision text.
- Approval-matrix / authority reference linkage.
- Governance rule-version linkage.
- Supporting evidence and provenance fields.
- Draft → Checked → Recommended → Approved workflow.
- Approval is blocked when no authority is attached.

### 2. Segregation of Duties
- Workflow stage order is enforced.
- Preparer cannot normally check/recommend/approve the same decision.
- Checker/recommender cannot later approve their own stage.
- Exceptional override requires both a documented reason and authority/reference.
- Overrides remain visible on the decision and governance dashboard.

### 3. Rule Versioning + Effective Dating
- Stable rule key with sequential versions.
- Authority type/reference, effective from/until, applicable category and rule definition.
- New version supersedes rather than overwrites the prior version.
- Historical versions remain available for old decisions.

### 4. Decision Provenance
- Decisions can record source facts/data and the rule/calculation/method used.
- Rule-version and authority linkage are preserved on the decision record.

### 5. Regulatory Change Management
- `GOV-CHG-YYYY-NNNN` reference.
- Circular/service-minute source, applicability assessment, owner and affected modules/rules.
- Status from received → assessment/planning → implementation → testing → implemented/closed.
- Implemented status requires both testing and implementation evidence.

### 6. Records Retention / Legal Hold
- Active/archived/disposal lifecycle.
- Configurable retention years and computed disposal eligibility.
- Legal/administrative hold with reason.
- Disposal cannot be authorized while a hold is active.
- Disposal cannot be recorded until eligibility and authorization are satisfied.
- Disposition evidence/reference is retained.

### 7. Formal Responsibility Handover
- `HND-YYYY-NNNN` certificate.
- Outgoing officer, incoming officer, supervisor and effective date.
- Point-in-time workload snapshot: employee files, promotions, increments, retirements, DQ issues and escalations.
- Outgoing sign-off → incoming acceptance → supervisor verification.
- Evidence hash after verification.
- Downloadable A4 PDF certificate.
- Subject Officers may initiate only their own handover; oversight roles may create broader handovers.

### 8. Governance Compliance Dashboard
Shows control signals including:
- decisions in review;
- decisions without authority;
- SoD overrides;
- expired delegations;
- open regulatory changes;
- retention/disposal due;
- pending handovers;
- high-severity data-quality issues;
- overdue access reviews;
- backup records without restore-test evidence.

## Database migrations
- `2026_09_24_140000_add_governance_control_phase_tables.php`
- `2026_09_24_141000_add_governance_control_navigation.php`

Run:

```bash
php artisan migrate
```

No npm build is required.
