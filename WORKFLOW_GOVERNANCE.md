# Workforce workflow governance (v9)

This overlay adds the remaining operational workflows without deleting records.

- `documents:notify-expiring` uses the HR Position responsibility map, only sends reminders for verified documents, and deduplicates each recipient/document/expiry/reminder band. Uploading a replacement stores `supersedes_id`; the prior version remains available for audit.
- Document decisions are `pending`, `verified`, or `rejected`; confidential downloads continue to use the existing management-only policy.
- Promotions are prepared, independently checked, and approved. A preparer cannot approve their own promotion, and grade history remains append-only.
- Reconciliation issues store an evidence snapshot, require an explanation and resolution note, and require a different manager to verify the resolution.
- Recruitment vacancies move forward through identified, requested, shortlisting, offered, and filled (or cancelled); retirement projects may be linked.
- Duplicate-resolution cases record which employee remains authoritative and never merge or delete either record.
- Escalations are idempotent by source/rule/status and retain acknowledgement and resolution history.

Run the new migration after `000094`, register Laravel's scheduler, and run:

```text
php artisan migrate
php artisan documents:notify-expiring
php artisan test tests/workflow-policy.php
```

The included policy checks are deterministic CI checks. Full PostgreSQL migrations and browser rendering still belong in the deployment environment.
