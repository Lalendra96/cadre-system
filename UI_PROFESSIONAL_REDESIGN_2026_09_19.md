# Professional Interface Redesign — 2026-09-19

This update applies a professional public-sector cadre-management visual layer based on the approved mockup direction while preserving existing application workflows, permissions and governance safeguards.

## Included

- Navy/blue institutional application shell with clearer hierarchy.
- Professional dense dashboard/card/table/form styling suitable for administrative workforce systems.
- Self-explanatory governance and decision-support notice styling.
- Updated login experience with explicit authorised-use and governance guidance.
- Clear separation between system-generated indicators and official administrative authority.
- Existing light/dark toggle retained.
- Existing navigation logic, permission checks and role visibility retained.
- Existing Subject Officer explicit employee-allocation boundary retained.
- Existing Admin Group aggregate-only employee privacy boundary retained.

## Branding placeholders

Replace these files with institution-approved artwork when available. Keep the filenames to avoid template changes:

- `public/images/branding/hospital-logo-placeholder.svg`
- `public/images/branding/sri-lanka-emblem-placeholder.svg`
- `public/images/branding/sri-lanka-flag-placeholder.svg`

The placeholders are deliberately generic and must not be treated as official artwork.

## Main visual stylesheet

- `public/css/carder-professional.css`

This stylesheet is loaded after `md3-dark.css` so existing page markup continues to work without changing business logic.
