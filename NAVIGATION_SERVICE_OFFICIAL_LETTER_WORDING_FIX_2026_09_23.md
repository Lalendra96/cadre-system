# Service & Official Letter navigation wording fix — 2026-09-23

The sidebar resolves route-specific labels from `lang/{locale}/nav.php` before falling back to the `nav_items.label` database value. The DB rows had already been renamed, but the English, Sinhala and Tamil translation files still contained the former Live Letter labels, so the sidebar continued to display them.

Updated route translations:

- `service-letters.index` → **Service & Official Letter Builder**
- `service-letters.create` → **Create Service / Official Letter**

Equivalent Sinhala and Tamil wording is also updated.

A normalization migration and manual PostgreSQL script are included to keep existing `nav_items` rows, roles and active state aligned with the translated navigation.
