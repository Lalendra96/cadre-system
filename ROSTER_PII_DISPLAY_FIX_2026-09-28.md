# Roster encrypted employee-name display fix — 2026-09-28

## Issue
Employee names are protected PII and are encrypted at rest by `EncryptsPersonnelData` on the `Employee` model. The Roster dashboard and roster-plan creation screen were reading `employees.name` with `DB::table()`/raw joins. Query Builder results bypass Eloquent model accessors, so ciphertext was rendered in the user interface.

## Fix
- `RosterController@index` now loads upcoming roster assignments through `RosterAssignment` with the `employee` relationship.
- Roster dashboard renders `employee->display_name`, which uses the existing Employee model decryption path.
- `RosterPlanController@create` now loads active employees through `Employee`, converts them to a minimal decrypted dropdown payload, and sorts by decrypted display name in application memory.
- `RosterPlanController@store` now resolves employees with `Employee::findOrFail()` instead of a raw employee query.
- Roster plan detail renders `employee->display_name`.
- No decryption key, encryption logic, or manual `decrypt()` call was added to a Blade view or JavaScript payload.

## Security rationale
Decryption remains centralized in `PiiCryptographyService` / `EncryptsPersonnelData`. Roster screens only receive the minimum decrypted employee fields required for legitimate roster assignment operations.
