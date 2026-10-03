-- Carder Management: surface the Secure Live Letter Builder in existing databases.
-- Safe to run once on PostgreSQL after taking a backup.
BEGIN;

UPDATE nav_items
SET is_active = false,
    updated_at = CURRENT_TIMESTAMP
WHERE route_name IN (
    'service-letters.index',
    'service-letters.create',
    'service-letter-templates.index',
    'service-letter-letterheads.index',
    'e-signatures.edit'
)
AND COALESCE(section, '') <> 'Letters & Documents';

INSERT INTO nav_items
(section, label, route_name, route_params, open_in_new_tab, allowed_roles, custom_checks, sort_order, is_active, created_by, created_at, updated_at)
SELECT 'Letters & Documents', '✉️ Service & Official Letter Builder', 'service-letters.index', NULL, false,
       '["subject_officer","planning_officer","admin_group","super_admin"]'::json,
       NULL, 10, true, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
WHERE NOT EXISTS (
    SELECT 1 FROM nav_items WHERE section = 'Letters & Documents' AND route_name = 'service-letters.index'
);

INSERT INTO nav_items
(section, label, route_name, route_params, open_in_new_tab, allowed_roles, custom_checks, sort_order, is_active, created_by, created_at, updated_at)
SELECT 'Letters & Documents', '✍️ Create Service / Official Letter', 'service-letters.create', NULL, false,
       '["subject_officer","planning_officer","super_admin"]'::json,
       NULL, 15, true, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
WHERE NOT EXISTS (
    SELECT 1 FROM nav_items WHERE section = 'Letters & Documents' AND route_name = 'service-letters.create'
);

INSERT INTO nav_items
(section, label, route_name, route_params, open_in_new_tab, allowed_roles, custom_checks, sort_order, is_active, created_by, created_at, updated_at)
SELECT 'Letters & Documents', '🧩 Letter Templates', 'service-letter-templates.index', NULL, false,
       '["super_admin"]'::json, NULL, 20, true, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
WHERE NOT EXISTS (
    SELECT 1 FROM nav_items WHERE section = 'Letters & Documents' AND route_name = 'service-letter-templates.index'
);

INSERT INTO nav_items
(section, label, route_name, route_params, open_in_new_tab, allowed_roles, custom_checks, sort_order, is_active, created_by, created_at, updated_at)
SELECT 'Letters & Documents', '🏛 Letterheads', 'service-letter-letterheads.index', NULL, false,
       '["super_admin"]'::json, NULL, 30, true, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
WHERE NOT EXISTS (
    SELECT 1 FROM nav_items WHERE section = 'Letters & Documents' AND route_name = 'service-letter-letterheads.index'
);

INSERT INTO nav_items
(section, label, route_name, route_params, open_in_new_tab, allowed_roles, custom_checks, sort_order, is_active, created_by, created_at, updated_at)
SELECT 'Letters & Documents', '✍️ My E-Signature', 'e-signatures.edit', NULL, false,
       '["admin_group"]'::json, NULL, 40, true, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
WHERE NOT EXISTS (
    SELECT 1 FROM nav_items WHERE section = 'Letters & Documents' AND route_name = 'e-signatures.edit'
);

-- If a prior row for the same section/route already exists, normalize it.
UPDATE nav_items
SET label = CASE route_name
        WHEN 'service-letters.index' THEN '✉️ Service & Official Letter Builder'
        WHEN 'service-letters.create' THEN '✍️ Create Service / Official Letter'
        WHEN 'service-letter-templates.index' THEN '🧩 Letter Templates'
        WHEN 'service-letter-letterheads.index' THEN '🏛 Letterheads'
        WHEN 'e-signatures.edit' THEN '✍️ My E-Signature'
    END,
    allowed_roles = CASE route_name
        WHEN 'service-letters.index' THEN '["subject_officer","planning_officer","admin_group","super_admin"]'::json
        WHEN 'service-letters.create' THEN '["subject_officer","planning_officer","super_admin"]'::json
        WHEN 'service-letter-templates.index' THEN '["super_admin"]'::json
        WHEN 'service-letter-letterheads.index' THEN '["super_admin"]'::json
        WHEN 'e-signatures.edit' THEN '["admin_group"]'::json
    END,
    custom_checks = NULL,
    is_active = true,
    updated_at = CURRENT_TIMESTAMP
WHERE section = 'Letters & Documents'
AND route_name IN (
    'service-letters.index',
    'service-letters.create',
    'service-letter-templates.index',
    'service-letter-letterheads.index',
    'e-signatures.edit'
);

COMMIT;
