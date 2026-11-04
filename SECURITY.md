# Security policy

## Supported versions

| Version | Supported |
| --- | --- |
| 1.x | Yes |

## Reporting a vulnerability

Email **soumyadebnath1619@gmail.com** with a description, reproduction steps and impact. Do not open a public issue for a suspected vulnerability. You will receive an acknowledgement within 3 business days.

## Security controls in this repository

- OAuth 2.0 authorization server (Laravel Passport) with scoped tokens and client credentials for machine clients.
- HMAC-SHA256 verification for Keap and GTM webhooks, with timing-safe comparisons.
- Idempotency keys on application creation; replay with a different payload returns `409`.
- Per-tenant rate limiting and per-IP WordPress submission throttling.
- Phone numbers encrypted at rest; emails stored with a SHA-256 hash for deduplication.
- RBAC (`admin`, `loan_officer`, `viewer`) for decision endpoints.
- Append-only-style hash chains for domain events and audit logs, with verification tests.
- Security headers on API responses and no PII in GTM dataLayer events.
- Secrets are configured through environment variables and are never committed.

## Out of scope

- Live vendor credentials for Keap, BigQuery, GTM or a bank-statement provider.
- Infrastructure-level controls such as WAF, mTLS, KMS key rotation and WORM storage. These are documented in `docs/threat-model.md` as residual risks.
