-- Secure Live Letter Editor — PostgreSQL 14+
-- Manual alternative to the Laravel migration for environments managed through pgAdmin.
-- BACK UP THE DATABASE FIRST. Run once as a role allowed to alter the Carder schema.

BEGIN;

ALTER TABLE public.service_letters
    ADD COLUMN IF NOT EXISTS document_classification varchar(30) NOT NULL DEFAULT 'internal',
    ADD COLUMN IF NOT EXISTS contains_personal_data boolean NOT NULL DEFAULT true,
    ADD COLUMN IF NOT EXISTS retention_category varchar(40) NOT NULL DEFAULT 'official_record',
    ADD COLUMN IF NOT EXISTS access_note varchar(300),
    ADD COLUMN IF NOT EXISTS editor_lock_version integer NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS approved_content_hash varchar(128),
    ADD COLUMN IF NOT EXISTS submitted_at timestamp(0) without time zone,
    ADD COLUMN IF NOT EXISTS issued_at timestamp(0) without time zone;

CREATE TABLE IF NOT EXISTS public.service_letter_revisions (
    id bigserial PRIMARY KEY,
    service_letter_id bigint NOT NULL REFERENCES public.service_letters(id) ON DELETE CASCADE,
    version_number integer NOT NULL,
    subject varchar(200) NOT NULL,
    rendered_body text NOT NULL,
    reference_no varchar(100),
    snapshot_hash varchar(128) NOT NULL,
    change_reason varchar(120) NOT NULL DEFAULT 'autosave',
    created_by bigint NOT NULL REFERENCES public.users(id),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);
CREATE UNIQUE INDEX IF NOT EXISTS sl_revision_version_unique ON public.service_letter_revisions(service_letter_id, version_number);
CREATE INDEX IF NOT EXISTS sl_revision_letter_created_idx ON public.service_letter_revisions(service_letter_id, created_at);

CREATE TABLE IF NOT EXISTS public.service_letter_comments (
    id bigserial PRIMARY KEY,
    service_letter_id bigint NOT NULL REFERENCES public.service_letters(id) ON DELETE CASCADE,
    user_id bigint NOT NULL REFERENCES public.users(id),
    body text NOT NULL,
    is_resolved boolean NOT NULL DEFAULT false,
    resolved_by bigint REFERENCES public.users(id),
    resolved_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);
CREATE INDEX IF NOT EXISTS sl_comments_letter_resolved_idx ON public.service_letter_comments(service_letter_id, is_resolved);

CREATE TABLE IF NOT EXISTS public.service_letter_presence (
    id bigserial PRIMARY KEY,
    service_letter_id bigint NOT NULL REFERENCES public.service_letters(id) ON DELETE CASCADE,
    user_id bigint NOT NULL REFERENCES public.users(id),
    last_seen_at timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);
CREATE UNIQUE INDEX IF NOT EXISTS sl_presence_user_unique ON public.service_letter_presence(service_letter_id, user_id);
CREATE INDEX IF NOT EXISTS sl_presence_letter_seen_idx ON public.service_letter_presence(service_letter_id, last_seen_at);

COMMIT;
