CREATE TABLE IF NOT EXISTS profile_completion_targets (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    subject_code_id BIGINT NOT NULL REFERENCES subject_codes(id) ON DELETE RESTRICT,
    position_id BIGINT NOT NULL REFERENCES positions(id) ON DELETE RESTRICT,
    target_count INTEGER NOT NULL CHECK (target_count >= 0),
    effective_from DATE NOT NULL,
    source_reference VARCHAR(150) NOT NULL,
    notes TEXT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_by BIGINT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    updated_by BIGINT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    disabled_by BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    disabled_at TIMESTAMP NULL,
    disable_reason TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE INDEX IF NOT EXISTS pct_target_scope_idx
    ON profile_completion_targets(user_id, subject_code_id, position_id, is_active);

INSERT INTO nav_items (
    section, label, route_name, route_params, open_in_new_tab,
    allowed_roles, custom_checks, sort_order, is_active, created_by,
    created_at, updated_at
)
SELECT
    'Workforce', '📊 Profile Completion Tracking', 'employee-profile-completion.index', NULL, FALSE,
    '["super_admin","planning_officer","admin_group","subject_officer"]', NULL,
    245, TRUE, 1, NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM nav_items WHERE route_name = 'employee-profile-completion.index'
);
