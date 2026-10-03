# Carder Management — Governance Full Integration Phase

Date: 2026-09-24

This phase integrates the governance-control layer into the existing HR/establishment transaction workflows instead of maintaining a parallel governance register.

## 1. Unified Administrative Decision Register

The pre-existing `administrative_decisions` human-approval table and the newer governance register are now unified by migration `2026_09_24_142000_unify_governance_decision_integration.php`.

Existing decisions are backfilled with:
- permanent `ADM-YYYY-NNNNNN` decision reference;
- preparer identity;
- governance title/source type;
- migration provenance evidence.

New transactional decisions automatically capture:
- decision number;
- effective date;
- source workflow/record where available;
- approval-matrix entry where configured;
- applicable versioned governance rule where configured;
- authority/reference;
- employee source facts and submitted field names;
- rule/authority provenance.

## 2. Enforced Segregation of Duties

Consequential administrative decisions now use:

`Prepared → Checked → Recommended → Approved`

The same officer is blocked from performing multiple stages unless an exceptional override is recorded with BOTH:
- override reason; and
- authority/reference.

Final approval also requires an authority/reference and applies the underlying HR transaction only after final approval.

Where an Approval Authority Matrix is configured, checker/approver role/category requirements are enforced. An effective authority delegation can satisfy the configured requirement.

## 3. Versioned Rules + Effective Dating

Governance rules:
- remain versioned;
- preserve superseded versions;
- automatically end the previous version on the day before a new version becomes effective;
- reject historical period overlaps;
- are resolved against the decision effective date;
- may optionally be limited to an employee position/category.

Common process aliases are resolved automatically, e.g. `retirement_completion` → `RETIREMENT`, `grade_change` → `PROMOTION`, transfer corrections → `TRANSFER`.

## 4. Decision Provenance

Automatic provenance includes, as applicable:
- employee/pay number;
- DOB;
- appointment date;
- retirement age;
- current position/unit/subject code IDs;
- increment dates;
- submitted workflow fields;
- source/reference;
- rule key/version/effective period;
- approval authority/source;
- capture time and method.

The Administrative Decision Review screen displays this as readable key/value evidence instead of raw JSON.

## 5. Integrated Workflows

### Already decision-gated and now upgraded to full governance stages
- grade changes;
- increment records;
- increment workflow decisions;
- transfers;
- acting appointments;
- interdiction/disciplinary records;
- confirmation in service;
- leave/LWOP.

### Newly governance-gated
- transfer corrections;
- acting-appointment corrections;
- sensitive employee correction requests;
- bulk employee corrections;
- retirement completion.

No authoritative employee/HR record is changed by these actions until final governance approval.

### Promotion workflow
Promotions now enforce:

`Prepared → Checked → Recommended → Approved`

with distinct officers at each stage. Approval-matrix role/delegation rules are applied when configured. Final approval creates a permanent Administrative Decision/Order record linked to the promotion.

### Cadre review
The existing Director/MoH external-decision workflow is retained. When the proposal reaches final MoH-approved state, a permanent governance decision is registered with the proposal item snapshot, Ministry reference and an explicit provenance note that the final decision is external rather than an invented internal checker/recommender action.

## 6. Retirement Completion

A retirement project cannot move directly to Completed.

Before completion can be submitted, the system requires:
- pension status = completed;
- clearance status = completed;
- handover status = completed;
- DOB verified;
- service verified;
- contact verified;
- documents verified.

The final completion then goes through the governed Administrative Decision workflow and is only applied on approval.

## 7. Employee Corrections

Sensitive correction requests are validated by the first reviewer but the employee master is NOT changed at that point.

Approved correction requests move to `governance_review` and enter the full Administrative Decision workflow. Only final approval changes the authoritative employee record.

Bulk corrections are also converted into individual governed decisions rather than directly updating multiple employee records.

## 8. Regulatory Change Management

Regulatory changes can be linked to:
- an uploaded Circular/Memo record;
- one or more versioned governance rules;
- an implementation owner;
- explicit implementation tasks.

Implementation tasks support:
- `[ ] pending task`
- `[x] completed task`

Lifecycle is controlled:

`Received → Assessing → Planned → Implementing → Testing → Implemented → Closed`

Controls include:
- positive applicability required before implementation planning;
- implementation owner required;
- sequential stage progression;
- all implementation tasks completed before Implemented;
- testing evidence required;
- implementation evidence required;
- implementation owner cannot independently verify their own change (except Super Admin emergency oversight);
- verified/approved identity and date retained.

## 9. Formal Handover Certificate

Handover now requires three distinct persons:
- outgoing officer;
- incoming officer;
- supervisor/verifier.

Only the named person may perform each acknowledgement step; Super Admin cannot impersonate a signatory.

Snapshot now includes:
- employee files;
- pending promotions;
- increments;
- retirement cases;
- data-quality issues;
- escalations;
- pending administrative decisions;
- pending corrections;
- active acting appointments;
- pending service letters;
- owned regulatory changes.

Registered private e-signature assets are attached to each acknowledgement when available. The final PDF displays the e-signatures, timestamps and evidence hash.

Verified handovers automatically enter records-retention tracking.

## 10. Records Retention / Legal Hold

Retention tracking is now automatically created for:
- approved Administrative Decisions;
- verified handover certificates;
- verified employee documents;
- approved service letters;
- implemented regulatory changes;
- issued vacancy-availability letters.

The system deliberately does NOT invent a retention duration. Auto-created cases show **Policy not assigned** until an authorised user assigns the approved retention period.

Lifecycle controls include:
- assign/update retention period;
- archive;
- legal/administrative hold;
- release hold;
- disposal eligibility date;
- independent disposal authorization;
- separate officer records actual disposal;
- mandatory disposition evidence/reference;
- retained disposition audit record.

## 11. Governance Compliance Dashboard

The dashboard now surfaces:
- decisions in review;
- missing authority;
- missing provenance;
- decisions without a versioned rule;
- SoD overrides;
- expired delegations;
- open regulatory changes;
- overdue regulatory changes;
- records due for disposal;
- retention cases without policy assignment;
- pending handovers;
- high-severity data-quality issues;
- overdue access reviews;
- backup records without restore-test evidence.

## Deployment

After copying/replacing the updated project:

```bash
php artisan migrate
```

No npm build is required for this phase.

## Validation performed

All modified PHP controllers, services, models, routes and migrations were checked with `php -l`.

The supplied source does not include `vendor/`, therefore a full Laravel boot/feature-test run could not be performed in this working copy.
