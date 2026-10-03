# AI Chat Access & Feature Control — 2026-09-25

## Master feature switch

Super Admin controls **AI Chat — Offline Help, KB & Decision Support** from **Administration → Feature Management → AI Chat**.

The setting uses `feature_offline_kb_assistant` for backward database compatibility, but the application feature identifier is now `ai_chat`.

The migration intentionally sets the master switch to **disabled** so the feature must be explicitly enabled after deployment.

When disabled:
- user-facing AI Chat routes return 404;
- the AI Chat navigation item is hidden;
- the floating **Explain this page** action is hidden;
- no Phase 1–4 questions can be submitted;
- Super Admin retains access to Knowledge Source administration so content can be prepared and verified safely before enabling the feature.

## Access matrix

| Capability | Super Admin | Admin Group | Planning Officer | Medical Officer Planning | Unit Manager | Subject Officer |
|---|---:|---:|---:|---:|---:|---:|
| Phase 1 — Application Help | Yes | Yes | Yes | Yes (Admin Group) | Yes | Yes |
| Phase 2 — Controlled KB | Yes | Yes | Yes | Yes (Admin Group) | Yes | Yes |
| Phase 3 — Context-aware Help | Yes | Yes | Yes | Yes (Admin Group) | Yes | Yes |
| Phase 4 — Decision-support Intelligence | Yes | No* | Yes | Yes | No | No |
| Knowledge Source administration | Yes | No | No | No | No | No |
| AI Chat master enable/disable | Yes | No | No | No | No | No |

`*` Ordinary Admin Group members do not receive Phase 4 unless their category is **Medical Officer Planning**.

## Governance boundary

AI Chat is a support system only. It cannot approve or execute recruitment, transfer, promotion, discipline, procurement, expenditure, establishment changes, service reconfiguration, clinical decisions or other consequential administrative actions.
