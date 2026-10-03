-- Carder Management - Letter Sharing collaboration enhancements
-- PostgreSQL / Laravel 10 equivalent of migration 2026_09_22_063000

ALTER TABLE letters
    ADD COLUMN IF NOT EXISTS review_due_date date NULL,
    ADD COLUMN IF NOT EXISTS archived_at timestamp(0) without time zone NULL;

ALTER TABLE letter_comments
    ADD COLUMN IF NOT EXISTS comment_type varchar(20) NOT NULL DEFAULT 'comment',
    ADD COLUMN IF NOT EXISTS quoted_text text NULL;

ALTER TABLE letter_revisions
    ADD COLUMN IF NOT EXISTS retention_category varchar(60) NULL,
    ADD COLUMN IF NOT EXISTS access_note text NULL,
    ADD COLUMN IF NOT EXISTS review_due_date date NULL;
