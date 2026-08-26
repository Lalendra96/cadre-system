# HIMS PARIKSHA — Carder Management System
## Installation Guide

> **Scope of this document:** step-by-step setup from a bare server to a running,
> production-ready instance. For feature documentation, the role/access matrix,
> and system settings reference, see `README.md` instead — this file is
> deliberately just the install path.

---

## Table of Contents

1. [System Requirements](#1-system-requirements)
2. [Get a Laravel Skeleton](#2-get-a-laravel-skeleton)
3. [Copy This Project Into It](#3-copy-this-project-into-it)
4. [Install Composer Dependencies](#4-install-composer-dependencies)
5. [Configure the Environment](#5-configure-the-environment)
6. [Register Middleware](#6-register-middleware)
7. [Run Migrations](#7-run-migrations)
8. [Seed Core Data](#8-seed-core-data)
9. [Schedule Background Commands](#9-schedule-background-commands)
10. [Configure PHP for File Uploads](#10-configure-php-for-file-uploads)
11. [Web Server Configuration](#11-web-server-configuration)
12. [First Login](#12-first-login)
13. [Production Security Checklist](#13-production-security-checklist)
14. [Verification Checklist](#14-verification-checklist)
15. [Troubleshooting](#15-troubleshooting)

---

## 1. System Requirements

| Requirement | Minimum | Notes |
|---|---|---|
| PHP | 8.2+ | This codebase uses `declare(strict_types=1)`, enums, and readonly-style patterns throughout |
| PostgreSQL | 14+ | The codebase is PostgreSQL-specific in places (`ILIKE`, `DISTINCT ON`) — **do not substitute MySQL/MariaDB** without rewriting those queries |
| Composer | 2.x | |
| Web server | Nginx or Apache | Document root must point to `public/` |
| Cron | any | Required for Laravel's scheduler to fire the 6 background commands (see §9) |

### Required PHP extensions

```
pdo_pgsql   — PostgreSQL driver
mbstring    — string handling
bcmath      — used by decimal-cast salary/increment fields
xml         — required by phpoffice/phpspreadsheet
gd          — required by barryvdh/laravel-dompdf for image rendering in PDFs
zip         — required by phpoffice/phpspreadsheet for .xlsx reading
fileinfo    — MIME-type detection on every file upload (letters, e-signatures, imports)
```

Check what you have:
```bash
php -m | grep -E "pdo_pgsql|mbstring|bcmath|xml|gd|zip|fileinfo"
```

---

## 2. Get a Laravel Skeleton

This project ships as `app/`, `database/`, `resources/`, and `routes/` only — it
is **not** a full Laravel installation (no `composer.json`, `vendor/`,
`bootstrap/`, `config/`, or `artisan`). Start from a fresh Laravel 10 skeleton:

```bash
composer create-project laravel/laravel:^10.0 hims-carder
cd hims-carder
```

---

## 3. Copy This Project Into It

Copy the four delivered directories **over** the fresh skeleton's matching
directories, merging rather than replacing wholesale (the skeleton's
`routes/console.php`, default `resources/views/welcome.blade.php`, etc. can
stay or go — only `routes/web.php` and everything under `resources/views/`
in this delivery matter):

```bash
cp -r /path/to/delivered/app/*      ./app/
cp -r /path/to/delivered/database/* ./database/
cp -r /path/to/delivered/resources/* ./resources/
cp    /path/to/delivered/routes/web.php ./routes/web.php
```

---

## 4. Install Composer Dependencies

Two packages beyond Laravel's defaults are required — both are referenced
directly in the codebase (`app/Services/PdfExportService.php` and
`app/Services/SpreadsheetReaderService.php`), and both fail with a clear,
caught error message rather than a fatal crash if missing:

```bash
composer require barryvdh/laravel-dompdf
composer require phpoffice/phpspreadsheet

php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
```

| Package | Used for |
|---|---|
| `barryvdh/laravel-dompdf` | PDF export for reports (snapshot, trend, YoY, retirement, unit breakdown) |
| `phpoffice/phpspreadsheet` | Reading `.xlsx`/`.xls` files in the Employee bulk importer. **Not required if you only ever import `.csv`** — CSV parsing uses PHP's built-in `SplFileObject`, zero dependencies. |

---

## 5. Configure the Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

```dotenv
APP_NAME="HIMS PARIKSHA"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-hospital-domain.lk

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=hims_carder
DB_USERNAME=hims_carder_app
DB_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true

FILESYSTEM_DISK=local
```

> **`APP_DEBUG=false` is not optional in production.** With it `true`, an
> unhandled exception dumps a full stack trace — including file paths and
> query values — straight to the browser. This is the single most common
> HIS data-leak vector in misconfigured deployments.

Create a **dedicated, least-privilege** database user rather than reusing a
superuser account:

```sql
CREATE DATABASE hims_carder;
CREATE USER hims_carder_app WITH PASSWORD '...';
GRANT ALL PRIVILEGES ON DATABASE hims_carder TO hims_carder_app;
```

`SESSION_DRIVER=database` is required — `ConcurrentSessionMiddleware` (which
signs a user out if they log in elsewhere) depends on sessions being queryable
from the database, not the filesystem or an array driver.

---

## 6. Register Middleware

This codebase uses two custom route middleware aliases (`role:` and
`feature:`) throughout `routes/web.php`. Register them in
`bootstrap/app.php` (Laravel 11) or `app/Http/Kernel.php` (Laravel 10):

**Laravel 10** — `app/Http/Kernel.php`:
```php
protected $middlewareAliases = [
    // ...Laravel's defaults stay...
    'role'    => \App\Http\Middleware\EnsureUserRole::class,
    'feature' => \App\Http\Middleware\EnsureFeatureAccess::class,
];
```

> ### ⚠ Action required — two security middleware are not yet wired anywhere
>
> `ConcurrentSessionMiddleware` and `IpAllowlistMiddleware` exist as classes
> (`app/Http/Middleware/`) and are **imported** at the top of `routes/web.php`
> — but checking every line of that file confirms **neither is actually
> applied to any route or route group** in the delivered code. The README's
> "Security 16" (concurrent session lock) and "Security 17" (IP allowlist)
> sections describe these as active features, and the corresponding toggle
> settings (`concurrent_session_lock_enabled`, `ip_allowlist_enabled`) exist
> under **Admin → Settings** — but flipping those toggles currently has
> **no effect** until you add both middleware to the request pipeline
> yourself. This is not optional if you intend to rely on either control.
>
> Add both to the `'web'` middleware group in `app/Http/Kernel.php` so they
> apply to every request, before Laravel routes to a controller:
>
> ```php
> protected $middlewareGroups = [
>     'web' => [
>         // ...Laravel's defaults (EncryptCookies, StartSession, etc.)...
>         \App\Http\Middleware\IpAllowlistMiddleware::class,
>         \App\Http\Middleware\ConcurrentSessionMiddleware::class,
>     ],
> ];
> ```
>
> Order matters: IP allowlist should reject a disallowed request as early as
> possible, before session/auth machinery does any work on it — place it
> before `ConcurrentSessionMiddleware`, which needs an authenticated session
> to have anything to check.

---

## 7. Run Migrations

63 migrations, timestamped `2025_06_25` through `2025_07_10`. They run in
filename order and are safe to re-run — every `Schema::table()` migration
guards its column additions with `Schema::hasColumn()` checks:

```bash
php artisan migrate
```

If this is a genuinely fresh database, this takes a few seconds. If you're
migrating an existing PARIKSHA installation forward, review any migration
failure output carefully before re-running — a handful of early migrations
intentionally drop a legacy `designations` table in favour of `positions`
(see `2025_06_27_000024_remove_designations.php`), which is destructive if
you have real data in that legacy table.

---

## 8. Seed Core Data

### 8a. Required for every environment

```bash
php artisan db:seed --class=UserCategorySeeder   # 7 Admin Group categories
php artisan db:seed --class=SuperAdminSeeder      # creates admin@hims.local
```

### 8b. Then choose ONE of the following — never both

**Dev / demo / training environment** — fictional test data:
```bash
php artisan db:seed --class=DummyDataSeeder
```
This seeder **hard-aborts** if it detects real production data already
present (57+ positions or 8+ subject codes), specifically to prevent
accidentally corrupting a real establishment register.

**Production** — real data sourced from official MoH Excel registers:
```bash
php artisan db:seed --class=ApprovedCarderSeeder      # 57 real positions + approved cadre
php artisan db:seed --class=SubjectCodeOfficerSeeder  # 30 real subject codes + officers
php artisan db:seed --class=MonthlyEntrySeeder        # real headcount snapshot
```

> All seeded user accounts (Super Admin and every demo account) have
> `force_password_change = true` — see §12.

---

## 9. Schedule Background Commands

Six console commands exist; **none of them run automatically** until you
schedule them. Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('increments:notify-upcoming')->dailyAt('07:00');
    $schedule->command('retirements:notify-upcoming')->dailyAt('07:15');
    $schedule->command('acting-officers:notify-ending')->dailyAt('07:30');
    $schedule->command('vacancy-letters:notify-expiring')->dailyAt('07:45');
    $schedule->command('service-letters:notify-stale')->dailyAt('08:00');
    $schedule->command('letters:purge-expired')->daily();
}
```

Then register Laravel's scheduler as a single cron entry on the server —
this is the **only** cron entry needed; Laravel dispatches everything above
from it:

```cron
* * * * * cd /path/to/hims-carder && php artisan schedule:run >> /dev/null 2>&1
```

| Command | What it does if never scheduled |
|---|---|
| `increments:notify-upcoming` | Subject Officers never get the 30-day-before increment reminder |
| `retirements:notify-upcoming` | No 90/60/30-day retirement warnings |
| `acting-officers:notify-ending` | Acting Subject Officer access can lapse with no warning to anyone |
| `vacancy-letters:notify-expiring` | No reminder before a vacancy declaration's 90-day window closes |
| `service-letters:notify-stale` | Letters can sit in the AO's approval queue indefinitely, unnoticed |
| `letters:purge-expired` | Old letter attachment files accumulate on disk indefinitely |

---

## 10. Configure PHP for File Uploads

Three upload paths exist in this system with specific size ceilings enforced
in validation. Your `php.ini` must accommodate the largest of them:

| Upload | App-enforced max | `php.ini` directive needed |
|---|---|---|
| Letter attachments | 10 MB per file | `upload_max_filesize`, `post_max_size` ≥ 10M |
| Employee bulk import (CSV/XLSX) | 5 MB | ≥ 5M (covered by the above) |
| E-signature image | 512 KB | ≥ 1M (covered by the above) |

```ini
upload_max_filesize = 12M
post_max_size = 12M
memory_limit = 256M
max_execution_time = 60
```

The `memory_limit` and `max_execution_time` increases matter specifically
for `phpoffice/phpspreadsheet` — parsing a large `.xlsx` file loads it
substantially into memory, and the default 128M/30s can be too tight for a
hospital-wide employee register with hundreds of rows and formatting.

---

## 11. Web Server Configuration

Document root is `public/`, standard Laravel. Nginx example:

```nginx
server {
    listen 443 ssl http2;
    server_name your-hospital-domain.lk;
    root /path/to/hims-carder/public;

    ssl_certificate     /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Never serve these directly
    location ~ /\.(?!well-known).* { deny all; }
    location ~ ^/(storage|bootstrap/cache) { deny all; }

    client_max_body_size 12M;  # must match php.ini upload_max_filesize
}
```

> **HTTPS is not optional.** This system handles NIC numbers, salary data,
> e-signature images, and session cookies. Serving it over plain HTTP on a
> hospital LAN is still a real exposure risk to anyone on the same network
> segment — use TLS even for an internal-only deployment, and set
> `SESSION_SECURE_COOKIE=true` (already in the `.env` block in §5) so the
> session cookie is rejected outright over a non-HTTPS connection.

Set ownership/permissions so the web server user can write to the two
directories Laravel needs write access to, and nowhere else:

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

---

## 12. First Login

```
URL:      https://your-hospital-domain.lk/login
Email:    admin@hims.local
Password: ChangeMe@123
```

You'll be forced to set a new password immediately
(`force_password_change = true` on every seeded account) — minimum 8
characters, mixed case, at least one number.

**Do this first, before anything else:**
1. Change the Super Admin password
2. Go to **Admin → Users** and create real named accounts for your actual
   Planning Officer, Director, and initial Subject Officers
3. Disable or delete the demo accounts from `DummyDataSeeder` if you seeded
   it accidentally in what turns out to be a production environment

---

## 13. Production Security Checklist

| ✓ | Item | Where |
|---|---|---|
| ☐ | `APP_DEBUG=false` | `.env` |
| ☐ | `SESSION_SECURE_COOKIE=true` and serving over HTTPS | `.env` + web server |
| ☐ | Database user is least-privilege, not a superuser | PostgreSQL |
| ☐ | IP allowlist enabled if the hospital network has a known static range | **Admin → Settings → Security** |
| ☐ | `storage/` and `bootstrap/cache/` are the *only* web-server-writable paths | filesystem permissions |
| ☐ | Cron is running (`schedule:run` every minute) | verify with `php artisan schedule:list` |
| ☐ | Database backups are configured (this project does not include backup automation) | your infrastructure |
| ☐ | `.env` is not web-readable and not committed to version control | filesystem / `.gitignore` |
| ☐ | Demo/seeded accounts' passwords changed or accounts disabled | **Admin → Users** |

---

## 14. Verification Checklist

Run through this after setup to confirm the install is functionally sound:

```bash
# 1. Routes are all registered (no missing controller errors)
php artisan route:list | grep -c "employees\|service-letters\|vacancy-availability"

# 2. Scheduled commands are registered
php artisan schedule:list

# 3. Migrations are all applied
php artisan migrate:status | grep -c "Ran"
# Should show 63

# 4. Storage is writable
php artisan tinker --execute="Storage::disk('local')->put('test.txt', 'ok'); echo Storage::disk('local')->get('test.txt');"
```

Then in the browser: log in, confirm the dashboard loads without a 500,
confirm the notification bell polls without a console error (F12 → Network
tab → look for `/notifications/unread-count` returning 200), and try one
PDF export to confirm DomPDF is correctly installed.

---

## 15. Troubleshooting

| Symptom | Likely cause |
|---|---|
| IP allowlist / concurrent session settings toggle on but seem to do nothing | §6 not completed — these two middleware must be manually added to the `'web'` group, they aren't wired by anything in the delivered `routes/web.php` |
| `Class "Barryvdh\DomPDF\Facade\Pdf" not found` | §4 not completed — run `composer require barryvdh/laravel-dompdf` |
| `Excel (.xlsx) import requires the phpoffice/phpspreadsheet package` | Expected, caught error — either install it or use `.csv` |
| 500 error on any `role:` or `feature:`-protected route | Middleware aliases not registered — see §6 |
| Notification bell always shows 0 / never updates | Check `/notifications/unread-count` in the browser Network tab; if it 404s, `routes/web.php` wasn't copied over the skeleton's default routes file |
| Increment/retirement reminders never arrive | Scheduler not configured — see §9; confirm with `php artisan schedule:list` and check your crontab |
| `SQLSTATE[42883]: undefined function ILIKE` | You're running MySQL/MariaDB instead of PostgreSQL — this codebase requires PostgreSQL |
| File upload silently fails past a certain size | `php.ini` `upload_max_filesize`/`post_max_size` too low — see §10 |
| Session logs out unexpectedly on every request | `SESSION_DRIVER` isn't `database`, or the `sessions` table migration didn't run |

---

*HIMS PARIKSHA — Carder Management System · Teaching Hospital Peradeniya · Loons Lab (Pvt) Ltd*
