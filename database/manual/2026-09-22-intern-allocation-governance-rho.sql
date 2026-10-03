BEGIN;

ALTER TABLE intern_batches
    ADD COLUMN IF NOT EXISTS start_date date,
    ADD COLUMN IF NOT EXISTS end_date date,
    ADD COLUMN IF NOT EXISTS assigned_subject_officer_id bigint,
    ADD COLUMN IF NOT EXISTS responsibility_assigned_by bigint,
    ADD COLUMN IF NOT EXISTS responsibility_assigned_at timestamp(0) without time zone;

CREATE INDEX IF NOT EXISTS intern_batches_start_end_date_idx
    ON intern_batches (start_date, end_date);

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'intern_batches_assigned_subject_officer_id_foreign'
    ) THEN
        ALTER TABLE intern_batches
            ADD CONSTRAINT intern_batches_assigned_subject_officer_id_foreign
            FOREIGN KEY (assigned_subject_officer_id) REFERENCES users(id) ON DELETE SET NULL;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'intern_batches_responsibility_assigned_by_foreign'
    ) THEN
        ALTER TABLE intern_batches
            ADD CONSTRAINT intern_batches_responsibility_assigned_by_foreign
            FOREIGN KEY (responsibility_assigned_by) REFERENCES users(id) ON DELETE SET NULL;
    END IF;
END $$;

ALTER TABLE interns
    ADD COLUMN IF NOT EXISTS is_active boolean NOT NULL DEFAULT true,
    ADD COLUMN IF NOT EXISTS disabled_by bigint,
    ADD COLUMN IF NOT EXISTS disabled_at timestamp(0) without time zone,
    ADD COLUMN IF NOT EXISTS disable_reason text;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'interns_disabled_by_foreign'
    ) THEN
        ALTER TABLE interns
            ADD CONSTRAINT interns_disabled_by_foreign
            FOREIGN KEY (disabled_by) REFERENCES users(id) ON DELETE SET NULL;
    END IF;
END $$;

CREATE TABLE IF NOT EXISTS intern_rho_placements (
    id bigserial PRIMARY KEY,
    intern_batch_id bigint NOT NULL REFERENCES intern_batches(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    intern_id bigint NOT NULL REFERENCES interns(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    status varchar(30) NOT NULL DEFAULT 'pending',
    placement_institution varchar(180),
    placement_unit varchar(180),
    effective_date date,
    reference_no varchar(100),
    notes text,
    recorded_by bigint REFERENCES users(id) ON DELETE SET NULL,
    updated_by bigint REFERENCES users(id) ON DELETE SET NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT intern_rho_placements_batch_intern_unique UNIQUE (intern_batch_id, intern_id),
    CONSTRAINT intern_rho_placements_status_check CHECK (status IN ('pending', 'placed', 'deferred', 'not_placed'))
);

CREATE INDEX IF NOT EXISTS intern_rho_placements_batch_status_idx
    ON intern_rho_placements (intern_batch_id, status);

COMMIT;
