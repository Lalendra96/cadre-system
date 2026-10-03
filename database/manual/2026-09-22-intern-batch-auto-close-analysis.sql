-- Intern Medical Officer Allocation: batch closure metadata used by the
-- governed auto-close workflow. Run only if Laravel migrations cannot be used.

ALTER TABLE intern_batches
    ADD COLUMN IF NOT EXISTS closed_at timestamp(0) without time zone,
    ADD COLUMN IF NOT EXISTS closed_by bigint,
    ADD COLUMN IF NOT EXISTS close_reason varchar(500),
    ADD COLUMN IF NOT EXISTS closed_automatically boolean NOT NULL DEFAULT false;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = 'intern_batches_closed_by_foreign'
    ) THEN
        ALTER TABLE intern_batches
            ADD CONSTRAINT intern_batches_closed_by_foreign
            FOREIGN KEY (closed_by) REFERENCES users(id) ON DELETE SET NULL;
    END IF;
END $$;
