# Pre-Production Security Verification Checklist

Use this checklist for every material release.

## Application
- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] No default/shared production admin password remains
- [ ] Forced password change tested before acknowledgement notice
- [ ] 90-day password-age rule tested
- [ ] Required privileged roles cannot access protected pages without MFA
- [ ] MFA invalid-code throttling tested
- [ ] Disabled user cannot authenticate
- [ ] Concurrent-session invalidation tested
- [ ] CSRF rejection tested
- [ ] Role-boundary tests completed for Super Admin / Admin Group / Planning Officer / Unit Manager / Subject Officer
- [ ] IDOR/horizontal-access checks completed for employee, documents, letters and reports
- [ ] Upload extension, MIME, size, suspicious filename and malware controls tested
- [ ] `UPLOAD_SCAN_ENABLED=true` in production when approved scanner is available
- [ ] Generic 403/404/419/429/500 behaviour verified with debug disabled
- [ ] Security log records login failure/success, MFA failure/success and logout
- [ ] No password/OTP/secret is written to logs

## Server / Network
- [ ] HTTPS/TLS enabled OR current HTTP intranet exception formally risk-accepted
- [ ] Only required ports open
- [ ] Database not publicly accessible
- [ ] Application database user is least privilege
- [ ] Uploaded/writable directories cannot execute scripts
- [ ] OS/web server/PHP/PostgreSQL patched to approved levels
- [ ] Default/unnecessary accounts and services disabled
- [ ] Remote administration restricted and encrypted
- [ ] Firewall/network rules approved and logged

## Assurance / Operations
- [ ] Database + configuration + logs backed up
- [ ] Restore test completed and recorded
- [ ] RTO/RPO documented
- [ ] Security logs protected/forwarded to approved destination
- [ ] Security logs reviewed by assigned officer
- [ ] Third-party component register reviewed
- [ ] Static/code security review completed
- [ ] Vulnerability assessment / penetration test completed as required before production
- [ ] Open findings have owners and remediation dates
- [ ] Incident Response Plan and escalation contacts current
