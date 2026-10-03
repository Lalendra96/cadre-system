-- Normalize existing DB-backed navigation for Service & Official Letters.
UPDATE nav_items
SET section = 'Letters & Documents',
    label = '✉️ Service & Official Letter Builder',
    allowed_roles = '["subject_officer","planning_officer","admin_group","super_admin"]',
    is_active = TRUE,
    updated_at = NOW()
WHERE route_name = 'service-letters.index';

UPDATE nav_items
SET section = 'Letters & Documents',
    label = '✍️ Create Service / Official Letter',
    allowed_roles = '["subject_officer","planning_officer","super_admin"]',
    is_active = TRUE,
    updated_at = NOW()
WHERE route_name = 'service-letters.create';
