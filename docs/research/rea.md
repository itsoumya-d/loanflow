# REA notes

[REA](https://github.com/morluto/rea) is a reverse-engineering workflow: inspect the artifact, map behaviour, cite evidence and state limitations. It is normally used on binaries, JavaScript bundles, .NET assemblies or live apps.

## Artifact inventory

- A public job description (text supplied by the candidate).
- Public marketing and review pages for a New Zealand lender.
- Public documentation for OAuth 2.0, Keap REST v2, BigQuery `insertAll`, GTM server-side tagging and WordPress plugins.

No target binary, APK, private JavaScript bundle, internal API or authenticated account was available, so no binary or bundle analysis was performed. Claiming otherwise would be dishonest.

## Behaviour map

| Observed signal | Inferred behaviour | Implemented as |
| --- | --- | --- |
| "Conditional approval in about a minute" | Fast automated screening, then human review | State machine with `received → screening → approved/declined/referred`; outbox keeps the request fast |
| "Lender APIs, Keap CRM, bank statement retrieval, SMS and AI calling" | Multiple third-party integrations with retries and credentials | Generic integration architecture; Keap, BigQuery and GTM implemented; other providers documented |
| "Bring code, hosting and environments in-house: Git, CI/CD" | Delivery ownership | GitHub Actions, Docker, migrations, tests |
| "Own cyber security: OWASP, access control, MFA, secrets, logging, backups, incident response" | Security ownership | Threat model, RBAC, HMAC, encryption, audit chain, metrics |
| "GTM, server-side tagging, BigQuery" | Measurement ownership | dataLayer contract, GTM template, signed forwarder, BigQuery exporter |
| "AI-first ... Claude Code, GitHub" | Agent-assisted delivery with review | Plan-first build, tests at each layer, human review of generated code |

## Evidence → finding → path

1. **Evidence:** the ad asks for a PHP/MySQL portal and a WordPress presence. **Finding:** the intake surface must be decoupled from the API. **Path:** WordPress plugin → OAuth 2.0 → Laravel API.
2. **Evidence:** the ad asks for lender/CRM integrations and webhooks. **Finding:** retries and replay safety are mandatory. **Path:** idempotency keys, webhook receipts, outbox.
3. **Evidence:** the ad asks for regulated-data security. **Finding:** auditability and PII minimisation are different controls. **Path:** hash chains plus no PII in GTM.
4. **Evidence:** the ad asks for weekly reporting against dates, cost and return. **Finding:** status and timeline data must be queryable. **Path:** timeline endpoint and metrics.

## Limitations

- No target binary or private bundle was inspected.
- Integration behaviour is based on public API documentation, not live vendor accounts.
- The public job ad is a marketing document, not an internal architecture spec.
