<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication security controls
    |--------------------------------------------------------------------------
    |
    | Defaults align the application with Sri Lanka CERT guidance. Values may
    | be tuned by the organisation's approved Information Security Policy.
    |
    */
    'password_max_age_days' => (int) env('PASSWORD_MAX_AGE_DAYS', 90),
    'login_max_attempts' => (int) env('LOGIN_MAX_ATTEMPTS', 5),
    'login_lockout_seconds' => (int) env('LOGIN_LOCKOUT_SECONDS', 900),
    'login_group_lookup_max_attempts' => (int) env('LOGIN_GROUP_LOOKUP_MAX_ATTEMPTS', 30),
    'login_group_lookup_decay_seconds' => (int) env('LOGIN_GROUP_LOOKUP_DECAY_SECONDS', 60),
    'mfa_required_roles' => array_values(array_filter(array_map('trim', explode(',', (string) env('MFA_REQUIRED_ROLES', 'super_admin,admin_group'))))),
    'mfa_max_attempts' => (int) env('MFA_MAX_ATTEMPTS', 5),
    'mfa_lockout_seconds' => (int) env('MFA_LOCKOUT_SECONDS', 300),
    'upload_scan_enabled' => filter_var(env('UPLOAD_SCAN_ENABLED', false), FILTER_VALIDATE_BOOL),
    'upload_scanner_command' => env('UPLOAD_SCANNER_COMMAND', '/usr/bin/clamscan'),
    'upload_reject_double_extensions' => filter_var(env('UPLOAD_REJECT_DOUBLE_EXTENSIONS', true), FILTER_VALIDATE_BOOL),
];
