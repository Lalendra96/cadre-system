-- Carder Management: Unit Manager decision-support scope
-- PostgreSQL manual deployment equivalent of migration 2026_09_21_190000.

CREATE TABLE IF NOT EXISTS public.unit_user_decision_scope (
    id bigserial PRIMARY KEY,
    user_id bigint NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
    unit_id bigint NOT NULL REFERENCES public.units(id) ON DELETE RESTRICT,
    assigned_by bigint NULL REFERENCES public.users(id) ON DELETE SET NULL,
    assigned_at timestamp(0) without time zone NULL,
    created_at timestamp(0) without time zone NULL,
    updated_at timestamp(0) without time zone NULL,
    CONSTRAINT uniq_unit_user_decision_scope UNIQUE (user_id, unit_id)
);

CREATE INDEX IF NOT EXISTS idx_unit_user_decision_scope
    ON public.unit_user_decision_scope (unit_id, user_id);

INSERT INTO public.nav_items
    (section, label, route_name, route_params, open_in_new_tab, allowed_roles, custom_checks, sort_order, is_active, created_by, created_at, updated_at)
SELECT
    'Planning', 'Unit Decision Support', 'unit-decision-support.index', NULL, false,
    '["unit_manager","super_admin"]'::json, NULL, 72, true, NULL, NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM public.nav_items WHERE route_name = 'unit-decision-support.index'
);
