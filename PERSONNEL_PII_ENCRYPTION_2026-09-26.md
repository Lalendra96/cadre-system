# Personnel PII Encryption & Exact-Search Protection

## What this update does

This release adds application-layer protection for Employee and Intern personally identifiable information while preserving authorized Laravel UI display.

### Cryptography
- Reversible PII: Laravel `Encrypter` with a **dedicated 256-bit PII key** and **AES-256-CBC + MAC authentication**.
- Exact-match search: **HMAC-SHA256 blind indexes** with a completely separate 256-bit search key.
- The general `APP_KEY` is not reused for PII.
- Ciphertext is prefixed `pii:v1:` so migration and future key-version work can distinguish protected values from legacy plaintext.

### Employee fields encrypted
`name`, `pay_no`, `service_file_no`, `nic_number`, `wop_number`, `professional_registration_no`, `email`, `whatsapp_mobile`, `permanent_address`, `current_address`, `emergency_contact_name`, `emergency_contact_relationship`, `emergency_contact_mobile`, `confirmation_reference_no`, `notes`.

Exact HMAC indexes are maintained for: `name`, `pay_no`, `service_file_no`, `nic_number`, `wop_number`, `professional_registration_no`, `email`, `whatsapp_mobile`.

### Intern fields encrypted
`name`, `nic_number`, `mobile_number`.

All three receive exact-match HMAC indexes.

### Additional duplicated personnel PII protected
- `transfer_records.employee_name`
- `car_pass_requests.employee_name_snapshot`
- `car_pass_requests.pay_no_snapshot`
- `employee_change_requests.old_value`
- `employee_change_requests.requested_value`

### Audit protection
New audit events redact protected personnel values rather than copying plaintext or ciphertext into `audit_logs.old_values` / `new_values`.
A separate dry-run/commit command is included to redact historic audit JSON values.

## Exact search policy

Employee search no longer uses wildcard/prefix matching against identity data. The user must provide a complete value. Laravel normalizes the supplied value, calculates HMAC-SHA256, and PostgreSQL performs an indexed equality lookup.

There is no `%term%`, `LIKE`, or `ILIKE` search for protected employee identity fields.

## Deployment sequence — safest path

**Do not skip the backup. Do not invent or hard-code keys into source files.**

1. Take and verify a PostgreSQL backup before changing the database.
2. Deploy this source with `PII_ENCRYPTION_ENABLED=false` initially.
3. Run the schema migration:
   `php artisan migrate`
4. Generate two independent random keys:
   `php artisan pii:generate-keys`
5. Put the printed values in the server `.env` only:
   - `PII_ENCRYPTION_KEY=base64:...`
   - `PII_SEARCH_KEY=base64:...`
   - `PII_ENCRYPTION_ENABLED=true`
   - `PII_CIPHER=AES-256-CBC`
6. Clear cached configuration:
   `php artisan config:clear`
7. Run the conversion in **dry-run mode first**:
   `php artisan pii:encrypt-existing`
8. During a maintenance window, run:
   `php artisan down`
   `php artisan pii:encrypt-existing --commit --chunk=100`
9. Verify every protected value and HMAC index:
   `php artisan pii:verify`
   Do not proceed if this returns a failure.
10. Preview historical audit redaction:
   `php artisan pii:scrub-audit-history`
11. Commit historical audit redaction:
   `php artisan pii:scrub-audit-history --commit`
12. Run `php artisan pii:verify` again.
13. Return the application:
   `php artisan up`
14. Take a new encrypted/secured PostgreSQL backup after successful migration.

The conversion command is idempotent. Rows already using the `pii:v1:` envelope are decrypted and re-verified rather than double-encrypted. Each chunk runs inside a database transaction; if decrypt-after-write verification fails, that chunk rolls back.

## Key handling requirements

- Never commit PII keys to Git.
- Never store the PII encryption key or HMAC search key in PostgreSQL.
- Store server `.env` outside web-accessible locations with OS permissions restricted to the application account.
- Back up the two keys separately in the organization's approved secret/password-management process.
- Loss of `PII_ENCRYPTION_KEY` means encrypted values cannot be recovered.
- Loss of `PII_SEARCH_KEY` means existing exact-search indexes cannot be regenerated unless the encrypted values can first be decrypted.
- Never use the same key for encryption and HMAC search.

## Performance design

HMAC-SHA256 computation for a single search term is negligible. The HMAC fields are indexed using PostgreSQL B-tree indexes, so exact employee lookup remains an indexed equality query rather than decrypting/scanning the whole table.

List/dropdown display is decrypted only after authorized Eloquent rows are returned to Laravel. PostgreSQL does not receive the decryption key. Raw governance queries that previously selected employee name/pay directly were changed to load those display values through the Employee model.

Encrypted names cannot be meaningfully sorted in SQL, so affected personnel lists sort decrypted names in PHP after retrieval where needed. Large primary employee pages remain paginated and do not decrypt the entire employee database for a search.

## Important scope decision

Operational dates (for example date of birth, appointment/joining dates, increment dates) remain in their existing typed date columns because the current system performs retirement, age, workforce-planning and scheduler queries directly on them. Encrypting those columns without redesigning the analytical model would break or severely slow those workflows. Direct identifiers/contact data are encrypted by this release; date-field minimization and derived-date redesign should be treated as a separate schema project if policy requires cryptographic protection of dates as well.

Generated official documents can intentionally contain employee data (for example approved service-letter bodies). This release protects canonical Employee/Intern PII and known duplicate identity snapshots/correction values; official document-content encryption should be governed with document retention, immutable hashing, signing and recovery requirements rather than silently changing signed-document storage semantics.

## Files added / materially changed

- `config/pii.php`
- `app/Services/PiiCryptographyService.php`
- `app/Services/PersonnelDisplayService.php`
- `app/Traits/EncryptsPersonnelData.php`
- `app/Rules/PiiUnique.php`
- `app/Console/Commands/GeneratePiiKeys.php`
- `app/Console/Commands/EncryptExistingPersonnelData.php`
- `app/Console/Commands/VerifyPersonnelEncryption.php`
- `app/Console/Commands/ScrubHistoricalPiiFromAuditLogs.php`
- `database/migrations/2026_09_26_173000_add_personnel_pii_protection.php`
- Employee / Intern / transfer / car-pass / correction models
- Employee exact-search and uniqueness validation paths
- Data-quality duplicate detection using HMAC indexes
- Governance raw-query PII hydration
- Audit logging redaction

## 2026-09-26 follow-up hardening: imports and secondary workflows

The protection layer also covers write paths that can otherwise bypass model encryption:

- Admin CSV/XLSX Employee Import writes through `Employee::create()`, so encrypted fields and HMAC blind indexes are generated automatically.
- Intern XLSX/CSV/DOCX import now checks duplicate names using the exact HMAC blind index before creating the encrypted Intern record. It no longer uses plaintext `firstOrCreate(name=...)` against ciphertext.
- Transfer Record `employee_name` snapshots are encrypted by the `TransferRecord` model.
- Car Pass employee-name/pay-number snapshots are encrypted by `CarPassRequest`.
- Car Pass employee lookup for NIC, Pay No and Phone now uses exact HMAC blind-index lookup. No SQL operation is performed against encrypted phone ciphertext.
- Advanced HRMIS reconciliation now matches Pay No using HMAC and updates an Employee through Eloquent, not a direct `DB::table('employees')->update()` bypass.
- HRMIS reconciliation stored values (`external_identifier`, `local_value`, `external_value`) and field-provenance `field_value` are encrypted as secondary PII stores.
- Existing records in those secondary governance tables are included in `pii:encrypt-existing` and `pii:verify`.

Important: direct SQL writes to protected Employee/Intern PII columns must not be added later. New application code must update those records through the Eloquent models or an approved PII service so encryption and blind-index maintenance cannot be bypassed.
