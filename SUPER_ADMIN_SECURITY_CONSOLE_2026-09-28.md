# Super Admin Security Console — 2026-09-28

## Purpose
Adds a Super Admin-only security console for authenticated-user visibility, remote logout, forced password change, MFA reset, and database-backed security event review.

## Main controls
- Online users are users with authenticated activity within the last 5 minutes.
- Active and recently active sessions are listed with user, device/browser summary, IP, login time and last activity.
- Remote logout of a selected session.
- Revoke all sessions for a selected user.
- Force password change and revoke existing sessions.
- Reset MFA and revoke existing sessions.
- Recent security events include successful/failed authentication and administrative security actions.
- The currently logged-in Super Admin session cannot be remotely terminated from the console.
- The currently logged-in Super Admin cannot reset their own MFA from the console.
- Destructive security actions require the Super Admin's current password.

## Security design
`SESSION_DRIVER=file` remains supported. The feature does not require Laravel database sessions.

Only SHA-256 hashes of Laravel session IDs are stored in `user_security_sessions`; the raw session ID is not stored there. Activity heartbeat updates are limited to approximately once per minute per active session. Remote revocation is checked by authenticated middleware and is enforced on the next request.

Security events are written to the existing security log and, after migration, to `security_events` for the Super Admin console. Context must not contain passwords, OTPs, encryption keys or raw session IDs.

## Deployment
After copying the updated source:

```bash
php artisan migrate
php artisan db:seed --class=NavItemSeeder
php artisan optimize:clear
```

No change to `SESSION_DRIVER` is required.

## Notes
Existing logged-in users will begin appearing after their next authenticated request. New logins are registered immediately.

Remote logout takes effect on the user's next authenticated request. A browser that is completely idle cannot be forced to visibly redirect until it communicates with the server again; its next request will be rejected and redirected to login.
