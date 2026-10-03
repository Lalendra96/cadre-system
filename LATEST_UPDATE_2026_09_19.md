# Latest Update — 19 September 2026

This build keeps the existing interface styling and adds the missing governance/legal-safeguard functionality:

- Governance & Official Authority profile
- Business Rule Register with source references and immutable version history
- Human approval queue for consequential HR actions
- Configuration and decision audit history
- AI advisory-only capability guard
- Incident & Correction Register
- Internal decision-support notices
- Tri-language Administrative Support System notice after successful login
- Existing offline OTP MFA retained and reformatted
- Subject Officer employee privacy remains based on explicit employee allocation

No existing CSS/theme files were replaced as part of this update.

## Professional Interface Redesign

A new professional public-sector interface layer has been added based on the approved cadre-management mockup direction.

- Added `public/css/carder-professional.css` and loaded it after the existing MD3 stylesheet.
- Updated the authenticated application header and navigation presentation.
- Updated the login interface with clearer authorised-use and governance guidance.
- Restyled decision-support notices, tables, forms, cards, status indicators and dense administrative screens.
- Added replaceable placeholders for the hospital logo, Sri Lankan emblem and Sri Lankan flag under `public/images/branding/`.
- Preserved existing light/dark theme behaviour.
- No workforce permission, Subject Officer allocation, Admin Group privacy, administrative-decision or incident-governance business logic was intentionally changed by the UI update.

See `UI_PROFESSIONAL_REDESIGN_2026_09_19.md` for branding replacement paths and design notes.
