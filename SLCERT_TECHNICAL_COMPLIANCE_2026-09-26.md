# Sri Lanka CERT Technical Security Compliance – Carder Management

Date: 2026-09-26

Basis: the supplied **Technical Guidelines for Web Application & Website Security (Version 1.0, 2022)** and **Information and Cyber Security Policy for Government Organizations (2023)**.

This register deliberately distinguishes controls implemented in Laravel from controls that require hospital/server/organizational action. Source-code changes alone do not constitute Sri Lanka CERT certification.

## Application controls implemented

| Control | Status | Implementation |
|---|---|---|
| Password minimum/complexity | Implemented | Laravel password validation; temporary passwords force replacement. |
| 90-day password ageing | Implemented | `password_changed_at` + `PASSWORD_MAX_AGE_DAYS=90`. |
| Current password for password change | Implemented | Existing password must be supplied. |
| Password hashing | Implemented | Laravel `hashed` cast / framework password hasher. |
| Login lockout | Implemented | Per-account/IP rate limiting. |
| Generic invalid-login response | Implemented | No account-existence disclosure from password failure. |
| Mandatory password before notice | Implemented | Forced/expired password middleware executes before administrative acknowledgement. |
| MFA for privileged roles | Implemented / configurable | Default required roles: `super_admin,admin_group`. |
| MFA brute-force control | Implemented | Independent OTP attempt limiter and temporary lockout. |
| Session regeneration | Implemented | Regenerated on login, successful MFA and MFA enrolment. |
| Concurrent-session restriction | Implemented | Current session token middleware. |
| Inactivity/expiry configuration | Implemented | 30-minute session example; expire-on-close enabled. |
| CSRF | Implemented | Laravel CSRF middleware for web forms. |
| Cookie controls | Implemented with transport caveat | HttpOnly/SameSite; Secure must remain false while deployment is HTTP-only. |
| Security response headers | Implemented | Frame/type/referrer/permissions controls; HSTS only on HTTPS. |
| Server-side + client-side validation | Substantially implemented | Laravel validation plus UI validation; global leading-space control. |
| Secure upload pre-check | Implemented | Central `SecureUploadService` rejects suspicious filenames/double extensions and supports mandatory malware scanning. |
| Private sensitive uploads | Implemented where sensitive | Employee documents, letters, signatures, imports, car-pass templates stored on local/private disk. |
| Generic production errors | Implemented | Custom safe error responses when `APP_DEBUG=false`; database/exception details are not returned to users. |
| Security event logging | Implemented at application layer | Authentication/MFA events use dedicated security channel. |
| Public route throttling | Implemented | Circular/public intern endpoints receive explicit request limits. |
| Third-party version inventory | Implemented | See `THIRD_PARTY_COMPONENT_REGISTER.md`. |
| Dependency wildcard removal | Implemented | DOMPDF dependency pinned to compatible major/minor range. |

## Controls requiring deployment / hospital IT action

### 1. HTTPS / TLS
**Open / high priority.** The application currently supports HTTP on the local intranet. This does not significantly reduce performance, but HTTP does not protect credentials or confidential HR data against packet capture on the LAN.

Current transitional settings:

```env
SESSION_SECURE_COOKIE=false
```

When HTTPS is introduced:

```env
SESSION_SECURE_COOKIE=true
```

Use an organisation-approved internal CA/certificate and trusted endpoints. HSTS becomes effective only after HTTPS is in place.

### 2. Malware scanning for uploads
The application integration is present. It must be enabled only after an approved scanner is installed and tested on the server:

```env
UPLOAD_SCAN_ENABLED=true
UPLOAD_SCANNER_COMMAND=/usr/bin/clamscan
```

With scanning enabled, uploads fail closed if the scanner is unavailable or returns an error.

### 3. Immutable/remote security logs
Laravel rotating files are not immutable. Hospital IT should forward security, application, web-server and operating-system logs to a protected remote syslog/SIEM or other write-protected destination. Define retention and review responsibility.

### 4. Database hardening
PostgreSQL should:
- have no public address;
- accept traffic only from approved application/server hosts and ports;
- use a least-privilege application role rather than a superuser;
- enable required audit/logging controls;
- encrypt protected backups;
- restrict database and backup filesystem permissions.

### 5. Server and network hardening
Hospital IT must separately enforce firewall segmentation, minimum open ports, removal/disablement of unnecessary services/default accounts, patch management, antimalware where applicable, secure administrative access, and restricted filesystem permissions. Uploaded/writable directories must not permit script execution.

### 6. Backup / DR
Define and approve RPO/RTO. Back up the database, application configuration, logs and required system state. Maintain at least one protected/off-site or appropriately separated copy and periodically perform restore tests.

### 7. Remote administration
If the Carder server is administered from outside the trusted hospital segment, use approved VPN/secure remote access, MFA and authorised endpoints. Do not expose SSH/RDP/database ports directly to untrusted networks.

## Controls requiring governance / assurance activity

- Maintain an Information Asset Register and classify Carder information (Public / Limited Sharing / Confidential / Secret as applicable).
- Complete a privacy/risk assessment and threat model for employee PII and administrative workflows.
- Maintain an approved access-control matrix and periodically review role assignments.
- Maintain a formal cybersecurity incident response procedure and incident register.
- Maintain patch/change records and rollback procedure.
- Review third-party components and versions periodically.
- Perform code/static security review after material changes.
- Perform vulnerability assessment and penetration testing before live deployment and periodically thereafter in accordance with the supplied policy/guideline.
- Document remediation and follow-up of security findings.
- Conduct backup restoration exercises.
- Review security logs routinely.

## Recommended authentication sequence

1. Login credentials
2. Existing MFA challenge (when already enrolled)
3. Mandatory/expired password change
4. Mandatory MFA enrolment for roles configured in `MFA_REQUIRED_ROLES` when not yet enrolled
5. Administrative acknowledgement notice
6. Dashboard/application

This sequence ensures that the administrative acknowledgement cannot be used to bypass mandatory password or MFA controls.
