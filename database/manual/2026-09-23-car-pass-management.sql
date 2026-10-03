-- Laravel migration equivalent: 2026_09_23_054500_create_car_pass_management_tables.php
-- PostgreSQL 14+
-- Apply only once and back up the database first.

CREATE TABLE IF NOT EXISTS car_pass_responsibility_assignments (
    id BIGSERIAL PRIMARY KEY,
    subject_officer_id BIGINT NOT NULL REFERENCES users(id),
    effective_from DATE NOT NULL,
    effective_to DATE NULL,
    reason TEXT NOT NULL,
    reference_no VARCHAR(100) NULL,
    assigned_by BIGINT NOT NULL REFERENCES users(id),
    ended_at TIMESTAMP NULL,
    ended_by BIGINT NULL REFERENCES users(id),
    end_reason TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE TABLE IF NOT EXISTS car_pass_templates (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(50) NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size BIGINT NOT NULL,
    allowed_position_ids JSONB NOT NULL,
    default_validity_months SMALLINT NOT NULL DEFAULT 12,
    version INTEGER NOT NULL DEFAULT 1,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    uploaded_by BIGINT NOT NULL REFERENCES users(id),
    disabled_by BIGINT NULL REFERENCES users(id),
    disabled_at TIMESTAMP NULL,
    disable_reason TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE (code, version)
);

CREATE TABLE IF NOT EXISTS car_pass_requests (
    id BIGSERIAL PRIMARY KEY,
    uuid UUID NOT NULL UNIQUE,
    reference_no VARCHAR(60) NOT NULL UNIQUE,
    employee_id BIGINT NOT NULL REFERENCES employees(id),
    template_id BIGINT NOT NULL REFERENCES car_pass_templates(id),
    employee_name_snapshot VARCHAR(180) NOT NULL,
    pay_no_snapshot VARCHAR(60) NULL,
    position_snapshot VARCHAR(180) NULL,
    unit_snapshot VARCHAR(180) NULL,
    template_name_snapshot VARCHAR(150) NOT NULL,
    template_code_snapshot VARCHAR(50) NOT NULL,
    template_version_snapshot INTEGER NOT NULL,
    template_image_snapshot_path VARCHAR(500) NOT NULL,
    vehicle_registration_no VARCHAR(40) NOT NULL,
    vehicle_type VARCHAR(40) NOT NULL,
    valid_from DATE NOT NULL,
    valid_to DATE NOT NULL,
    purpose TEXT NULL,
    notes TEXT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'draft',
    prepared_by BIGINT NOT NULL REFERENCES users(id),
    submitted_at TIMESTAMP NULL,
    reviewed_by BIGINT NULL REFERENCES users(id),
    reviewed_at TIMESTAMP NULL,
    decision_note TEXT NULL,
    approved_at TIMESTAMP NULL,
    issued_at TIMESTAMP NULL,
    issued_by BIGINT NULL REFERENCES users(id),
    revoked_at TIMESTAMP NULL,
    revoked_by BIGINT NULL REFERENCES users(id),
    revocation_reason TEXT NULL,
    verification_token_hash VARCHAR(128) NULL,
    content_hash VARCHAR(128) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE INDEX IF NOT EXISTS car_pass_requests_employee_status_idx ON car_pass_requests(employee_id, status);
CREATE INDEX IF NOT EXISTS car_pass_requests_status_submitted_idx ON car_pass_requests(status, submitted_at);
CREATE INDEX IF NOT EXISTS car_pass_requests_prepared_created_idx ON car_pass_requests(prepared_by, created_at);

INSERT INTO system_settings (key, value, type, "group", label, description, created_at, updated_at)
VALUES
('feature_car_passes', 'false', 'boolean', 'features', 'Car Pass Management', 'Enable the governed Car Pass workflow.', NOW(), NOW()),
('car_pass_approval_role', 'admin_group', 'string', 'car_passes', 'Car Pass approval role', 'Independent role authorised to approve Car Pass requests.', NOW(), NOW())
ON CONFLICT (key) DO UPDATE SET
    value = EXCLUDED.value,
    type = EXCLUDED.type,
    "group" = EXCLUDED."group",
    label = EXCLUDED.label,
    description = EXCLUDED.description,
    updated_at = NOW();

-- Navigation entries (safe for existing databases where route_name may not be unique).
INSERT INTO nav_items (
    section, label, route_name, route_params, open_in_new_tab,
    allowed_roles, custom_checks, sort_order, is_active, created_by, created_at, updated_at
)
SELECT
    'My Work', '🚗 Car Pass Management', 'car-passes.index', NULL, FALSE,
    '["subject_officer","admin_group","planning_officer","super_admin"]'::json,
    NULL, 405, TRUE, NULL, NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM nav_items WHERE route_name = 'car-passes.index'
);

INSERT INTO nav_items (
    section, label, route_name, route_params, open_in_new_tab,
    allowed_roles, custom_checks, sort_order, is_active, created_by, created_at, updated_at
)
SELECT
    'Administration', '🚗 Car Pass Administration', 'car-passes.admin.index', NULL, FALSE,
    '["super_admin"]'::json,
    NULL, 445, TRUE, NULL, NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM nav_items WHERE route_name = 'car-passes.admin.index'
);
