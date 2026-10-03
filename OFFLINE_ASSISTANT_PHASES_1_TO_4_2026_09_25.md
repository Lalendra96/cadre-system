# Offline Help & Knowledge Assistant — Phases 1–4

## Governance boundary
The assistant is a support system only. It does not approve, authorise or execute consequential administrative, HR, legal, financial, procurement, establishment or clinical decisions.

## Phase 1 — Application Help
- Uses only local application help and user-manual sources.
- Seeded English, Sinhala and Tamil planning help.
- No internet fallback.

## Phase 2 — Controlled Knowledge Base
- Retrieval is limited to active, verified local sources.
- Source metadata is returned with every supported answer.
- Unsupported questions return a safe no-source response rather than invented policy.
- Public/internal classifications only in general chat.

## Phase 3 — Context-aware Assistant
- Every authenticated page gets an **Explain this page** floating action.
- Current Laravel route is passed as context.
- The assistant explains purpose, workflow, safeguards and next steps without granting extra permissions.

## Phase 4 — Decision-support Intelligence
- Restricted to Planning Officer, Medical Officer Planning and Super Admin through `canAccessPlanningReports()`.
- Requires explicit support-only acknowledgement before a question is submitted.
- Displays authorised aggregate planning indicators and retrieves verified KB guidance.
- Does not make or execute institutional decisions.

## Audit and security
- Every question is written to `ai_assistant_interactions` with phase, context route, source IDs, answer mode, user, timestamp, IP and user agent.
- Knowledge sources preserve verification, effective/expiry dates, classification and supersession metadata.
- No raw confidential source retrieval through general chat.
- Feature can be disabled centrally by Super Admin through Feature Management.

## Offline inference extension
The current implementation is retrieval-first and works without a local model. Ollama/llama.cpp may later be used only as an optional local summarisation layer after governed retrieval. Citations, role checks and decision boundaries should remain enforced in Laravel.
