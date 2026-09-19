# Sri Lankan Health Sector Officer Workflow Update

## What changed

### Subject Officer workflow
- Sidebar is task-oriented: **🏠 Today & My Work**, **📅 My Actions**, **👥 My Employees**, **📂 Service History**, **🏛 Register Incoming Officer**, transfers, acting appointments, data quality, retirements and service letters.
- Technical HR Intelligence / responsibility-management screens are no longer shown to Subject Officers.
- Increment-specific Subject Officer menu entries were removed from the primary workflow. Increment history is retained as a secondary record.
- Workspace prioritises grade progression, incomplete service histories, retirements, professional-registration expiry, returned service letters and data quality.
- Employee access remains limited to individually allocated Employee Profiles.

### Planning Officer workflow
- `Planning Summary` is now presented as **📊 Planning Dashboard**.
- Adds aggregate incoming/outgoing movement, net movement, external/Combined-Service arrivals, expected releases, retirement pressure and grade-progression pipeline.
- Existing cadre vs actual, reconciliation and forecasting remain available.
- Admin Group still receives aggregate/count views only and is not given individual service-history access.

### Low-ICT workflow improvements
- Added **Guided New Employee** wizard.
- Added separate **Incoming Officer** wizard for officers transferred from another government institution/agency.
- Plain-language questions are used in the wizards instead of database terminology.
- Validation messages explain that entered data is retained.
- Employee Profile form has local-browser draft autosave for entered fields.
- Employee 360 contains a **What do you want to record?** action panel to route the officer to the correct workflow.
- Transfer recording now has a mandatory review confirmation.

### Combined Service / cross-agency service history
New table: `employee_service_periods`.

It records career/posting periods without requiring all historic institutions/positions to exist in hospital master data. A period can store:
- service / cadre name;
- Central/Provincial/other sector;
- ministry/department;
- institution name;
- configured position or free-text historic position;
- configured grade or free-text historic grade;
- start/end dates;
- movement type/reference;
- whether the period counts for service, grade service and pension;
- source document;
- verification status;
- current-posting flag.

The employee master now also has:
- `current_service_name`
- `combined_service_name`
- `date_joined_combined_service`

Public Service start, Combined Service start, current-grade start and the date reported for duty to this hospital remain separate facts.

### Incoming officer workflow
The guided workflow captures:
1. officer identity;
2. previous institution/ministry;
3. current hospital position/unit;
4. Public/Combined Service dates and current-grade start;
5. transfer/release reference;
6. review and confirmation.

Saving creates:
- Employee Profile;
- previous service/posting period;
- current TH Peradeniya posting period;
- grade-history record using the historic grade start date when supplied;
- incoming transfer record;
- Subject Officer employee allocation when created by an allocated Subject Officer.

This prevents a transfer into the hospital from incorrectly restarting the officer's service or grade clock.

### Employee 360
Employment Snapshot now distinguishes:
- Current Service;
- Combined Service and start date;
- Current Position;
- Current Grade and grade-start date;
- Current Institution;
- Current Unit/Ward;
- Joined Public Service;
- Reported for Duty to this institute;
- Total Public Service;
- Service at this Institution;
- grade progression and retirement.

The lifecycle timeline also includes structured service/posting periods.

### Transfer categories
Expanded to support:
- Internal Unit Transfer
- Internal Institutional Transfer
- Incoming External Transfer
- Outgoing External Transfer
- Temporary Attachment
- Permanent Release
- Secondment
- Deputation
- Reversion
- Inter-Ministry Transfer
- Inter-Department Transfer
- Provincial ↔ Central movement
- Other

## Deployment

```bash
php artisan migrate
php artisan db:seed --class=NavItemSeeder
php artisan optimize:clear
```

Migration added:

`2026_09_18_000104_create_employee_service_periods.php`

## Verification

After migration, test these flows:

1. Subject Officer sees only allocated employees.
2. Subject Officer opens **🏛 Register Incoming Officer** and records a Development Officer arriving from another institution.
3. Confirm Employee 360 shows different dates for Public Service / Combined Service / TH Peradeniya reporting / current grade.
4. Open **📂 Service History** and confirm previous and current institutions are separate rows.
5. Confirm grade promotion calculations use the Grade History effective date carried from the previous institution.
6. Planning Dashboard shows aggregate incoming/outgoing counts.
7. Admin Group cannot open individual Employee Profiles / Service History.
8. Record a transfer and confirm the review checkbox is required.

## Validation performed in this package
All PHP files under `app`, migrations, seeders and routes were checked with `php -l` and passed syntax validation. Full Laravel boot/route tests could not be executed in the build environment because the supplied project package does not contain `vendor/`.
