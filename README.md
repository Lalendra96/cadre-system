# HIMS PARIKSHA — Carder Management System

> **Hospital Information Management System — Workforce Cadre Module**  
> Teaching Hospital Peradeniya · Loons Lab (Pvt) Ltd

---

## Table of Contents

1. [Overview](#overview)
2. [Technology Stack](#technology-stack)
3. [Role System](#role-system)
4. [Feature Reference & Access Matrix](#feature-reference--access-matrix)
   - [Authentication & Security](#authentication--security)
   - [Dashboard](#dashboard)
   - [Approved Carder](#approved-carder)
   - [Monthly Carder Entries](#monthly-carder-entries)
   - [Submission Deadline Enforcement](#submission-deadline-enforcement)
   - [Entry Verification Layer](#entry-verification-layer)
   - [Entry Amendment Workflow](#entry-amendment-workflow)
   - [Transfer Records](#transfer-records)
   - [Employee Profiles](#employee-profiles)
   - [Acting Appointments](#acting-appointments)
   - [Annual Cadre Review](#annual-cadre-review)
   - [Unit Types & Units](#unit-types--units)
   - [Unit Post Allocations](#unit-post-allocations)
   - [Letter Sharing](#letter-sharing)
   - [Reports](#reports)
   - [System Administration](#system-administration)
   - [Security Controls](#security-controls)
5. [System Settings Reference](#system-settings-reference)
6. [Admin Group Category Reference](#admin-group-category-reference)
7. [Deployment Checklist](#deployment-checklist)
8. [Default Credentials](#default-credentials)

---

## Overview

The Carder Management System is a standalone Laravel 10 module within HIMS PARIKSHA that manages the hospital's approved workforce (cadre). It tracks Ministry of Health–approved headcounts, actual in-position figures per month per subject code, unit-level post allocation, staffing vacancy gaps, and internal letter circulation between Senior Management.

**Project stats:** 44 migrations · 22 models · 28 controllers · 59 blade views · 5 middleware classes

---

## Technology Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.2+, Laravel 10 |
| Database | PostgreSQL 14+ |
| Frontend | Vanilla JavaScript (no framework), Blade templates |
| UI System | Material Design 3 — Dark theme |
| Auth | Session-based, OTP force-change, concurrent session lock |
| Security | IP allowlist (CIDR), export audit log, role middleware, feature gates |

---

## Role System

The system uses a **multi-role pivot table** (`user_roles`). One account can hold more than one role simultaneously. Role checks use `hasRole()`, `hasAnyRole()`, and `isSuperAdmin()` — never a single `role` column.

### Roles

| Role Key | Label | Purpose |
|---|---|---|
| `super_admin` | Super Admin | Full system access. Manages users, settings, security. |
| `planning_officer` | Planning Officer | Manages approved carder, reviews entries, generates reports. |
| `admin_group` | Admin Group | Senior management. Reviews letters, verifies entries (if category matches). Sub-divided by **User Category** (see below). |
| `subject_officer` | Subject Officer | Data entry. Submits monthly entries for their assigned subject codes only. |

### Precedence when roles overlap

When a user holds multiple roles, **elevated roles always take priority**:

```
Super Admin  >  Admin Group  >  Planning Officer  >  Subject Officer
```

The `EnsureFeatureAccess` middleware bypasses Subject Officer restrictions entirely for any user who also holds an elevated role. This prevents a multi-role account (e.g. Planning Officer who is also a Subject Officer) from being incorrectly blocked by position-bound guards.

---

## Feature Reference & Access Matrix

> **Legend**  
> ✅ Full access &nbsp; 👁 Read-only &nbsp; ⚙ Configurable &nbsp; ❌ No access &nbsp; 🔑 Category-gated

### Authentication & Security

| Feature | Super Admin | Planning Officer | Admin Group | Subject Officer |
|---|:---:|:---:|:---:|:---:|
| Login / Logout | ✅ | ✅ | ✅ | ✅ |
| Force password change on first login | ✅ | ✅ | ✅ | ✅ |
| Forgot password (request via system) | ✅ | ✅ | ✅ | ✅ |
| See forgot-password badge on dashboard | ✅ | ❌ | ❌ | ❌ |
| Concurrent session protection | ✅ | ✅ | ✅ | ✅ |
| IP allowlist enforcement | ✅ | ✅ | ✅ | ✅ |

All users are subject to the IP allowlist when enabled, including Super Admin. The only bypass is local loopback (`127.0.0.1`/`::1`).

---

### Dashboard

| Feature | Super Admin | Planning Officer | Admin Group | Subject Officer |
|---|:---:|:---:|:---:|:---:|
| View dashboard | ✅ | ✅ | ✅ | ✅ |
| Forgot-password request badge | ✅ | ❌ | ❌ | ❌ |
| Vacancy alert banner | ✅ | ✅ | ✅ | ❌ |
| Pending verification count | ✅ | ✅ | 🔑 | ❌ |

> 🔑 Admin Group sees the verification count only if their User Category is designated as a verifier in system settings.

---

### Approved Carder

Records the **Ministry of Health–approved** total number of posts per position per year. This is the reference figure against which actual in-position numbers are compared.

> **Director-only writes.** The Director (Admin Group, Director category) and Super Admin are the only users who may create, edit, or disable approved carder records. Planning Officers can view the figures but cannot modify them — the approved carder represents a formal MoH authorisation, not an operational estimate.

| Feature | Super Admin | Planning Officer | Director | Deputy Dir General / Deputy Dir / MO Planning | Other Admin Group | Subject Officer |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| View approved carder list | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| Create / edit approved amounts | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| Disable record | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| Re-enable record | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| Print register | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |

**Hard deletion is removed.** Disabling a record:
- Removes it from all reports and vacancy calculations for that year
- Preserves the complete audit trail (who created it, who disabled it, why)
- Requires a written reason of at least 10 characters
- Can be reversed by the Director at any time
- Is logged with user, timestamp, and reason in the audit log

---

### Monthly Carder Entries

Subject officers submit actual in-position figures monthly per subject code and position. Data includes males, females, transferred in/out, no-pay leave.

| Feature | Super Admin | Planning Officer | Admin Group | Subject Officer |
|---|:---:|:---:|:---:|:---:|
| View own entries | ✅ | ✅ | ❌ | ✅ ¹ |
| Create new entry | ✅ | ✅ | ❌ | ✅ ¹ |
| Edit entry | ✅ | ✅ | ❌ | ✅ ¹ |
| Delete entry | ✅ | ✅ | ❌ | ✅ ¹ |
| Copy from previous month (AJAX) | ✅ | ✅ | ❌ | ✅ ¹ |
| Check deadline status (AJAX) | ✅ | ✅ | ❌ | ✅ ¹ |

> ¹ Subject Officers can only access entries for subject codes **assigned to their account** and linked to a position. Officers with no position-bound subject code cannot access this section.

**Validation rules enforced:**
- One entry per `(subject_code, position, year, month)` — duplicate submissions rejected
- Deadline enforcement: entries locked after the configured cutoff date
- Sanity check: total in-post > 120% of approved carder triggers a data-quality warning

---

### Submission Deadline Enforcement

Configurable monthly deadline after which entries for a given period are locked. Managed by Super Admin in **Admin → Settings**.

| Setting | Default | Description |
|---|---|---|
| `deadline_enforcement_enabled` | `true` | Toggle deadline locking on/off |
| `deadline_day` | `15` | Day of the **following** month by which entries are due (1–28) |
| `deadline_grace_days` | `0` | Extra days of grace after the deadline day |

**Example:** With `deadline_day = 15`, entries for **March** are due by **15 April**. After that, the entry is locked and officers must request an amendment.

| Feature | Super Admin | Planning Officer | Admin Group | Subject Officer |
|---|:---:|:---:|:---:|:---:|
| Configure deadline settings | ✅ | ❌ | ❌ | ❌ |
| View deadline status on entry form | ✅ | ✅ | ❌ | ✅ |
| Submit after deadline | ❌ ² | ❌ ² | ❌ | ❌ |

> ² Deadline enforcement applies to all roles. To accept a late entry, the Super Admin can temporarily disable `deadline_enforcement_enabled`.

---

### Entry Verification Layer

Optional workflow requiring a designated verifier to approve submissions before they appear in reports. Disabled by default.

| Feature | Super Admin | Planning Officer | Admin Group | Subject Officer |
|---|:---:|:---:|:---:|:---:|
| Configure verification settings | ✅ | ❌ | ❌ | ❌ |
| View pending-verification queue | ✅ | ✅ ³ | 🔑 | ❌ |
| Verify (approve) an entry | ✅ | ✅ ³ | 🔑 | ❌ |
| Reject an entry (send back) | ✅ | ✅ ³ | 🔑 | ❌ |

> ³ Depends on `entry_verifier_role` setting.  
> 🔑 Admin Group users can verify only if their User Category is in the `entry_verifier_category_ids` list.

**Verifier role options (Admin → Settings):**

| Option | Who can verify |
|---|---|
| Planning Officer | Any user with the `planning_officer` role |
| Super Admin only | Super Admin account exclusively |
| Specific Categories | Admin Group users whose category is ticked (e.g. Chief Clerk, Medical Officer Planning) — multiple categories may be selected simultaneously |

Super Admin **always** bypasses the verifier check regardless of configuration.

---

### Entry Amendment Workflow

When an entry is locked (after verification or past deadline), the submitting officer can request an amendment. A supervisor then approves (unlocks for resubmission) or rejects.

| Feature | Super Admin | Planning Officer | Admin Group | Subject Officer |
|---|:---:|:---:|:---:|:---:|
| Request amendment on own entry | ✅ | ✅ | ❌ | ✅ |
| View amendment request queue | ✅ | ✅ | 🔑 | ❌ |
| Approve amendment (unlock entry) | ✅ | ✅ | 🔑 | ❌ |
| Reject amendment request | ✅ | ✅ | 🔑 | ❌ |

> 🔑 Same category-gate as Verification Layer applies.

**Amendment status flow:**

```
submitted → amendment_requested → [approved] → submitted (re-editable)
                                → [rejected] → submitted (unchanged)
```

---

### Transfer Records

Individual transfer events linked to a monthly entry (instead of just aggregate `transferred_in`/`transferred_out` counts). Tracks who transferred, direction, type, from/to location, and effective date.

| Feature | Super Admin | Planning Officer | Admin Group | Subject Officer |
|---|:---:|:---:|:---:|:---:|
| View all transfer records | ✅ | ✅ | ❌ | ✅ |
| Create transfer record | ✅ | ✅ | ❌ | ✅ |
| Edit transfer record | ✅ | ✅ | ❌ | ✅ |
| Delete transfer record | ✅ | ✅ | ❌ | ✅ |
| Filter by direction / date range | ✅ | ✅ | ❌ | ✅ |

**Transfer types:** Permanent · Temporary · Deputation · Secondment

---

### Employee Profiles

Individual employee records linked to a subject code, position, and unit. Used as the baseline for unit-wise breakdown reporting and retirement projections.

| Feature | Super Admin | Planning Officer | Admin Group | Subject Officer |
|---|:---:|:---:|:---:|:---:|
| View employee list | ✅ | ✅ | ❌ | ✅ ¹ |
| Create / edit employee profile | ✅ | ✅ | ❌ | ✅ ¹ |
| Delete employee profile | ✅ | ✅ | ❌ | ✅ ¹ |

> ¹ Subject Officers see only employees under their assigned subject codes. Access also requires `can_view_employees = true` on their account.

Key fields tracked: Pay No., name, gender, date of birth, date of appointment, retirement age, position, subject code, unit.

---

### Acting Appointments

Records officers performing duties in a position above their substantive grade. Prevents double-counting in headcount reports.

| Feature | Super Admin | Planning Officer | Admin Group | Subject Officer |
|---|:---:|:---:|:---:|:---:|
| View acting appointments | ✅ | ✅ | ✅ | ✅ |
| Create acting appointment | ✅ | ✅ | ❌ | ✅ |
| Edit / end acting appointment | ✅ | ✅ | ❌ | ✅ |
| Delete acting appointment | ✅ | ✅ | ❌ | ✅ |

**Route group for CRUD:** `role:subject_officer,super_admin,planning_officer`  
**Route for view:** `role:super_admin,admin_group,planning_officer`

---

### Annual Cadre Review

Structured workflow for proposing changes to the approved cadre for the following year. Routes through Director and MoH approval before being applied.

| Feature | Super Admin | Planning Officer | Admin Group | Subject Officer |
|---|:---:|:---:|:---:|:---:|
| View proposals | ✅ | ✅ | ✅ | ❌ |
| Create draft proposal | ✅ | ✅ | ✅ | ❌ |
| Edit draft | ✅ | ✅ | ✅ | ❌ |
| Delete draft | ✅ | ✅ | ✅ | ❌ |
| Submit for Director approval | ✅ | ✅ | ✅ | ❌ |
| Approve / reject (Director) | ✅ | ✅ | ✅ | ❌ |
| Mark as submitted to MoH | ✅ | ✅ | ✅ | ❌ |
| Mark as MoH-approved | ✅ | ✅ | ✅ | ❌ |
| Apply approved proposal | ✅ | ✅ | ✅ | ❌ |

**Proposal lifecycle:**

```
draft → submitted → director_approved → moh_submitted → approved
                 └──────────────────────────────────→ rejected
```

**Applying** an approved proposal creates new `approved_carders` rows for the proposal year, updating the reference headcount used in all reports.

**Validation rules enforced:**
- Each position may appear only once per proposal
- Reductions > 20% of current approved require a per-item justification
- Setting a position to zero posts requires justification
- At least one item must show a net change

---

### Unit Types & Units

Classifies hospital organisational units for structured reporting.

| Feature | Super Admin | Planning Officer | Admin Group | Subject Officer |
|---|:---:|:---:|:---:|:---:|
| View unit types | ✅ | ✅ | ❌ | ❌ |
| Create / edit unit types | ✅ | ✅ | ❌ | ❌ |
| Assign units to a type | ✅ | ✅ | ❌ | ❌ |
| Delete unit type | ✅ | ✅ | ❌ | ❌ |
| View units | ✅ | ✅ | ❌ | ❌ |
| Create / edit units | ✅ | ✅ | ❌ | ❌ |

**Seeded unit types:** Ward · Department · OPD Clinic · Theatre · ICU / HDU · Laboratory · Pharmacy · Radiology · Administration

---

### Unit Post Allocations

Lets the Planning Officer or Medical Officer Planning directly enter the planned post count and current headcount per unit per position per year — for example: *Ward 01: Sister I × 1, Nursing Officer × 10, Staff Nurse × 5*.

| Feature | Super Admin | Planning Officer | Admin Group | Subject Officer |
|---|:---:|:---:|:---:|:---:|
| View allocation index | ✅ | ✅ | ✅ | ❌ |
| Edit allocations for a unit | ✅ | ✅ | ✅ | ❌ |
| Save / update allocations | ✅ | ✅ | ✅ | ❌ |

**Two fields per unit × position cell:**

| Field | Meaning |
|---|---|
| Allocated Posts | Planned/approved posts for this unit & position (management decision) |
| Actual In Post | Current headcount as known by the Planning Officer |

The **Unit Breakdown Report** uses these figures as its primary data source when available, falling back to employee profile counts for cells without allocation data.

**Validation:** `actual_in_post` more than 50% above `allocated_posts` requires a note (catches data entry errors before save).

**UX features:** Enter key navigates down the column for fast keyboard entry; live vacancy and fill-rate update as you type; unsaved-changes warning on navigation.

---

### Letter Sharing

Subject Officers can upload documents (PDF/Word/images) and share them with selected Senior Management officers for review. Each recipient reviews and records a decision individually.

| Feature | Super Admin | Planning Officer | Admin Group | Subject Officer |
|---|:---:|:---:|:---:|:---:|
| Create and share a letter | ❌ | ❌ | ❌ | ✅ ⁴ |
| View own letters (inbox) | ❌ | ❌ | ✅ ⁵ | ✅ |
| Download / preview attachment | ✅ | ✅ | ✅ ⁵ | ✅ |
| Mark letter as reviewed | ❌ | ❌ | ✅ ⁵ | ❌ |
| Delete letter | ✅ | ❌ | ❌ | ✅ ⁶ |

> ⁴ Subject Officers with `can_view_letters = true` on their account.  
> ⁵ Admin Group users whose User Category has `can_receive_letters = true`.  
> ⁶ Only if no recipient has read it yet.

**Eligible recipient categories** (configurable per category in Admin → User Categories):

| Category | Receives Letters (default) |
|---|:---:|
| Deputy Director General | ✅ |
| Director | ✅ |
| Deputy Director (I–N) | ✅ |
| Administrative Officer / Hospital Secretary | ✅ |
| Chief Clerk | ❌ |
| Medical Officer Planning | ✅ |
| Chief Accountant | ✅ |

> The `can_receive_letters` flag is editable per category by Super Admin with no code deployment.

**Letter recipient picker:** Interactive person-card grid with category filter pills, live search, click-to-select, and a selected-recipients chip strip. All downloads are audit-logged.

---

### Reports

All report routes require the user to be **Super Admin, Planning Officer, or Admin Group**.

| Report | URL | Super Admin | Planning Officer | Admin Group | Subject Officer |
|---|---|:---:|:---:|:---:|:---:|
| Planning Summary | `/planning/summary` | ✅ | ✅ | ❌ | ❌ |
| Officer Submission Rate | `/reports/officer-submissions` | ✅ | ❌ | ✅ | ❌ |
| Report Summary | `/reports/summary` | ✅ | ❌ | ✅ | ❌ |
| 12-Month Trend | `/reports/trend` | ✅ | ✅ | ✅ | ❌ |
| Year-on-Year Comparison | `/reports/yoy` | ✅ | ✅ | ✅ | ❌ |
| Historical Snapshot | `/reports/snapshot` | ✅ | ✅ | ✅ | ❌ |
| Retirement Projections | `/reports/retirement-projections` | ✅ | ✅ | ✅ | ❌ |
| Unit-wise Breakdown | `/reports/unit-breakdown` | ✅ | ✅ | ✅ | ❌ |
| MoH CSV Export | `/reports/export/moh-csv` | ✅ | ✅ | ✅ | ❌ |
| MoH Printable Report | `/reports/export/moh-print` | ✅ | ✅ | ✅ | ❌ |
| Carder Register Print | `/reports/export/carder-register` | ✅ | ✅ | ✅ | ❌ |
| Audit Log | `/audit-logs` | ✅ | ❌ | ✅ | ❌ |

#### Report Descriptions

**Planning Summary** — Approved vs actual per position with fill-rate bars and KPIs. Designed for the Planning Officer's daily review.

**Officer Submission Rate** — 6-month grid showing which subject officers have submitted, carried forward, or missed entries. Identifies gaps before deadlines.

**12-Month Trend** — Line chart of in-position vs approved for a selected position across all 12 months of a chosen year.

**Year-on-Year Comparison** — Side-by-side approved and in-position figures for two selected years at a chosen month. Shows ▲/▼ change per position.

**Historical Snapshot** — Point-in-time "as at date X" view with carry-forward applied. Generates a printable/exportable record that a Director can sign.

**Retirement Projections** — Lists employees retiring within a configurable horizon (6/12/24/36 months). Grouped as Overdue / Critical / Soon / Upcoming using `date_of_birth` and `retirement_age` from employee profiles.

**Unit-wise Breakdown** — Matrix of positions × units showing actual headcount, vacancy, and fill rate. Uses Unit Post Allocations as primary source, falls back to employee profile counts. Vacancy alert strip highlights positions exceeding the configured threshold. Filterable by unit type and position.

**MoH CSV Export** — Downloads a properly formatted CSV with period header, position rows, and totals — ready to upload to the Ministry portal without reformatting.

**MoH Printable Report** — A4 printable HTML table that auto-triggers `window.print()`. Includes an authorised-signature line for official submission.

**Carder Register Print** — A4-landscape printable register showing each position, its approved amount, linked subject codes, and assigned officers.

---

### System Administration

| Feature | Super Admin | Planning Officer | Admin Group | Subject Officer |
|---|:---:|:---:|:---:|:---:|
| User management (CRUD) | ✅ | ❌ | ❌ | ❌ |
| User categories (CRUD) | ✅ | ❌ | ❌ | ❌ |
| Subject codes (CRUD) | ✅ | ❌ | ❌ | ❌ |
| Positions (CRUD) | ✅ | ✅ | ❌ | ❌ |
| Units (CRUD) | ✅ | ✅ | ❌ | ❌ |
| Unit types (CRUD) | ✅ | ✅ | ❌ | ❌ |
| System settings | ✅ | ❌ | ❌ | ❌ |
| IP allowlist management | ✅ | ❌ | ❌ | ❌ |
| Export audit log viewer | ✅ | ❌ | ❌ | ❌ |
| Reset any user's password | ✅ | ❌ | ❌ | ❌ |

---

### Security Controls

Three security controls are enforced at the middleware layer and configurable by Super Admin.

#### Security 16 — Concurrent Session Prevention

Each user account has one valid session at a time. Logging in from a new browser invalidates all previous sessions; the displaced session sees an "ended because you signed in from another device" message on next request.

| Setting | Default |
|---|---|
| `concurrent_session_lock_enabled` | `true` |

#### Security 17 — IP Allowlist

When enabled, only requests from allowlisted CIDR ranges are accepted. IPv4 and IPv6 supported. Loopback (`127.0.0.1`/`::1`) is always permitted.

| Feature | Super Admin | Planning Officer | Admin Group | Subject Officer |
|---|:---:|:---:|:---:|:---:|
| Manage IP allowlist | ✅ | ❌ | ❌ | ❌ |
| Subject to IP enforcement | ✅ ⁷ | ✅ ⁷ | ✅ ⁷ | ✅ ⁷ |

> ⁷ All roles are subject to IP enforcement when enabled — there is no bypass for Super Admin. If the Super Admin's IP is not in the allowlist, they must add it via server-side database access before enabling.

| Setting | Default |
|---|---|
| `ip_allowlist_enabled` | `false` |

#### Security 18 — Export Audit Log

Every file download and data export is recorded with user, export type, filename, resource reference, IP address, and timestamp.

**Audited events:**
- Letter attachment downloads and previews
- MoH CSV export
- MoH printable report
- Carder register print
- Retirement projections export
- Unit allocation updates
- System settings changes

| Feature | Super Admin | Planning Officer | Admin Group | Subject Officer |
|---|:---:|:---:|:---:|:---:|
| View audit log | ✅ | ❌ | ❌ | ❌ |
| Filter by type / user / date | ✅ | ❌ | ❌ | ❌ |
| Subject to audit logging | ✅ | ✅ | ✅ | ✅ |

| Setting | Default |
|---|---|
| `export_audit_enabled` | `true` |

---

## System Settings Reference

All settings are managed by Super Admin at **Admin → Settings** (`/admin/settings`).

| Group | Key | Type | Default | Description |
|---|---|---|---|---|
| deadlines | `deadline_enforcement_enabled` | boolean | `true` | Lock entries after deadline |
| deadlines | `deadline_day` | integer | `15` | Day of following month entries are due (1–28) |
| deadlines | `deadline_grace_days` | integer | `0` | Grace days after deadline day |
| verification | `entry_verification_required` | boolean | `false` | Require verifier approval before entries appear in reports |
| verification | `entry_verifier_role` | string | `planning_officer` | `planning_officer` \| `super_admin` \| `category` |
| verification | `entry_verifier_category_ids` | json | `[]` | Array of UserCategory IDs when role = `category` |
| alerts | `vacancy_alert_enabled` | boolean | `true` | Show vacancy alerts on dashboards |
| alerts | `vacancy_alert_threshold_pct` | integer | `20` | Alert when vacancy % ≥ this value |
| security | `concurrent_session_lock_enabled` | boolean | `true` | One active session per user |
| security | `ip_allowlist_enabled` | boolean | `false` | Enforce CIDR IP allowlist |
| security | `export_audit_enabled` | boolean | `true` | Log all data exports and downloads |

---

## Admin Group Category Reference

User Categories define the sub-role of each Admin Group member. Managed at **Admin → User Categories**.

| Category | Sort Order | Receives Letters | Notes |
|---|:---:|:---:|---|
| Deputy Director General | 10 | ✅ | |
| Director | 20 | ✅ | |
| Deputy Director (I–N) | 30 | ✅ | Multiple officers share one category; each appears individually as a letter recipient |
| Administrative Officer / Hospital Secretary | 40 | ✅ | |
| Chief Clerk | 50 | ❌ | Not a letter recipient by default; toggle per institution |
| Medical Officer Planning | 60 | ✅ | Can be designated as entry verifier in System Settings |
| Chief Accountant | 70 | ✅ | |

> The `can_receive_letters` flag and `sort_order` are editable per category by Super Admin. Changes take effect immediately with no code deployment.

---

## Deployment Checklist

```bash
# 0. PDF export support (required for Reports → Export PDF buttons)
composer require barryvdh/laravel-dompdf
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"

# 1. Run all migrations (000001–000047)
php artisan migrate

# 2. Seed core data
php artisan db:seed --class=UserCategorySeeder   # 7 Admin Group categories
php artisan db:seed --class=SuperAdminSeeder      # creates admin@hims.local

# 3a. FOR DEV / DEMO / TRAINING environments ONLY — fictional test data:
php artisan db:seed --class=DummyDataSeeder
#     ⚠ This seeder hard-aborts if it detects real production data already
#     present (57+ real positions or 8+ real subject codes), to prevent
#     corrupting the official establishment register. Do not force-run it
#     against a production database.

# 3b. FOR PRODUCTION — real data sourced from official MoH Excel registers:
php artisan db:seed --class=ApprovedCarderSeeder      # 57 real positions + approved cadre
php artisan db:seed --class=SubjectCodeOfficerSeeder  # 30 real subject codes + officers
php artisan db:seed --class=MonthlyEntrySeeder        # real headcount snapshot

# 4. Register middleware in app/Http/Kernel.php (if not already done)
# Add to $routeMiddleware:
#   'feature' => \App\Http\Middleware\EnsureFeatureAccess::class,
#   'role'    => \App\Http\Middleware\EnsureUserRole::class,
```

**Environment variables to set in `.env`:**

```dotenv
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=hims_carder
DB_USERNAME=...
DB_PASSWORD=...
SESSION_DRIVER=database
SESSION_LIFETIME=120
```

---

## Default Credentials

> ⚠ **Change immediately after first login.** All seeded accounts have `force_password_change = true`.

| Account | Email | Password | Role |
|---|---|---|---|
| Super Admin | `admin@hims.local` | `ChangeMe@123` | `super_admin` |
| Director | `director@hims.local` | `Password@123` | `admin_group` |
| Deputy Director I | `deputy.director1@hims.local` | `Password@123` | `admin_group` |
| Deputy Director II | `deputy.director2@hims.local` | `Password@123` | `admin_group` |
| Deputy Director III | `deputy.director3@hims.local` | `Password@123` | `admin_group` |
| Admin Officer | `admin.officer@hims.local` | `Password@123` | `admin_group` |
| Chief Clerk | `chief.clerk@hims.local` | `Password@123` | `admin_group` |
| Chief Accountant | `chief.accountant@hims.local` | `Password@123` | `admin_group` |
| Deputy Director General | `ddg@hims.local` | `Password@123` | `admin_group` |
| Medical Officer Planning | `mop@hims.local` | `Password@123` | `admin_group` |
| Planning Officer | `planning@hims.local` | `Password@123` | `planning_officer` |
| Subject Officers (×30) | `officer-EA@hims.local` … | `Password@123` | `subject_officer` |

---

*HIMS PARIKSHA Carder Management System — Loons Lab (Pvt) Ltd*  
*Deployed at Teaching Hospital Peradeniya*
