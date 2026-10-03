# Animated interface, chart controls, contextual help and AI audit update

## Install over the previous offline package

Copy the project files over the existing Laravel 10 installation. Preserve `.env`, `vendor/`, `bootstrap/` and `storage/`.

```bash
php artisan optimize:clear
php artisan view:cache
```

Hard-refresh the browser (Ctrl+F5). No database migration is introduced. The existing `audit_logs` table must be available for assistant operations. Do not restore the historical SQL backup for this update.

All new scripts and styles are local. The previous offline dependencies are retained; Composer runtime dependencies are still supplied by your existing installation.

## Motion and temporal history

- Dashboard cards enter with a short staggered animation; hover states emphasize the active panel.
- Historical organisation shows the latest 12 actual recorded events up to the selected as-at date, in chronological order.
- Replay timeline animates the displayed events. It does not recompute, rewrite or simulate a different historical state.
- Empty history remains empty; no demonstration events are inserted.
- Browser/device reduced-motion preferences disable non-essential motion and Chart.js animation.

## Charts

- Rounded bars, clearer hover markers, improved theme-aware tooltips and short entry animations.
- Highlight largest value finds the largest numeric value among visible series/points. Clear highlight removes emphasis.
- Chart guide & data table shows the underlying values (up to the first 100 categories) for easier comparison and keyboard access.
- Existing series colors, values and chart scale choices are preserved. Only presentation and inspection controls change.

## Contextual help

`config/section_help.php` supplies route-specific guidance for dashboards, employee records, service history, governance, AI, imports, allocations, letters, audit logs and administration.

The governance workspace also has help within individual eligibility, forecasting, temporal, reconciliation, document, case-bundle, export, rule and provenance sections. Unknown screens no longer receive unrelated employee-profile instructions. Detailed steps are in English; selected employee/AI/letter/dashboard topics also have Sinhala and Tamil summaries when those UI locales are selected.

## AI behavior and audit

Record Assistant now shows a working state, its actual execution source, a review notice and an audit reference. Repeated clicks are blocked while a request runs.

Service-letter AI returns a preview. It does not immediately overwrite the editor. **Use this draft** records the selection and then places it in the editable body. The letter still needs to be reviewed and saved. Changing the employee/template/language/purpose/instructions invalidates the preview. Responses for a changed selection are discarded. If the body changes while the selection is being recorded, that new edit is preserved.

The employee summary endpoint now uses POST and CSRF protection. Any custom client calling the old GET endpoint must be updated.

Use the existing **Audit Log** screen. Paste an exact AI audit reference to find its related entries, or select one of these action filters:

| Action | Meaning |
|---|---|
| AI / assistant started | A scoped, authorised assistant execution was requested |
| LAN AI completed | The hospital LAN model returned usable text |
| Offline assistant used | Rules/templates produced the result, including when the LAN model was unconfigured or unavailable |
| AI / assistant failed | Generation failed before a usable result |
| Assisted draft selected | The user selected a generated letter preview for the editor; this does not mean the letter was saved or approved |

The trail records the user, employee reference, timestamp, IP, correlated request reference, capability, result mode, provider status, elapsed milliseconds and applicable template/language metadata. It does not duplicate prompts, employee facts, generated bodies, endpoint credentials or raw exception messages. A started event without an outcome may indicate an interrupted request or an unavailable audit store. Generation is not started if its initial audit entry cannot be written. An output is not returned to the client if completion logging fails.

Applied-draft references are checked against the current user, employee and completed letter-draft event, with the employee access check repeated server-side.

## Validation

- PHP syntax and compiled Blade checks across the application.
- 33 whitespace-validation cases retained from the previous update.
- 30 focused SQLite assertions covering AI modes, failure/fallback logs, sensitive-content exclusion, contextual help and historical snapshot chronology.
- Chromium tests covering chart highlight/data tables, timeline replay, reduced motion, AI duplicate prevention, failure recovery, preview application, audit failure behavior, stale-selection protection, light/dark themes, mobile layout and local-only assets.

Run the included PHP regression scripts in a development/test installation with Composer development dependencies and PDO SQLite:

```bash
php tests/offline-interface-regression.php
php tests/ai-experience-regression.php
```

Validation uses synthetic fixtures and an available Laravel 10 test runtime. Full authenticated hospital workflows and the live LAN model still need staging verification. No production database data was changed.
