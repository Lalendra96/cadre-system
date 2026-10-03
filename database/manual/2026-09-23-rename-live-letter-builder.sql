-- Rename user-facing navigation only. Internal service-letter route names remain unchanged.
UPDATE nav_items
SET label = '✉️ Service & Official Letter Builder', updated_at = NOW()
WHERE route_name = 'service-letters.index';

UPDATE nav_items
SET label = '✍️ Create Service / Official Letter', updated_at = NOW()
WHERE route_name = 'service-letters.create';
