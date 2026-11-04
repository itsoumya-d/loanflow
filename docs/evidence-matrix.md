# Evidence matrix

This matrix maps the target brief to the implementation and to a runnable proof. Every row is deliberately specific so a reviewer can open the file or run the test.

| Requirement | Status | Implementation | Proof |
| --- | --- | --- | --- |
| PHP 8.x | Implemented | `composer.json` requires `php ^8.3`; all application code is strictly typed | CI matrix `.github/workflows/ci.yml` runs 8.3, 8.4 and 8.5 |
| Laravel | Implemented | `laravel/framework ^13.17`; `app/`, `routes/`, `config/` | `php artisan --version`, feature tests |
| MySQL | Implemented | Migrations for applications, events, audit logs, webhook receipts, deliveries, OAuth | CI `mysql` job runs migrations and tests against MySQL 8 |
| JavaScript | Implemented | `wordpress/loanflow-intake/assets/js/loanflow.js`; `gtm/datalayer.mjs` | `npm run test:gtm` (6 tests) |
| REST APIs | Implemented | `routes/api.php`; `LoanApplicationController`; FormRequests; Resources | `tests/Feature/LoanApplicationSubmissionTest.php`, `LoanApplicationStatusTest.php` |
| Webhooks | Implemented | `KeapWebhookController`, `KeapWebhookVerifier`, `WebhookReceiptService` | `tests/Feature/KeapWebhookTest.php` (signature, replay, reference resolution) |
| OAuth 2.0 | Implemented | Laravel Passport authorization server; scoped tokens; client credentials; Keap token client | `tests/Feature/OAuthTokenTest.php`; `tests/Unit/KeapClientTest.php` |
| Keap CRM | Implemented | `KeapClient` upsert, retries, token cache; `KeapWebhookProcessor` | `tests/Unit/KeapClientTest.php`; `tests/Feature/KeapWebhookTest.php` |
| WordPress | Implemented | `wordpress/loanflow-intake` plugin: shortcode, REST proxy, nonce, honeypot, rate limit, GTM | `tests/WordPress/FormTest.php`, `ApiClientTest.php`, `GtmTest.php` |
| BigQuery | Implemented | `BigQueryExporter`; `ServiceAccountAccessTokenProvider`; schema | `tests/Unit/BigQueryExporterTest.php`, `ServiceAccountTokenProviderTest.php`; `docs/bigquery-schema.json` |
| Google Tag Manager | Implemented | `gtm/datalayer.mjs`; `gtm/templates/loanflow_event.tpl`; `GtmEventForwarder`; ingest endpoint | `gtm/datalayer.test.mjs`; `tests/Unit/GtmEventForwarderTest.php`; `tests/Feature/GtmEventTest.php` |
| OWASP secure coding | Implemented | Validation, Eloquent bindings, scopes, RBAC, HMAC, encryption, no secrets | `docs/threat-model.md`; security tests |
| Access control and RBAC | Implemented | Passport scopes plus `manage-loan-applications` gate | `tests/Feature/LoanApplicationStatusTest.php` |
| Secrets management | Implemented | Environment configuration; no secrets in git; token caching | `.env.example`, `SECURITY.md` |
| Logging and monitoring | Implemented | Request IDs, structured failure logs, Prometheus metrics, health endpoint | `tests/Feature/HealthAndMetricsTest.php`; `MetricsService` |
| Audit trail | Implemented | Hash-chained events and audit logs with verification | `tests/Unit/HashChainTest.php` |
| Idempotency | Implemented | Required header, payload hash, replay/conflict semantics | `LoanApplicationSubmissionTest` replay and conflict tests |
| Privacy Act 2020 considerations | Partially implemented | Consent fields, PII minimisation, no PII in GTM, timeline | `docs/threat-model.md`; retention/deletion remains operational |
| CI/CD | Implemented | GitHub Actions PHP, MySQL, Node and plugin-lint jobs | `.github/workflows/ci.yml` |
| Docker | Implemented | Multi-stage `Dockerfile`, `docker-compose.yml`, nginx/PHP config | `docker compose up -d --build` |
| Documentation | Implemented | README, architecture, threat model, ADRs, research notes | `README.md`, `docs/` |
| Automated tests | Implemented | 52 PHP tests / 149 assertions; 6 Node tests | `php artisan test`; `npm run test:gtm` |
| Bank-statement retrieval | Not implemented | Integration boundary only; provider credentials and consent flow are out of scope | Documented in `docs/architecture.md` and `README.md` |
| SMS delivery | Not implemented | Out of scope; queue and notification boundaries are ready | Documented limitation |
| AI calling | Not implemented | Out of scope; no vendor credentials or call-recording compliance model | Documented limitation |
| Paid social / LinkedIn outreach | N/A | Not part of the repository | — |

## Interview talking points

1. **State machine correctness**: terminal decisions cannot be reopened; invalid transitions return 422.
2. **Idempotency**: same key + same payload returns the original application; same key + different payload returns 409.
3. **Webhook replay**: unique `(provider, event_id)` receipt plus signature verification.
4. **OAuth boundary**: machine clients use client credentials; humans use scoped tokens plus RBAC.
5. **Outbox**: applicant submission never waits on Keap, BigQuery or GTM.
6. **Tamper evidence**: hash chains for events and audit logs; tests prove tampering is detected.
7. **Analytics without PII**: GTM events carry a reference, not an email or phone.
8. **Operational readiness**: health, metrics, Docker, CI matrix and a threat model with named residual risks.
