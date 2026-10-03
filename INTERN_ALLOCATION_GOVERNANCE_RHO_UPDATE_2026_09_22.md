# Intern Medical Officer Allocation — Governance, Timeline & RHO Update

## Integrated workflow

1. Create an Intern Medical Officer batch with a recorded start date and end date.
2. Formally assign one Subject Officer as the operational owner of the batch.
3. Configure rotation capacities.
4. Import/maintain the batch's intern list.
5. Allocate first and second appointments using the existing assignment workflow.
6. Monitor recorded batch periods on the Intern Allocation timeline.
7. Use the Ending Intern List to capture RHO placements without re-entering intern names.
8. Close the batch while retaining historical records and audit evidence.

## Access model

- **Assigned Subject Officer:** operational write access for the assigned batch while it is open.
- **Other Subject Officers:** read-only where they have module access.
- **Admin Group:** read-only oversight.
- **Planning Officer:** read-only oversight.
- **Super Admin:** read-only operational oversight plus formal responsibility assignment/reassignment and batch creation for assignment to a Subject Officer.

Reassignment requires a reason and is written to the audit log. A previous owner immediately becomes read-only.

## Governance safeguards

- Only recorded/authoritative dates are shown on the timeline; no predictive allocation decisions are generated.
- RHO placement capture is generated from the batch intern list and does not create a second identity list.
- The system does not recommend an RHO institution, unit or placement.
- A placement marked **Placed** requires institution, effective date and official reference number.
- Intern records are disabled rather than hard-deleted; historical assignments remain available for audit purposes.
- Operational exports continue through the project's export-audit mechanism.
- Public intern self-selection is available only while the batch is active and within its recorded start/end period.
- RHO and allocation writes are restricted to the formally assigned Subject Officer.

## Database changes

Laravel migration:

`database/migrations/2026_09_22_120000_govern_intern_batches_and_add_rho_placements.php`

Manual PostgreSQL alternative:

`database/manual/2026-09-22-intern-allocation-governance-rho.sql`


## Existing batches after migration

Legacy batches are deliberately not auto-assigned to a user because guessing operational ownership would create an incorrect governance record. Super Admin should open **Intern Medical Officer Allocation** and use **Reassign** on each legacy batch to establish the responsible Subject Officer. The assigned officer can then record or correct the official batch start/end dates with a mandatory reason.
