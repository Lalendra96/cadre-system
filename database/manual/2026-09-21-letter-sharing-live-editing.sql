-- Carder Management / Laravel 10
-- Governed Live Letter Editing integrated into the existing Letter Sharing workflow.
-- Use this only when migrations cannot be run. Back up PostgreSQL first.

ALTER TABLE public.letters ADD COLUMN IF NOT EXISTS live_edit_enabled boolean NOT NULL DEFAULT false;
ALTER TABLE public.letters ADD COLUMN IF NOT EXISTS live_content text;
ALTER TABLE public.letters ADD COLUMN IF NOT EXISTS reference_no varchar(100);
ALTER TABLE public.letters ADD COLUMN IF NOT EXISTS workflow_status varchar(30) NOT NULL DEFAULT 'draft';
ALTER TABLE public.letters ADD COLUMN IF NOT EXISTS document_classification varchar(30) NOT NULL DEFAULT 'internal';
ALTER TABLE public.letters ADD COLUMN IF NOT EXISTS contains_personal_data boolean NOT NULL DEFAULT false;
ALTER TABLE public.letters ADD COLUMN IF NOT EXISTS retention_category varchar(60) NOT NULL DEFAULT 'official_correspondence';
ALTER TABLE public.letters ADD COLUMN IF NOT EXISTS access_note text;
ALTER TABLE public.letters ADD COLUMN IF NOT EXISTS editor_lock_version integer NOT NULL DEFAULT 1;
ALTER TABLE public.letters ADD COLUMN IF NOT EXISTS approved_content_hash varchar(128);
ALTER TABLE public.letters ADD COLUMN IF NOT EXISTS approved_by bigint REFERENCES public.users(id) ON DELETE SET NULL;
ALTER TABLE public.letters ADD COLUMN IF NOT EXISTS approved_at timestamp without time zone;
ALTER TABLE public.letters ADD COLUMN IF NOT EXISTS issued_at timestamp without time zone;
CREATE INDEX IF NOT EXISTS idx_letters_live_workflow ON public.letters(workflow_status, live_edit_enabled);

ALTER TABLE public.letter_recipients ADD COLUMN IF NOT EXISTS access_level varchar(20) NOT NULL DEFAULT 'reviewer';

CREATE TABLE IF NOT EXISTS public.letter_revisions (
    id bigserial PRIMARY KEY,
    letter_id bigint NOT NULL REFERENCES public.letters(id) ON DELETE CASCADE,
    version_number integer NOT NULL,
    title varchar(200) NOT NULL,
    live_content text,
    reference_no varchar(100),
    document_classification varchar(30) NOT NULL DEFAULT 'internal',
    contains_personal_data boolean NOT NULL DEFAULT false,
    snapshot_hash varchar(128) NOT NULL,
    change_reason varchar(120),
    created_by bigint NOT NULL REFERENCES public.users(id) ON DELETE RESTRICT,
    created_at timestamp without time zone,
    updated_at timestamp without time zone,
    UNIQUE(letter_id, version_number)
);

CREATE TABLE IF NOT EXISTS public.letter_comments (
    id bigserial PRIMARY KEY,
    letter_id bigint NOT NULL REFERENCES public.letters(id) ON DELETE CASCADE,
    user_id bigint NOT NULL REFERENCES public.users(id) ON DELETE RESTRICT,
    body text NOT NULL,
    is_resolved boolean NOT NULL DEFAULT false,
    resolved_by bigint REFERENCES public.users(id) ON DELETE SET NULL,
    resolved_at timestamp without time zone,
    created_at timestamp without time zone,
    updated_at timestamp without time zone
);

CREATE TABLE IF NOT EXISTS public.letter_presence (
    id bigserial PRIMARY KEY,
    letter_id bigint NOT NULL REFERENCES public.letters(id) ON DELETE CASCADE,
    user_id bigint NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
    last_seen_at timestamp without time zone NOT NULL,
    created_at timestamp without time zone,
    updated_at timestamp without time zone,
    UNIQUE(letter_id, user_id)
);
CREATE INDEX IF NOT EXISTS idx_letter_presence_live ON public.letter_presence(letter_id, last_seen_at);

INSERT INTO public.system_settings (key,value,type,"group",label,description,created_at,updated_at)
VALUES ('feature_live_letter_editing','true','boolean','features','Live Letter Editing',
        'Enable governed collaborative editing inside Letter Sharing. When disabled, normal attachment sharing and review remain available.',NOW(),NOW())
ON CONFLICT (key) DO UPDATE SET value=EXCLUDED.value,type=EXCLUDED.type,"group"=EXCLUDED."group",label=EXCLUDED.label,description=EXCLUDED.description,updated_at=NOW();
