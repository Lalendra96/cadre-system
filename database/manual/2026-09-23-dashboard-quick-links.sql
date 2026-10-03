-- Dashboard Quick Links support for PostgreSQL installations that do not use Laravel migrations.
ALTER TABLE public.user_dashboard_layouts
    ADD COLUMN IF NOT EXISTS quick_links jsonb;
