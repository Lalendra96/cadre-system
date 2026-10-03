-- Role-aware analytics dashboard personal layouts.
-- PostgreSQL / Laravel 10 compatible.
-- Stores presentation preferences only; no analytics data or permissions are persisted here.

CREATE TABLE IF NOT EXISTS user_dashboard_layouts (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    role_key VARCHAR(80) NOT NULL,
    layout JSONB NOT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    CONSTRAINT user_dashboard_layouts_user_id_foreign
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT user_dashboard_layouts_user_role_unique
        UNIQUE (user_id, role_key)
);
