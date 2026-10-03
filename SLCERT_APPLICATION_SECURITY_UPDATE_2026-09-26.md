# Carder Management – Sri Lanka CERT Application Security Update

This update applies the remaining application-side controls identified during the review of the supplied Sri Lanka CERT documents.

## Implemented in this update

- Mandatory MFA policy for privileged roles, configurable with `MFA_REQUIRED_ROLES`.
- Independent MFA verification throttling and temporary lockout.
- Mandatory workflow order: password change → required MFA enrolment → acknowledgement → dashboard.
- Central secure-upload gate with suspicious filename/double-extension rejection.
- Optional fail-closed ClamAV integration (`UPLOAD_SCAN_ENABLED=true`).
- Secure/sanitised original filenames in key document/import/circular records.
- Upload scanning integration for employee documents, circulars, letters, employee imports, e-signatures, car-pass templates and official letterhead images.
- Production-safe generic error responses and dedicated 403/404/419/429/500/503 views.
- Import exceptions no longer expose parser/internal exception text to users.
- Additional throttling for public circular and intern-selection routes.
- Third-party component/version register and removal of the DOMPDF wildcard dependency.
- SL CERT technical compliance matrix and release verification checklist.

## Deployment actions still required

Application code cannot enforce the following independently:

- HTTPS/TLS on the hospital LAN.
- Firewall/segmentation and OS/web-server/database hardening.
- Remote/immutable log storage and SIEM/syslog review.
- Approved malware scanner installation/signature updates.
- Backup encryption, RPO/RTO, off-site/air-gapped copy and restore testing.
- Formal vulnerability assessment / penetration testing.
- Organisational risk assessment, asset classification, incident response and security audit processes.

See `SLCERT_TECHNICAL_COMPLIANCE_2026-09-26.md` for the full split between implemented, deployment-required and organisational controls.
