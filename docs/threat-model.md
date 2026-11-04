# Threat model

## Assets

- Applicant PII: name, email, phone, income, loan amount, employment status, notes.
- Consent records: credit-check and privacy consent.
- OAuth client secrets, Passport signing keys, Keap tokens, Google service-account key.
- Application status and decision history.
- Audit logs and event hash chain.
- Analytics events in BigQuery and GTM.

## Actors

- Applicant (unauthenticated).
- WordPress site (semi-trusted client).
- Loan officer, admin and viewer (authenticated staff).
- Machine clients (WordPress intake, future decision service).
- Keap, Google BigQuery and GTM (external processors).
- Attacker with network access, a stolen token, or a compromised WordPress site.

## Trust boundaries

1. Browser → WordPress.
2. WordPress → Laravel API.
3. Laravel → MySQL.
4. Laravel → Keap / BigQuery / GTM.
5. Keap / GTM → Laravel webhooks.
6. Operator → deployment secrets and infrastructure.

## STRIDE

| Threat | Example | Controls | Residual risk |
| --- | --- | --- | --- |
| Spoofing | Forged WordPress request or webhook | OAuth 2.0 tokens, nonce, HMAC signatures, timing-safe comparison | A stolen client secret can impersonate the WordPress site until rotated |
| Tampering | Changed loan amount or status | Server-side validation, state machine, hash-chained events, audit log | A database administrator can rewrite rows and the chain unless hashes are anchored externally |
| Repudiation | Applicant denies consent | Consent flags stored with the application, audit record, timestamps | No qualified electronic signature |
| Information disclosure | PII in logs, GTM or error responses | Phone encrypted, email hash for lookup, no PII in GTM, JSON error envelopes, security headers | Email is stored in plaintext for operations; a database leak exposes it |
| Denial of service | Form spam or webhook flood | Per-IP throttle, per-tenant rate limit, request size limits, queue isolation | No WAF or global edge rate limit in this repo |
| Elevation of privilege | Viewer approves a loan | OAuth scopes plus RBAC gate | A compromised admin account has full decision power |
| Replay | Duplicate submission or webhook | Idempotency keys, unique webhook receipt per event ID, state machine | A replayed request with a new key creates a new application by design |
| Supply chain | Malicious dependency | Composer lock, `composer audit`, Dependabot, pinned CI actions | No SLSA provenance or signed releases |

## OWASP secure-coding mapping

- **A01 Broken access control:** Passport scopes, `manage-loan-applications` gate, machine/human route separation.
- **A02 Cryptographic failures:** encrypted phone cast, hashed email lookup, HMAC webhooks, Passport keys, HTTPS assumed at the edge.
- **A03 Injection:** Eloquent bindings, FormRequest validation, no raw SQL with user input.
- **A04 Insecure design:** explicit state machine, idempotency, outbox, threat model, ADRs.
- **A05 Security misconfiguration:** `.env.example` only, no secrets committed, security headers, production PHP/nginx config.
- **A06 Vulnerable components:** `composer audit` in CI, Dependabot.
- **A07 Identification and authentication failures:** OAuth 2.0, token expiry, refresh expiry, rate limits.
- **A08 Software and data integrity failures:** lock file, hash chain, signed webhooks, CI.
- **A09 Logging and monitoring failures:** structured request IDs, audit logs, Prometheus metrics, health endpoint.
- **A10 SSRF:** outbound URLs are configuration, not user input; timeouts and retries are bounded.

## Privacy Act 2020 considerations

- Collect only the fields needed to assess the application.
- Record credit-check and privacy consent explicitly.
- Do not send PII to GTM; analytics events use a reference and non-identifying parameters.
- Provide a timeline so applicants and staff can see what happened.
- Document retention and deletion as an operational policy; this reference build does not implement automated retention.

## Residual risks to close before production

1. Move rate limiting to Redis and add an edge WAF.
2. Encrypt all PII fields or use a tokenisation service; keep a separate searchable hash.
3. Move secrets to a managed secret store with rotation.
4. Anchor audit hashes in WORM storage or an external transparency log.
5. Add mTLS or private networking for Keap/BigQuery/GTM calls where available.
6. Add malware scanning and document-type validation before accepting bank statements or ID documents.
7. Add alerting on integration failure rate, webhook signature failures and queue depth.
8. Complete a privacy impact assessment and a penetration test before go-live.
