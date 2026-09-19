# Carder Management — Officer Experience vNext

Implemented on top of `Carder-Management-SriLanka-workflow-update`.

## 1. Service Letter workflow + Letterhead — COMPLETE

- Guided 4-stage drafting interface: Employee/Purpose/Language → Template/Letterhead → Recipient/Reference → Review.
- English / Sinhala / Tamil template filtering.
- Existing 120 service-letter templates remain available.
- Draft → AO approval → approved/rejected workflow retained.
- Rejected/draft letters can now be edited and resubmitted.
- Official browser-print view added, with DRAFT watermark before approval.
- Configurable letterhead profiles with institution/ministry/department/address/contact/reference prefix/header/footer.
- Optional locally stored header logo/emblem image.
- Configurable signatory designation.
- Original / Copy / Certified Copy / Draft / Confidential marking.
- Recipient, reference number, purpose and letter language stored with each letter.

## 2. Trilingual UI framework — COMPLETE FOUNDATION

- Per-user interface locale stored in `users.locale`.
- English / Sinhala / Tamil switch available in the authenticated header and login screen.
- Laravel translation files added under `lang/en`, `lang/si`, `lang/ta`.
- Application shell, authentication essentials, Service Letter workflow, and priority navigation labels use translations.
- Existing legacy screens not yet given a translation key safely fall back to their existing English wording rather than breaking.

## 3. Feature Toggle management — COMPLETE

Super Admin → Administration → Feature Management.

Toggles:
- Service Letters + Letterheads
- Trilingual Interface
- Structured Service History / Combined Service
- Grade Progression
- AI Record Assistant
- AI Service Letter Assistant

Disabled modules are hidden from dynamic navigation. Core optional modules are also route-protected using `module:*` middleware.

## 4. AI Record Assistant — COMPLETE

Employee 360 → `✨ Record Assistant`.

- Summarises only Carder-recorded employee facts.
- Highlights missing public-service / institution / grade chronology and structured service history.
- Respects the existing WorkforceScopeService employee access rules.
- Default mode is a deterministic offline rules assistant: no internet and no remote API.
- Optional LAN-only OpenAI-compatible endpoint can be configured by Super Admin.
- Public/external endpoints are rejected; only localhost/private/reserved addresses are accepted.

## 5. AI Service Letter Assistant — COMPLETE

Inside Create/Edit Service Letter → `✨ AI-assisted draft`.

- Uses selected employee, purpose, language and optional existing template.
- Officer can give a short drafting instruction.
- Default mode remains fully offline/template based.
- Optional local LAN model can improve wording without sending data outside the hospital network.
- Prompts explicitly prevent invented dates, salary, disciplinary clearance, approvals and other unsupported facts.
- Every generated draft remains editable and must go through human review + AO approval.

## Database migrations

- `2026_09_19_000105_add_letterheads_locale_and_ai_support.php`
- `2026_09_19_000106_seed_officer_experience_feature_settings.php`

## Deployment

```bash
php artisan migrate
php artisan db:seed --class=NavItemSeeder
php artisan storage:link
php artisan optimize:clear
```

`storage:link` is only needed for uploaded letterhead logos.

## Optional local AI

Go to **Administration → Feature Management**.

Leave the LAN AI endpoint blank to use the built-in offline assistant.

For a hospital-local OpenAI-compatible service, configure for example:

```text
http://172.16.x.x:PORT
```

The application rejects public/external AI hosts.

## Verification checklist

1. Switch EN → සිංහල → தமிழ் from Login and header.
2. Create/edit a letterhead and optionally upload a logo.
3. Draft a Service Letter in each language and print preview.
4. Reject the draft as AO, edit it as the drafting officer, and resubmit.
5. Approve and verify the official print view.
6. Disable Service Letters in Feature Management and verify its navigation disappears / route is blocked.
7. Run Employee 360 Record Assistant for an allocated employee.
8. Run AI-assisted Service Letter drafting with local endpoint blank (offline mode).
9. If using a local model, configure a private/LAN endpoint and confirm mode changes to LAN AI.

## Validation performed in build environment

- PHP syntax validation completed successfully for 400 PHP files across app/database/routes/config/lang.
- Full Laravel boot / migration execution could not be run because the supplied project archive does not include `vendor/`.
