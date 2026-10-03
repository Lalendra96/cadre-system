# Employee Profile Handling Count — Subject Officer Total Update

## Change
The official Handling Count is now maintained once per Subject Officer instead of separately for each Position.

## Counting
- Handling Count: manually governed total for the Subject Officer.
- Current Profile Count: active employee profiles in that officer's effective HR responsibility.
- Remaining: max(Handling Count - Current Profile Count, 0).
- Completion %: Current Profile Count / Handling Count × 100, capped at 100%.

## Position breakdown
Positions are retained only as a read-only breakdown of the current profile count. No position-wise target is required.

## Governance
Previous position-scoped target records remain in the database as historical records but are ignored by the new officer-total calculation. New active officer-total target records use NULL subject_code_id and NULL position_id.
