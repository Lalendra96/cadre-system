# Governance / Legal-Safeguard Design Update — 2026-09-25

## Objective
Make governance safeguards self-explanatory at the point of administrative action, while preserving human authority, auditability, role separation, evidentiary history and minimum-necessary information use.

This implementation is a software-control design. It does not declare that an action is legally valid merely because the application permits or records it; the authorised institution remains responsible for the applicable law, circular, service minute, delegation and financial/administrative authority.

## Implemented safeguards

### 1. Reusable in-context safeguard guidance
Added `resources/views/partials/governance-legal-safeguard.blade.php` and applied it to:
- Administrative Officer dashboard
- Administrative Intelligence
- Governance Control dashboard
- Administrative Decision Register
- Utility Bill monitoring
- Utility account creation
- Utility bill creation
- Utility payment recording
- Utility responsibility assignment
- Safeguard Attestation Register

Each notice explains what the screen is for, what the displayed intelligence does **not** authorise, and what the officer must verify before acting.

### 2. Consequential-action attestations
Added `governance_action_attestations`.

For selected high-impact actions the system records:
- acting user;
- action type;
- affected resource;
- stated official administrative purpose;
- authority/reference where applicable;
- source/evidence review confirmation;
- material accuracy confirmation;
- minimum-necessary-use confirmation;
- role / segregation-of-duties confirmation;
- timestamp, IP address and user agent.

Attestation strengthens accountability but does not replace the underlying authority or audit record.

### 3. Administrative Decision safeguards
Before Check / Recommend / Approve, the officer must confirm:
- official purpose;
- source evidence reviewed;
- material facts checked;
- minimum information used;
- segregation-of-duties safeguard acknowledged.

Final approval additionally requires explicit confirmation that authority/reference and the applicable rule/version were checked.

Existing SoD override controls remain in force. Overrides still require reason + authority/reference.

### 4. Utility Bill financial-control safeguards
Utility account creation:
- source/master-data verification;
- accuracy confirmation;
- official purpose retained.

Bill creation:
- source bill verification;
- date/value accuracy confirmation;
- official purpose retained.

Payment recording:
- authority/file reference required;
- payment/voucher evidence review required;
- amount/date/bill/reference accuracy confirmation required;
- duplicate-payment check required;
- attestation retained with the payment event.

Utility responsibility assignment:
- authority/appointment reference is mandatory;
- explicit authority and continuity confirmations;
- previous assignment history remains preserved.

### 5. Safeguard Attestation Register
Added a read-only Governance Control workspace showing recent attestations with filters by action/date.

The screen explicitly warns that:
- an attestation is not proof that an action was lawful/correct;
- the underlying authority, source record, workflow and audit log must also be reviewed;
- absence on older records is not proof of wrongdoing because the control may have been introduced later.

### 6. Decision-support interpretation safeguards
Administrative dashboards and intelligence now explicitly distinguish:
- aggregate planning signals;
- source records;
- authority;
- consequential human decisions.

The interface states that intelligence must not be the sole basis for appointment, transfer, promotion, discipline, retirement processing, payment approval or similar consequential action.

### 7. Export audit improvement
Utility Bill CSV export now writes to the existing export audit log before delivery.

Also corrected the Utility Bill export link to use the registered `utility-bills.export.csv` route name.

## Migration
Run:

```bash
php artisan migrate
php artisan optimize:clear
```

No new npm dependency is required.
