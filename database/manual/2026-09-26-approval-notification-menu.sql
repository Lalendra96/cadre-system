-- Optional manual navigation update for installations where migrations are applied manually.
INSERT INTO nav_items (
    section, label, route_name, route_params, open_in_new_tab,
    allowed_roles, custom_checks, sort_order, is_active, created_at, updated_at
)
SELECT
    'Workflow & Approvals',
    'Requests for Approval',
    'approval-requests.index',
    NULL,
    FALSE,
    '["super_admin","admin_group","planning_officer"]'::json,
    NULL,
    5,
    TRUE,
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP
WHERE NOT EXISTS (
    SELECT 1 FROM nav_items WHERE route_name = 'approval-requests.index'
);
