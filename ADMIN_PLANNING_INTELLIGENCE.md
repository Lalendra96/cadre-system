# Administrative and planning intelligence

Administrative roles (Director, Deputy Director, Hospital Secretary, Administrative Officer and configured Admin Group categories) now receive aggregate decision support:

- employee counts by position, with no profile listing;
- current active intern assignments in a read-only view;
- administrative risk indicators for establishment, retirements, open increments and retirement projects;
- official report preparation and staged checking/approval/signing.

Planning Officers receive the Planning Intelligence view with bounded recruitment, retirement-horizon and salary-impact scenario inputs. The response is aggregate and does not expose employee names or confidential documents.

The internal workforce endpoint is read-only and requires an authenticated active Super Admin, Planning Officer or Admin Group user. It returns aggregate summary data only.

The official report workflow is:

`prepared → checked → approved → signed and archived`

The prepared, checked and approved actions are independent, and the signed snapshot stores a SHA-512 content hash. Historical report rows and snapshots are retained by the migration policy.
