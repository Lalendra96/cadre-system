# Client Version & Feature Manager Seeder

## Added

A dedicated idempotent seeder has been added:

`database/seeders/ClientFeatureManagerSeeder.php`

It is also included in `DatabaseSeeder` so normal application seeding creates the account.

## Default development account

- **Name:** Client Feature Manager
- **Email:** `feature.manager@hims.local`
- **Role:** `client_feature_manager`
- **Initial password (non-production fallback only):** `ChangeMe@123`
- **Force password change:** Yes

The account is intentionally created without employee-record, letter, circular-group or intern-assignment privileges.

## Production deployment

For governance/security reasons, the seeder refuses to use the known development fallback password when `APP_ENV=production`.

Set a strong temporary password before running the seeder:

```env
FEATURE_MANAGER_SEED_PASSWORD=Use-A-Strong-Temporary-Password
```

Optional overrides:

```env
FEATURE_MANAGER_SEED_NAME="Client Feature Manager"
FEATURE_MANAGER_SEED_EMAIL="feature.manager@your-domain.local"
```

Run only this seeder:

```bash
php artisan db:seed --class=ClientFeatureManagerSeeder
```

Or run the normal database seeding flow:

```bash
php artisan db:seed
```

## Governance behavior

The seeder:

- grants only `client_feature_manager`;
- does not grant Super Admin or operational HR roles;
- disables direct employee/letter/circular/intern-management flags;
- forces an initial password change for newly created accounts;
- never resets the password of an existing account when re-run;
- warns if the same email already has additional roles so an administrator can review the least-privilege boundary.

The role remains limited to client version/edition metadata and client-permitted feature controls. Payroll remains outside Client Feature Manager control and remains disabled by default under the Government-authoritative payroll policy.
