# Planning Officer Governance & Hospital Decision-Support Update — 2026-09-25

## Purpose
The Planning Officer interface is explicitly a **support system only**. It may structure evidence, calculate indicators, compare scenarios, document assumptions and prepare/referral planning recommendations. It does not itself create institutional authority for staffing, procurement, expenditure, service reconfiguration, cadre amendment, transfers, appointments or other consequential action.

## New Hospital Planning Decision Support workspace
A governed assessment register has been added for:
- Workforce & cadre planning
- Service capacity & demand
- Infrastructure & space
- Equipment & technology
- Financial / resource planning
- Utility & operational continuity
- Quality, safety & resilience
- Other hospital planning questions

Each assessment records:
- planning question and objective;
- data-as-at / evidence source context;
- source evidence and assumptions/limitations;
- reasonable options considered;
- advisory planning recommendation;
- workforce, financial, service-delivery, equity/access and implementation impacts;
- risks and mitigations;
- policy/authority reference;
- explicit decision-support-only acknowledgement;
- evidence, alternative-option, risk and minimum-necessary-data confirmations.

## Governed review/referral
The workflow is `Draft -> Ready for review -> Referred for authorised decision`.

`Referred` does **not** mean approved. Referral retains an advisory planning record and records a governance attestation with purpose, authority/evidence reference, evidence review, accuracy, minimum-necessary data use and role/no-conflict confirmation.

## Planning Intelligence redesign
The Planning Intelligence page now:
- clearly labels scenario inputs as assumptions;
- explains each output and its limitations;
- distinguishes official establishment from calculated gaps and illustrative projections;
- exposes readiness checks for data quality, reconciliation, HR escalations, retirement work, recruitment vacancies and official reports;
- provides a six-step evidence-to-authorised-decision workflow;
- links scenario analysis directly to the governed planning worksheet.

## Dashboard changes
The Planning Officer Dashboard includes a Hospital Planning Decision Workspace with counts for Draft, Ready for Review and Referred recommendations and direct links to scenario analysis and the planning register.

## Planning Summary changes
The Planning Summary now carries a governance/legal safeguard notice. For Planning Officer users the prominent action is changed from adding official approved-cadre figures to viewing the approved-cadre source and starting a planning assessment. Row-level actions are presented as source-reference actions rather than direct approved-cadre editing from this planning view.

## Code formatting
Dense single-line code introduced in earlier updates was reformatted for maintainability, including the Utility Bill Blade views and chart JavaScript, Utility Bill controller query/CSV code, and selected legacy PHP arrays/chains encountered during this update. Long SVG path data in the untouched Laravel welcome page is intentionally left as generated vector data rather than split into invalid markup.

## Deployment
Run:

```bash
php artisan migrate
php artisan optimize:clear
```

No new npm package is required.
