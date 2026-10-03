BEGIN;

ALTER TABLE letters ADD COLUMN IF NOT EXISTS letterhead_id BIGINT NULL;
ALTER TABLE letters ADD COLUMN IF NOT EXISTS letterhead_snapshot JSONB NULL;
ALTER TABLE letters ADD COLUMN IF NOT EXISTS e_signature_id BIGINT NULL;
ALTER TABLE letter_revisions ADD COLUMN IF NOT EXISTS letterhead_id BIGINT NULL;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'letters_letterhead_id_foreign'
    ) THEN
        ALTER TABLE letters
            ADD CONSTRAINT letters_letterhead_id_foreign
            FOREIGN KEY (letterhead_id)
            REFERENCES service_letter_letterheads(id)
            ON DELETE SET NULL;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'letters_e_signature_id_foreign'
    ) THEN
        ALTER TABLE letters
            ADD CONSTRAINT letters_e_signature_id_foreign
            FOREIGN KEY (e_signature_id)
            REFERENCES e_signatures(id)
            ON DELETE SET NULL;
    END IF;
END $$;

CREATE INDEX IF NOT EXISTS letter_revisions_letterhead_id_index
    ON letter_revisions(letterhead_id);

COMMIT;

UPDATE nav_items
SET label = '🏛 Official Letter Headers', updated_at = CURRENT_TIMESTAMP
WHERE route_name = 'service-letter-letterheads.index';
