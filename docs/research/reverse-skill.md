# Security review (reverse-skill router)

The [reverse-skill](https://github.com/zhaoxuya520/reverse-skill) router was used as a checklist, not as an exploitation guide. The relevant modules were:

- `code-audit` for authorization, injection, secrets and error handling.
- `api-security` for OAuth scopes, rate limits, replay and webhook trust.
- `identity-federation` for token lifecycle, client types and RBAC.

No target system was attacked. The review was performed on this repository's own code.

## Findings and fixes

| # | Finding | Risk | Fix | Proof |
| --- | --- | --- | --- | --- |
| 1 | The first route design put machine submissions behind `auth:api`, which rejects client-credentials tokens | WordPress could not submit; tempting workaround would be a shared secret | Moved machine routes to Passport's `EnsureClientIsResourceOwner`; kept human routes on `auth:api` + scope | `tests/Feature/OAuthTokenTest.php`; `routes/api.php` |
| 2 | Passport hashes client secrets; a test that reused `$client->secret` failed | False sense of security; brittle tests | Tests use the one-time `plainSecret`; production uses `loanflow:client` and stores the secret immediately | `tests/Feature/OAuthTokenTest.php` |
| 3 | Webhook replay could apply a status twice | Duplicate decisions and audit noise | Unique `(provider, event_id)` receipt; state-machine validation | `tests/Feature/KeapWebhookTest.php` |
| 4 | Idempotency key alone was not enough | Same key with a different payload could be silently accepted | Store a canonical payload hash; return `409` on mismatch | `tests/Feature/LoanApplicationSubmissionTest.php` |
| 5 | PII could leak into analytics | Privacy Act and trust risk | GTM payload carries a reference and non-identifying parameters only | `gtm/datalayer.test.mjs`; `docs/threat-model.md` |
| 6 | Audit logs could be edited without detection | Repudiation | Hash-chain events and audit records; verification tests | `tests/Unit/HashChainTest.php` |
| 7 | No per-tenant API limit | Abuse and noisy-neighbour risk | `loanflow-write` limiter plus per-IP WordPress throttle | `AppServiceProvider`; `Loanflow_Plugin::handle_submission` |
| 8 | Vendor failures could break the applicant request | Lost submissions | Outbox and post-commit job; failures audited, not fatal | `ProcessLoanApplication`; `loanflow:outbox` |
| 9 | Secrets could be committed | Credential leakage | `.env.example` only; `.gitignore`; no secrets in tests or logs | `SECURITY.md`; `.gitignore` |
| 10 | Error responses could expose internals | Information disclosure | JSON envelopes; integration errors return a provider label, not a stack trace | `bootstrap/app.php` |

## Residual risks

See `docs/threat-model.md`. The largest are: no WAF/edge rate limiting, email stored in plaintext for operations, no external anchor for the audit hash, no KMS rotation, and no live vendor penetration test.
