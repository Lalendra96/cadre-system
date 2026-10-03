# Sri Lanka CERT Security Alignment Update — 2026-09-26

This update applies application-level controls from the supplied Sri Lanka CERT **Technical Guidelines for Web Application & Website Security** and **Information and Cyber Security Policy for Government Organizations**. Infrastructure controls (firewalls, TLS certificates, OS hardening, IDS/IPS, encrypted backups, vulnerability assessment and penetration testing) remain deployment/operations responsibilities and cannot be guaranteed by Laravel source code alone.

## Authentication and password flow

- Mandatory password change now **always occurs before the Administrative Acknowledgement Notice**.
- Temporary-password and 90-day-expired-password users are blocked from normal protected routes.
- Password policy: minimum 8 characters, upper/lower case, number and special character.
- Current password is required when changing the password.
- New password cannot equal the current password.
- Password changes rotate the session ID and CSRF token and invalidate remember-me credentials.
- `password_changed_at` is tracked and the maximum age is configurable with `PASSWORD_MAX_AGE_DAYS` (default 90).
- Existing users are assigned a fresh password-age cycle when the migration runs, avoiding an immediate mass lockout.

## Login protection and audit logging

- Login lockout remains 5 failed attempts but now defaults to a 15-minute lockout (`LOGIN_LOCKOUT_SECONDS=900`).
- Authentication success, failure, logout, MFA success and password-change events are written to a dedicated rotating `storage/logs/security-*.log` channel without storing passwords.
- Security-log retention defaults to 90 days and is configurable.

## Session/cookie hardening

- Session idle lifetime default reduced to 30 minutes.
- Sessions expire when the browser closes by default.
- Fresh deployments are configured with `SESSION_ENCRYPT=true`. Existing deployments can enable it after clearing/expiring old session files to avoid format-transition problems.
- HttpOnly remains enabled and SameSite remains configurable (default `lax`).
- `SESSION_SECURE_COOKIE` is intentionally environment-controlled because current hospital LAN deployments may still use HTTP. It **must be set to true after HTTPS/TLS is enabled**.

## HTTP response hardening

Added: `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: same-origin`, restrictive `Permissions-Policy`, and HSTS when the request is HTTPS.

## Production defaults

`.env.example` now defaults to `APP_ENV=production` and `APP_DEBUG=false`.

## Required deployment controls outside this source update

Before production certification/go-live, the organisation should separately verify TLS/HTTPS, firewall/DMZ rules, database network isolation, OS/database patching and least privilege, encrypted backup/restore testing, malware controls, secure remote administration, log archival/review, and Sri Lanka CERT/qualified vulnerability assessment and penetration testing.


## Local intranet HTTP deployment note

The application may be used on an HTTP-only hospital intranet as a transitional deployment. This does not materially reduce application performance, but it does mean credentials, session traffic and application data are not protected by TLS while travelling across the LAN. Sri Lanka CERT guidance recommends secure transport for passwords and sensitive information; therefore HTTPS remains the preferred target configuration.

For the current HTTP-only deployment use:

```dotenv
APP_URL=http://172.16.0.217:8000
SESSION_SECURE_COOKIE=false
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
SESSION_LIFETIME=30
SESSION_EXPIRE_ON_CLOSE=true
SESSION_ENCRYPT=true
```

Do **not** enable `SESSION_SECURE_COOKIE` until the site is actually served through HTTPS. A Secure cookie is not sent by browsers over HTTP and can otherwise cause login/session failures or `419 Page Expired` errors. The application's HSTS response header is only added to HTTPS requests.

When HTTPS/TLS becomes available, update `APP_URL` to the HTTPS address and set `SESSION_SECURE_COOKIE=true`. Prefer a certificate issued by an internal CA trusted by hospital workstations (or another appropriately trusted certificate) rather than asking users to bypass certificate warnings.

This HTTP compatibility setting is an operational exception, not a statement of full Sri Lanka CERT transport-security compliance. The residual risk should be documented and accepted by the responsible organisation while HTTPS is pending.
