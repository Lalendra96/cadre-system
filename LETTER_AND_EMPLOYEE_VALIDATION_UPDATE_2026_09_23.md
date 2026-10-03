# Service & Official Letter / Employee Validation Update

## Letter wording
User-facing wording now uses **Service & Official Letter Builder** and **Create Service / Official Letter**. Internal route names and database model names are unchanged for compatibility.

## Guided Employee Profile validation
- Old Sri Lankan NIC: exactly 9 digits followed by V or X (10 characters total).
- New Sri Lankan NIC: exactly 12 digits.
- NIC input strips spaces, uppercases V/X and blocks unsupported characters client-side.
- Server validation remains authoritative and enforces NIC format and uniqueness.
- Guided text fields remove accidental leading whitespace while typing and are trimmed again server-side.
- Common text fields now have explicit length limits in the guided form.
- The wizard validates the current step before allowing the user to continue.
- The regular Employee Profile form now uses the same browser NIC pattern/help text.
